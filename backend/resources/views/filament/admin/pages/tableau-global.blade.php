<x-filament-panels::page>
    @php($d = $this->data())
    <div class="mb-4 max-w-xs">
        <label class="text-sm font-medium" for="global-month">{{ __('Mois') }}</label>
        <input id="global-month" type="month" wire:model.live="month" class="mt-1 w-full rounded-lg border-gray-300 dark:bg-gray-900" />
    </div>

    <div class="mb-6 grid gap-4 sm:grid-cols-4">
        <x-filament::section compact><p class="text-sm text-gray-500">{{ __('Consulats') }}</p><p class="text-2xl font-bold">{{ $d['totals']['consulates'] }}</p><p class="text-xs text-gray-500">{{ __(':active actifs · :suspended suspendus', ['active' => $d['totals']['active'], 'suspended' => $d['totals']['suspended']]) }}</p></x-filament::section>
        <x-filament::section compact><p class="text-sm text-gray-500">{{ __('Citoyens actifs') }}</p><p class="text-2xl font-bold">{{ $d['totals']['citizens'] }}</p></x-filament::section>
        <x-filament::section compact><p class="text-sm text-gray-500">{{ __('Rendez-vous du mois') }}</p><p class="text-2xl font-bold">{{ $d['totals']['appointments'] }}</p><p class="text-xs text-gray-500">{{ __(':count présents', ['count' => $d['totals']['present']]) }}</p></x-filament::section>
        <x-filament::section compact><p class="text-sm text-gray-500">{{ __('À traiter') }}</p><p class="text-2xl font-bold">{{ __(':count pré-demandes', ['count' => $d['totals']['pre_requests']]) }}</p><p class="text-xs text-gray-500">{{ __(':count signalements ouverts', ['count' => $d['totals']['open_reports']]) }}</p></x-filament::section>
    </div>

    <x-filament::section :heading="__('Par consulat')">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="border-b text-gray-500">
                    <tr><th class="py-2">{{ __('Consulat') }}</th><th class="py-2">{{ __('Abonnement') }}</th><th class="py-2">{{ __('Échéance') }}</th><th class="py-2">{{ __('Citoyens') }}</th><th class="py-2">{{ __('Rendez-vous') }}</th><th class="py-2">{{ __('Présents') }}</th><th class="py-2">{{ __('Pré-demandes') }}</th><th class="py-2">{{ __('Signalements') }}</th><th class="py-2">{{ __('Présence') }}</th><th class="py-2">{{ __('Délai moyen de décision') }}</th><th class="py-2">{{ __('En retard') }}</th></tr>
                </thead>
                <tbody class="divide-y">
                    @forelse ($d['rows'] as $r)
                        <tr>
                            <td class="py-2 font-medium">{{ $r['name'] }} <span class="text-xs text-gray-500">({{ $r['slug'] }})</span></td>
                            <td class="py-2"><span class="{{ $r['subscription_state'] === 'suspended' ? 'text-danger-600 font-semibold' : ($r['subscription_state'] === 'grace' || $r['subscription_state'] === 'expiring' ? 'text-warning-600' : '') }}">{{ $r['subscription'] }}</span>@unless($r['is_active']) <span class="text-xs text-gray-500">{{ __('(inactif)') }}</span>@endunless</td>
                            <td class="py-2">{{ $r['subscription_until'] ?? '—' }}</td>
                            <td class="py-2">{{ $r['citizens'] }}</td>
                            <td class="py-2">{{ $r['appointments'] }}</td>
                            <td class="py-2">{{ $r['present'] }}</td>
                            <td class="py-2">{{ $r['pre_requests'] }}</td>
                            <td class="py-2">{{ $r['open_reports'] }}</td>
                            <td class="py-2 {{ $r['attendance'] !== null && $r['attendance'] < 70 ? 'text-warning-600 font-semibold' : '' }}">{{ $r['attendance'] !== null ? $r['attendance'].' %' : '—' }}</td>
                            <td class="py-2">{{ $r['avg_days'] !== null ? __(':days j', ['days' => $r['avg_days']]) : '—' }}</td>
                            <td class="py-2 {{ $r['overdue'] > 0 ? 'text-danger-600 font-semibold' : '' }}">{{ $r['overdue'] }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="11" class="py-4 text-gray-500">{{ __('Aucun consulat.') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-filament::section>
</x-filament-panels::page>
