<x-filament-panels::page>
    <div class="space-y-4">
        {{-- Navigation de semaine --}}
        <div style="display:flex;flex-wrap:wrap;align-items:center;justify-content:space-between;gap:.75rem;">
            <div style="display:flex;gap:.5rem;align-items:center;">
                <x-filament::button color="gray" icon="heroicon-m-chevron-left" wire:click="previousWeek" :label-sr-only="true">{{ __('Semaine précédente') }}</x-filament::button>
                <x-filament::button color="gray" wire:click="thisWeek">{{ __('Cette semaine') }}</x-filament::button>
                <x-filament::button color="gray" icon="heroicon-m-chevron-right" wire:click="nextWeek" :label-sr-only="true">{{ __('Semaine suivante') }}</x-filament::button>
            </div>
            <p class="text-sm font-semibold text-gray-700">{{ $label }} · {{ trans_choice(':count rendez-vous', $total, ['count' => $total]) }}</p>
        </div>

        {{-- Une colonne par jour (défilement horizontal sur petit écran) --}}
        <div style="display:grid;grid-template-columns:repeat(6,minmax(11rem,1fr));gap:.75rem;overflow-x:auto;padding-bottom:.25rem;">
            @foreach ($days as $day)
                <section class="rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5" style="{{ $day['is_today'] ? 'outline:2px solid rgb(var(--primary-600));' : '' }}">
                    <header class="border-b border-gray-100 px-3 py-2">
                        <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">{{ $day['date']->locale(app()->getLocale())->isoFormat('dddd') }}</p>
                        <p class="text-lg font-bold text-gray-900">{{ $day['date']->locale(app()->getLocale())->isoFormat('D MMM') }}</p>
                    </header>
                    <div class="space-y-2 p-3">
                        @if ($day['closed'])
                            <p class="rounded-lg bg-danger-50 px-2 py-1 text-xs font-semibold text-danger-700">{{ __('Fermé') }} · {{ $day['closed']->label }}</p>
                        @endif
                        @if ($day['absent']->isNotEmpty())
                            <p class="rounded-lg bg-warning-50 px-2 py-1 text-xs text-warning-800">{{ __('Absents :') }} {{ $day['absent']->implode(', ') }}</p>
                        @endif
                        @forelse ($day['appointments'] as $a)
                            <div class="rounded-lg border border-gray-200 px-2 py-1.5">
                                <p class="text-xs font-semibold text-gray-900" dir="ltr">{{ \Illuminate\Support\Str::substr((string) $a->start_time, 0, 5) }}</p>
                                <p class="truncate text-sm text-gray-800">{{ $a->full_name }}</p>
                                <p class="truncate text-xs text-gray-500">{{ $a->service?->name }} · {{ $a->status?->getLabel() }}</p>
                            </div>
                        @empty
                            <p class="text-xs text-gray-400">{{ __('Aucun rendez-vous') }}</p>
                        @endforelse
                    </div>
                </section>
            @endforeach
        </div>
    </div>
</x-filament-panels::page>
