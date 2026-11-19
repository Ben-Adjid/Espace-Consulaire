<?php

namespace App\Actions;

use App\Mail\AssociationRoleMail;
use App\Models\Association;
use App\Models\AssociationOfficer;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

/**
 * Archive le bureau en cours sans élection (fin de mandat d'un an, vacance, démission).
 * Les mandats se terminent la veille de la date indiquée ; le bureau reste dans l'historique,
 * et les citoyens concernés sont prévenus par email.
 */
class CloseAssociationBoard
{
    /** Une seule clôture de bureau hors élection par période de 30 jours (plafond anti-abus). */
    public const MIN_DAYS_BETWEEN_CLOSURES = 30;

    public function __invoke(Association $association, ?string $endsOn = null, bool $byPlatformOwner = false): int
    {
        if (! $byPlatformOwner && $association->last_board_closed_at !== null
            && $association->last_board_closed_at->diffInDays(now()) < self::MIN_DAYS_BETWEEN_CLOSURES) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'closure' => [__('Le bureau a déjà été archivé il y a moins de :days jours. Organisez une élection.', ['days' => self::MIN_DAYS_BETWEEN_CLOSURES])],
            ]);
        }

        $end = CarbonImmutable::parse($endsOn ?? now()->toDateString());
        $association->forceFill(['last_board_closed_at' => now()])->save();

        $open = AssociationOfficer::with('citizen')
            ->where('association_id', $association->id)
            ->where(fn ($q) => $q->whereNull('mandate_end')->orWhere('mandate_end', '>=', $end->toDateString()))
            ->get();

        DB::transaction(function () use ($open, $end) {
            AssociationOfficer::whereIn('id', $open->pluck('id'))
                ->update(['mandate_end' => $end->subDay()->toDateString()]);
        });

        foreach ($open as $officer) {
            if ($officer->citizen?->email) {
                Mail::to($officer->citizen->email)->send(new AssociationRoleMail(
                    $officer->citizen,
                    'ended',
                    $association->name,
                    Association::ROLES[$officer->role] ?? $officer->role,
                    $end->subDay()->toDateString(),
                ));
            }
        }

        app(EndMemberships::class)($association);

        return $open->count();
    }
}
