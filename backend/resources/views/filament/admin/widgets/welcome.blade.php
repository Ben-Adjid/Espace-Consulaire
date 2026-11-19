<x-filament-widgets::widget>
    <div class="overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-gray-950/5">
        <div class="flex flex-col gap-4 p-6 sm:flex-row sm:items-center sm:justify-between" style="background: linear-gradient(120deg, rgba(18,59,54,.06), rgba(243,201,105,.10));">
            <div>
                <p class="text-xs font-semibold uppercase tracking-wider text-gray-500">{{ \Illuminate\Support\Str::ucfirst($date) }}</p>
                <h2 class="mt-1 text-2xl font-bold tracking-tight text-gray-950">{{ $greeting }}, {{ $name }}</h2>
                <p class="mt-1 text-sm text-gray-600">
                    {{ $roleLabel }}@if ($consulate) · {{ $consulate }}@endif
                </p>
            </div>
            <span class="inline-flex w-fit items-center gap-2 rounded-full bg-white/80 px-3 py-1.5 text-xs font-semibold text-primary-700 ring-1 ring-primary-600/20">
                <x-filament::icon icon="heroicon-m-shield-check" class="h-4 w-4" />
                {{ __('Accès vérifié pour votre rôle') }}
            </span>
        </div>

        @if (count($shortcuts))
            {{-- Grille en style intégré : la feuille de style précompilée de Filament ne contient pas toutes les classes. --}}
            <div class="border-t border-gray-100 p-4" style="display:grid;grid-template-columns:repeat(auto-fill,minmax(7.75rem,1fr));gap:.75rem;">
                @foreach ($shortcuts as $s)
                    <a href="{{ $s['url'] }}"
                       class="group flex flex-col items-start gap-2 rounded-xl border border-gray-200 p-3 transition hover:border-primary-300 hover:bg-primary-50/50 focus-visible:outline focus-visible:outline-2 focus-visible:outline-primary-600">
                        <span class="flex h-9 w-9 items-center justify-center rounded-lg text-primary-700" style="background:rgba(27,81,72,.08);">
                            <x-filament::icon :icon="$s['icon']" class="h-5 w-5" />
                        </span>
                        <span class="text-sm font-semibold text-gray-800">{{ $s['label'] }}</span>
                    </a>
                @endforeach
            </div>
        @endif
    </div>
</x-filament-widgets::widget>
