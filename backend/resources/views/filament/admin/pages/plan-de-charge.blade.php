<x-filament-panels::page>
    <x-filament::section>
        <x-slot name="heading">{{ __('Charge par service') }}</x-slot>
        <x-slot name="description">{{ __('Places ouvrables du mois (créneaux × jours d\'ouverture, jours fermés exclus) face aux rendez-vous pris. Un taux élevé signale qu\'il faut ouvrir des créneaux.') }}</x-slot>

        <div class="mb-4 max-w-xs">
            <label class="text-sm font-medium" for="charge-month">{{ __('Mois') }}</label>
            <input id="charge-month" type="month" wire:model.live="month" class="mt-1 w-full rounded-lg border-gray-300 dark:bg-gray-900" />
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="border-b text-gray-500">
                    <tr><th class="py-2">{{ __('Service') }}</th><th class="py-2">{{ __('Places') }}</th><th class="py-2">{{ __('Pris') }}</th><th class="py-2">{{ __('Taux') }}</th></tr>
                </thead>
                <tbody class="divide-y">
                    @forelse ($this->rows() as $row)
                        <tr>
                            <td class="py-2 font-medium">{{ $row['service'] }}</td>
                            <td class="py-2">{{ $row['places'] }}</td>
                            <td class="py-2">{{ $row['pris'] }}</td>
                            <td class="py-2">
                                <span class="font-semibold {{ $row['taux'] >= 90 ? 'text-danger-600' : ($row['taux'] >= 70 ? 'text-warning-600' : 'text-success-600') }}">{{ $row['taux'] }} %</span>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="py-4 text-gray-500">{{ __('Aucun service ouvert aux rendez-vous.') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-filament::section>
</x-filament-panels::page>
