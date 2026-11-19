<?php

namespace App\Models;

use App\Models\Concerns\BelongsToConsulate;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;

/**
 * Reçu d'encaissement consulaire. Numéro séquentiel par consulat et par année (ex. TCH-SN-2026-000042).
 * Jamais modifié ni supprimé : une erreur se corrige par une annulation motivée (traçée).
 */
class ConsularReceipt extends Model
{
    use BelongsToConsulate;

    public const METHODS = [
        'especes' => 'Espèces',
        'carte' => 'Carte bancaire',
        'virement' => 'Virement',
        'mobile_money' => 'Mobile money',
        'timbre' => 'Timbre fiscal',
    ];

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'received_at' => 'datetime',
            'cancelled_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        // Anti-doublon : un seul reçu valide par rendez-vous, et un seul par démarche (dossier en ligne).
        // Un reçu annulé ne compte plus ; il faut alors encaisser de nouveau, en connaissance de cause.
        static::creating(function (ConsularReceipt $receipt) {
            if ($receipt->appointment_id) {
                $appointment = Appointment::withoutGlobalScopes()->find($receipt->appointment_id);
                $validOnAppointment = static::withoutGlobalScopes()->where('appointment_id', $receipt->appointment_id)
                    ->whereNull('cancelled_at')->exists();
                if ($validOnAppointment) {
                    throw new \LogicException('Ce rendez-vous a déjà un reçu valide : annulez-le avant d’en émettre un autre.');
                }
                if ($appointment?->pre_request_id) {
                    $validOnFile = static::withoutGlobalScopes()
                        ->whereNull('cancelled_at')
                        ->whereIn('appointment_id', Appointment::withoutGlobalScopes()
                            ->where('pre_request_id', $appointment->pre_request_id)->select('id'))
                        ->exists();
                    if ($validOnFile) {
                        throw new \LogicException('Cette démarche a déjà été payée par un reçu valide.');
                    }
                }
            }

            $receipt->received_at ??= now();
            $receipt->receipt_number = DB::transaction(function () use ($receipt) {
                $consulate = Consulate::withoutGlobalScopes()->lockForUpdate()->findOrFail($receipt->consulate_id);
                $year = $receipt->received_at->format('Y');
                $prefix = $consulate->ref_prefix.'-'.$year.'-';
                $last = static::withoutGlobalScopes()
                    ->where('consulate_id', $consulate->id)
                    ->where('receipt_number', 'like', $prefix.'%')
                    ->orderByDesc('receipt_number')
                    ->value('receipt_number');
                $next = $last ? ((int) substr($last, strlen($prefix))) + 1 : 1;

                return $prefix.str_pad((string) $next, 6, '0', STR_PAD_LEFT);
            });
        });

        // Un reçu ne se modifie pas, sauf pour l'annuler.
        static::updating(function (ConsularReceipt $receipt) {
            $allowed = ['cancelled_at', 'cancel_reason', 'cancelled_by', 'updated_at'];
            if (array_diff(array_keys($receipt->getDirty()), $allowed) !== []) {
                throw new \LogicException('Un reçu enregistré ne peut pas être modifié : annulez-le et émettez-en un nouveau.');
            }
        });

        static::deleting(function () {
            throw new \LogicException('Un reçu ne peut pas être supprimé : annulez-le.');
        });
    }

    public function isCancelled(): bool
    {
        return $this->cancelled_at !== null;
    }

    public function appointment(): BelongsTo
    {
        return $this->belongsTo(Appointment::class)->withoutGlobalScopes();
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class)->withoutGlobalScopes();
    }

    public function cashier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'received_by')->withoutGlobalScopes();
    }
}
