<?php

namespace App\Console\Commands;

use App\Models\Consulate;
use App\Models\PreRequest;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;

/**
 * Alerte de délai : prévient, une fois par jour au plus, l'agent qui a un dossier en retard.
 * Un dossier sans attribution alerte l'équipe qui peut décider (decide_requests) du consulat.
 * Une seule alerte par personne et par jour, même si la commande tourne plusieurs fois.
 */
class AlertOverdueRequests extends Command
{
    protected $signature = 'consulat:alert-overdue';

    protected $description = 'Envoie aux agents les pré-demandes dont le délai cible est dépassé (un email par jour au plus).';

    public function handle(): int
    {
        $sent = 0;

        foreach (Consulate::withoutGlobalScopes()->where('is_active', true)->get() as $consulate) {
            $overdue = PreRequest::withoutGlobalScopes()
                ->where('consulate_id', $consulate->id)
                ->where('status', 'submitted')
                ->with(['service', 'citizen'])
                ->get()
                ->filter(fn (PreRequest $p) => $p->isOverdue());

            if ($overdue->isEmpty()) {
                continue;
            }

            $staff = User::query()->where('consulate_id', $consulate->id)->where('is_active', true)->get();
            $teamWhoDecides = $staff->filter(fn (User $u) => $u->can('decide_requests'));

            // Par destinataire : ses dossiers en retard ; les dossiers non attribués vont à l'équipe.
            $byRecipient = [];
            foreach ($overdue as $request) {
                if ($request->assigned_to) {
                    $byRecipient[$request->assigned_to][] = $request;
                } else {
                    foreach ($teamWhoDecides as $member) {
                        $byRecipient[$member->id][] = $request;
                    }
                }
            }

            foreach ($byRecipient as $userId => $requests) {
                $user = $staff->firstWhere('id', $userId);
                if (! $user || empty($user->email)) {
                    continue;
                }

                // Une alerte par personne et par jour.
                $key = 'overdue-alert:'.$user->id.':'.now()->toDateString();
                if (! Cache::add($key, true, now()->endOfDay())) {
                    continue;
                }

                Mail::raw($this->body($requests), function ($message) use ($user) {
                    $message->to($user->email)->subject('Pré-demandes en retard à traiter');
                });
                $sent++;
            }
        }

        $this->info("Alertes envoyées : {$sent}.");

        return self::SUCCESS;
    }

    /** @param  list<PreRequest>  $requests */
    private function body(array $requests): string
    {
        $lines = array_map(fn (PreRequest $p) => sprintf(
            '- #%d · %s · %s · échéance le %s',
            $p->id,
            $p->service?->name ?? '—',
            $p->citizen?->full_name ?? '—',
            $p->dueAt()?->format('d/m/Y') ?? '—',
        ), $requests);

        return "Bonjour,\n\nCes pré-demandes ont dépassé leur délai cible de traitement :\n\n"
            .implode("\n", $lines)
            ."\n\nMerci de les traiter dans l'espace d'administration.";
    }
}
