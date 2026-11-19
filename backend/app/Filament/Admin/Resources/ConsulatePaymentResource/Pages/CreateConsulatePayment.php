<?php

namespace App\Filament\Admin\Resources\ConsulatePaymentResource\Pages;

use App\Filament\Admin\Resources\ConsulatePaymentResource;
use App\Models\Consulate;
use App\Models\ConsulatePayment;
use App\Services\SubscriptionPeriod;
use Filament\Resources\Pages\CreateRecord;

class CreateConsulatePayment extends CreateRecord
{
    protected static string $resource = ConsulatePaymentResource::class;

    /**
     * Renouvellement en un clic : depuis la liste des consulats, le formulaire arrive déjà rempli
     * (consulat, période suivante, montant du dernier paiement). Il reste à confirmer le montant reçu.
     */
    public function mount(): void
    {
        parent::mount();

        $consulate = Consulate::withoutGlobalScopes()->find(request()->integer('consulate_id'));
        if (! $consulate) {
            return;
        }

        [$start, $end] = SubscriptionPeriod::nextFor($consulate);
        $lastAmount = ConsulatePayment::withoutGlobalScopes()->where('consulate_id', $consulate->id)
            ->latest('paid_at')->value('amount');

        $this->form->fill([
            'consulate_id' => $consulate->id,
            'amount' => $lastAmount,
            'currency' => config('subscription.default_currency'),
            'paid_at' => now()->toDateString(),
            'period_start' => $start->toDateString(),
            'period_end' => $end->toDateString(),
        ]);
    }

    /** Trace de qui a enregistré le paiement. */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['recorded_by'] = auth()->id();

        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
