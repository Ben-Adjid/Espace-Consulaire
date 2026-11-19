<x-filament-panels::page>
    <div wire:poll.15s class="space-y-8 text-lg">
        <div class="flex items-center justify-between">
            <p class="text-sm text-gray-500">{{ __('Mise à jour automatique toutes les 15 secondes · F11 pour le plein écran') }}</p>
            <p class="text-sm font-semibold">{{ now()->translatedFormat('l d F Y · H:i') }}</p>
        </div>

        <section>
            <h2 class="mb-3 text-2xl font-bold text-success-600">{{ __('Au guichet (') }}{{ $waiting->count() }})</h2>
            <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
                @forelse ($waiting as $a)
                    <div class="rounded-2xl border-2 border-success-500 bg-white p-5 shadow dark:bg-gray-900">
                        <div class="flex items-start justify-between">
                            <span class="text-5xl font-extrabold tracking-tight">{{ \App\Actions\CheckInAppointment::label($a->queue_number) }}</span>
                            <span class="text-sm text-gray-500">{{ __('arrivé') }} {{ $a->checked_in_at->format('H:i') }}</span>
                        </div>
                        <p class="mt-3 text-xl font-semibold">{{ $a->full_name }}</p>
                        <p class="text-gray-600">{{ $a->service?->name }}</p>
                        @if ($a->on_behalf_name)
                            <p class="text-sm text-amber-700">{{ __('Pour le compte de') }} {{ $a->on_behalf_name }}</p>
                        @endif
                        <button wire:click="complete({{ $a->id }})" class="mt-4 w-full rounded-xl bg-success-600 py-3 text-lg font-bold text-white hover:bg-success-700">{{ __('Terminé') }}</button>
                    </div>
                @empty
                    <p class="text-gray-500">{{ __('Personne au guichet pour le moment.') }}</p>
                @endforelse
            </div>
        </section>

        <section>
            <h2 class="mb-3 text-2xl font-bold text-primary-600">{{ __('Attendus aujourd\'hui (') }}{{ $expected->count() }})</h2>
            <div class="divide-y rounded-2xl border bg-white dark:bg-gray-900">
                @forelse ($expected as $a)
                    <div class="flex flex-wrap items-center justify-between gap-3 p-4">
                        <div>
                            <span class="font-mono text-gray-500" dir="ltr">{{ \Illuminate\Support\Str::substr((string) $a->start_time, 0, 5) }}</span>
                            <span class="ml-3 font-semibold">{{ $a->full_name }}</span>
                            <span class="ml-3 text-gray-500">{{ $a->service?->name }}</span>
                        </div>
                        <button wire:click="arrive({{ $a->id }})" class="rounded-xl bg-primary-600 px-5 py-2 font-bold text-white hover:bg-primary-700">{{ __('Arrivé · ticket') }}</button>
                    </div>
                @empty
                    <p class="p-4 text-gray-500">{{ __('Aucun rendez-vous restant.') }}</p>
                @endforelse
            </div>
        </section>

        @if ($done->count())
            <section>
                <h2 class="mb-3 text-lg font-semibold text-gray-500">{{ __('Terminés (') }}{{ $done->count() }})</h2>
                <p class="text-gray-500">{{ $done->map(fn ($a) => \App\Actions\CheckInAppointment::label($a->queue_number) ?? $a->reference)->implode(' · ') }}</p>
            </section>
        @endif
    </div>
</x-filament-panels::page>
