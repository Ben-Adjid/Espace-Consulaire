<x-filament-panels::page>
    <x-filament::section>
        <x-slot name="heading">{{ __('Exporter le rapport d\'un mois') }}</x-slot>
        <x-slot name="description">{{ __('Par service : rendez-vous, présents, absences et annulations, et total encaissé. Aucune donnée personnelle ne figure dans le fichier.') }}</x-slot>

        <div class="grid gap-4 sm:grid-cols-3">
            <div>
                <label class="text-sm font-medium" for="rapport-month">{{ __('Mois') }}</label>
                <input id="rapport-month" type="month" wire:model="month" class="fi-input mt-1 w-full rounded-lg border-gray-300 dark:bg-gray-900" />
            </div>
            @if (auth()->user()->isSuperAdmin())
                <div>
                    <label class="text-sm font-medium" for="rapport-consulate">{{ __('Consulat') }}</label>
                    <select id="rapport-consulate" wire:model="consulateId" class="mt-1 w-full rounded-lg border-gray-300 dark:bg-gray-900">
                        @foreach ($this->consulateOptions() as $id => $label)
                            <option value="{{ $id }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
            @endif
            <div class="flex items-end">
                <a href="{{ $this->downloadUrl() }}" class="fi-btn fi-btn-size-md rounded-lg bg-primary-600 px-4 py-2 text-sm font-semibold text-white hover:bg-primary-500">
                    {{ __('Télécharger le CSV') }}
                </a>
                <a href="{{ $this->printUrl() }}" target="_blank" rel="noopener" class="fi-btn fi-btn-size-md ms-2 rounded-lg border border-gray-300 px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50">
                    {{ __('Imprimer / PDF') }}
                </a>
            </div>
        </div>
    </x-filament::section>
</x-filament-panels::page>
