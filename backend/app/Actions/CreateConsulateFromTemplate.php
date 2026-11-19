<?php

namespace App\Actions;

use App\Models\Consulate;
use App\Models\ConsulatePayment;
use App\Models\Service;
use App\Models\TimeSlot;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Assistant de création d'un nouveau consulat, en une seule opération :
 * consulat, services, créneaux, premier paiement d'abonnement, activation, puis invitation du consul.
 * Tout ce qui précède la transaction est atomique : un échec ne laisse aucun consulat à moitié créé.
 */
class CreateConsulateFromTemplate
{
    /**
     * @param  array<string, mixed>  $data  identité, services (slugs), créneaux, premier paiement, consul
     */
    public function __invoke(array $data, ?int $recordedBy = null): Consulate
    {
        [$consulate, $consul] = DB::transaction(function () use ($data, $recordedBy) {
            $consulate = Consulate::create([
                'slug' => $data['slug'],
                'ref_prefix' => $data['ref_prefix'],
                'name' => ['fr' => $data['name']],
                'represented_country' => ['fr' => $data['represented_country']],
                'host_country' => ['fr' => $data['host_country']],
                'covered_countries' => array_values($data['covered_countries']),
                'languages' => $data['languages'],
                'primary_color' => '#002664',
                'secondary_color' => '#FECB00',
                'accent_color' => '#C60C30',
                'email' => $data['email'] ?? null,
                'phone' => $data['phone'] ?? null,
                'address' => ['fr' => $data['address'] ?? null],
                'is_active' => false,
            ]);

            // Services : les intitulés du modèle commun, prêts à être affinés par le consulat.
            $catalog = collect((require database_path('seeders/data/common.php'))['services'] ?? [])->keyBy('slug');
            foreach ($data['services'] as $slug) {
                $def = $catalog->get($slug);
                if (! $def) {
                    continue;
                }
                Service::withoutGlobalScopes()->create([
                    'consulate_id' => $consulate->id,
                    'slug' => $slug,
                    'name' => $def['name'],
                    'is_active' => true,
                    'allow_appointment' => true,
                ]);
            }

            // Créneaux : chaque ligne s'applique aux jours choisis, pour tous les services.
            foreach ($data['slots'] as $row) {
                foreach ($row['weekdays'] as $weekday) {
                    TimeSlot::withoutGlobalScopes()->create([
                        'consulate_id' => $consulate->id,
                        'weekday' => (int) $weekday,
                        'start_time' => $row['start_time'],
                        'end_time' => $row['end_time'],
                        'capacity' => (int) $row['capacity'],
                        'is_active' => true,
                    ]);
                }
            }

            // Premier paiement : prolonge l'abonnement jusqu'à la fin de la période (observateur du modèle).
            $p = $data['payment'];
            ConsulatePayment::create([
                'consulate_id' => $consulate->id,
                'amount' => $p['amount'],
                'currency' => $p['currency'],
                'paid_at' => $p['paid_at'],
                'period_start' => $p['period_start'],
                'period_end' => $p['period_end'],
                'method' => $p['method'],
                'reference' => $p['reference'] ?? null,
                'notes' => 'Premier paiement, enregistré à la création du consulat.',
                'recorded_by' => $recordedBy,
            ]);

            // Activation explicite : le paiement a été reçu et l'abonnement est en cours.
            $consulate->forceFill(['is_active' => true])->save();

            $consul = User::create([
                'name' => $data['consul_name'],
                'email' => $data['consul_email'],
                'password' => Hash::make(Str::random(40)),
                'consulate_id' => $consulate->id,
                'is_active' => true,
            ]);
            $consul->syncRoles([User::ROLE_CONSULATE_ADMIN]);

            return [$consulate, $consul];
        });

        // Invitation hors transaction : un échec d'envoi ne défait pas le consulat ; on peut la renvoyer.
        try {
            app(SendAdminInvitation::class)->execute($consul);
        } catch (\Throwable $e) {
            Log::warning('Invitation du consul non envoyée', ['consulate' => $consulate->slug, 'error' => $e->getMessage()]);
        }

        return $consulate;
    }
}
