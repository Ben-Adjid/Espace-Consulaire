<?php

namespace App\Console\Commands;

use App\Models\Consulate;
use App\Models\Service;
use App\Models\ServiceFee;
use Illuminate\Console\Command;

/**
 * Crée (ou met à jour) les deux variantes de la carte consulaire pour un consulat :
 * papier (2 000 FCFA) et numérique (5 000 FCFA), avec les mêmes pièces exigées.
 * Idempotent : relancer la commande ne crée aucun doublon.
 */
class AddConsularCardServices extends Command
{
    protected $signature = 'consulat:add-consular-card-services {consulate : Slug du consulat} {--legal-reference= : Référence de l’arrêté ou de la décision tarifaire}';

    protected $description = 'Crée les variantes papier et numérique de la carte consulaire avec leurs pièces et tarifs.';

    /** Pièces confirmées : photocopie du passeport, 2 photos d'identité, certificat de résidence. */
    private const DOCUMENTS = [
        'fr' => "Photocopie du passeport\n2 photos d'identité\nCertificat de résidence",
        'en' => "Photocopy of the passport\n2 passport photos\nCertificate of residence",
        'ar' => "صورة من جواز السفر\nصورتان شخصيتان\nشهادة الإقامة",
    ];

    /** Variantes : slug, nom, tarif en FCFA. */
    private const VARIANTS = [
        ['slug' => 'carte-consulaire-papier', 'name' => 'Carte consulaire (papier)', 'amount' => 2000],
        ['slug' => 'carte-consulaire-numerique', 'name' => 'Carte consulaire (numérique)', 'amount' => 5000],
    ];

    public function handle(): int
    {
        $consulate = Consulate::withoutGlobalScopes()->where('slug', $this->argument('consulate'))->first();
        if (! $consulate) {
            $this->error('Consulat introuvable.');

            return self::FAILURE;
        }

        $legal = (string) ($this->option('legal-reference') ?: 'À renseigner (arrêté tarifaire)');

        foreach (self::VARIANTS as $variant) {
            $service = Service::withoutGlobalScopes()->updateOrCreate(
                ['consulate_id' => $consulate->id, 'slug' => $variant['slug']],
                [
                    'name' => ['fr' => $variant['name'], 'en' => str_replace(['Carte consulaire', 'papier', 'numérique'], ['Consular card', 'paper', 'digital'], $variant['name']), 'ar' => $variant['name']],
                    'required_documents' => self::DOCUMENTS,
                    'is_active' => true,
                    'allow_appointment' => true,
                ],
            );

            // Un tarif enregistré ne se modifie pas : on ne le crée que s'il n'existe pas encore.
            $exists = ServiceFee::withoutGlobalScopes()->where('service_id', $service->id)
                ->whereDate('effective_from', now()->toDateString())->exists();
            if (! $exists) {
                ServiceFee::withoutGlobalScopes()->create([
                    'service_id' => $service->id,
                    'amount' => $variant['amount'],
                    'currency' => 'FCFA',
                    'effective_from' => now()->toDateString(),
                    'legal_reference' => $legal,
                ]);
            }

            $this->info("{$variant['name']} : {$variant['amount']} FCFA (service « {$variant['slug']} »).");
        }

        return self::SUCCESS;
    }
}
