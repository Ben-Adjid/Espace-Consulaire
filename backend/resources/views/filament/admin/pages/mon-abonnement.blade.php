<x-filament-panels::page>
    @php($consulate = auth()->user()->consulate)
    <x-filament::section>
        <x-slot name="heading">{{ __('État de l\'abonnement') }}</x-slot>
        <p class="text-lg font-semibold">{{ $this->stateLabel() }}</p>
        <p class="text-sm text-gray-500">
            @if ($consulate->subscription_until)
                Échéance : {{ $consulate->subscription_until->format('d/m/Y') }}
            @else
                {{ __('Aucun paiement enregistré pour le moment. Le règlement est fait auprès de l\'administration de la plateforme.') }}
            @endif
        </p>
    </x-filament::section>

    <x-filament::section heading="Historique des paiements">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="border-b text-gray-500">
                    <tr><th class="py-2">{{ __('Reçu le') }}</th><th class="py-2">{{ __('Période') }}</th><th class="py-2">{{ __('Montant') }}</th><th class="py-2">{{ __('Mode') }}</th><th class="py-2">{{ __('Référence') }}</th><th class="py-2"></th></tr>
                </thead>
                <tbody class="divide-y">
                    @forelse ($this->payments() as $p)
                        <tr>
                            <td class="py-2">{{ $p->paid_at->format('d/m/Y') }}</td>
                            <td class="py-2">{{ $p->period_start->format('d/m/Y') }} → {{ $p->period_end->format('d/m/Y') }}</td>
                            <td class="py-2">{{ number_format((float) $p->amount, 2, ',', ' ') }} {{ $p->currency }}</td>
                            <td class="py-2">{{ \App\Models\ConsulatePayment::METHODS[$p->method] ?? $p->method }}</td>
                            <td class="py-2">{{ $p->reference ?: '—' }}</td>
                            <td class="py-2"><a class="text-primary-600 underline" href="{{ route('admin.paiements.recu', $p) }}" target="_blank">{{ __('Reçu') }}</a></td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="py-4 text-gray-500">{{ __('Aucun paiement enregistré.') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-filament::section>
</x-filament-panels::page>
