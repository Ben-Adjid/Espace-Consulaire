<div class="space-y-6">
    {{-- 1. Service --}}
    <div class="card p-6">
        <h2 class="mb-3 flex items-center gap-2 text-lg font-bold text-brand">
            <span class="flex size-7 items-center justify-center rounded-full bg-brand text-sm text-white">1</span> {{ __('Choisissez le service') }}
        </h2>
        <select wire:model.live="serviceId" class="input" aria-label="{{ __('Service') }}">
            <option value="">{{ __('— Sélectionnez un service —') }}</option>
            @foreach ($services as $s)
                <option value="{{ $s->id }}">{{ $s->name }}</option>
            @endforeach
        </select>
        @error('serviceId') <p class="field-error">{{ $message }}</p> @enderror
    </div>

    {{-- 2. Date --}}
    @if ($service)
        <div class="card p-6">
            <h2 class="mb-3 flex items-center gap-2 text-lg font-bold text-brand">
                <span class="flex size-7 items-center justify-center rounded-full bg-brand text-sm text-white">2</span> {{ __('Choisissez la date') }}
            </h2>
            @if (count($dates))
                <div class="grid grid-cols-2 gap-2 sm:grid-cols-3 md:grid-cols-4">
                    @foreach ($dates as $d)
                        @php($c = \Carbon\Carbon::parse($d)->locale($locale))
                        <button type="button" wire:click="selectDate('{{ $d }}')" @class([
                            'rounded-lg border px-3 py-2 text-start text-sm transition',
                            'border-brand bg-brand text-white' => $date === $d,
                            'border-slate-300 bg-white hover:border-brand' => $date !== $d,
                        ]) aria-pressed="{{ $date === $d ? 'true' : 'false' }}">
                            <span class="block text-xs uppercase opacity-80">{{ $c->isoFormat('dddd') }}</span>
                            <span class="font-semibold">{{ $c->isoFormat('D MMM YYYY') }}</span>
                        </button>
                    @endforeach
                </div>
            @else
                <p class="text-slate-600">{{ __('Aucune disponibilité pour ce service pour le moment. Merci de nous contacter.') }}</p>
            @endif
            @error('date') <p class="field-error">{{ $message }}</p> @enderror
        </div>
    @endif

    {{-- 3. Créneau --}}
    @if ($service && $date)
        <div class="card p-6">
            <h2 class="mb-3 flex items-center gap-2 text-lg font-bold text-brand">
                <span class="flex size-7 items-center justify-center rounded-full bg-brand text-sm text-white">3</span> {{ __('Choisissez le créneau') }}
            </h2>
            @if ($slots->isNotEmpty())
                <div class="grid gap-2 sm:grid-cols-2">
                    @foreach ($slots as $slot)
                        <label @class([
                            'flex cursor-pointer items-center justify-between rounded-lg border p-3 text-sm',
                            'border-brand bg-brand/5 ring-2 ring-brand/30' => $slotId === $slot->id,
                            'border-slate-300 hover:border-brand' => $slotId !== $slot->id,
                        ])>
                            <span class="flex items-center gap-3">
                                <input type="radio" wire:model.live="slotId" value="{{ $slot->id }}" class="size-4 accent-[var(--brand)]">
                                <span class="font-semibold" dir="ltr">{{ $slot->label() }}</span>
                            </span>
                            <span class="text-xs text-slate-500">{{ trans_choice('{1} :count place|[2,*] :count places', $slot->remaining, ['count' => $slot->remaining]) }}</span>
                        </label>
                    @endforeach
                </div>
            @else
                <p class="text-slate-600">{{ __('Ce jour est complet. Choisissez une autre date.') }}</p>
            @endif
            @error('slotId') <p class="field-error" role="alert">{{ $message }}</p> @enderror
        </div>
    @endif

    {{-- 4. Coordonnées --}}
    @if ($service && $date && $slotId)
        <form wire:submit="submit" class="card space-y-4 p-6" novalidate>
            <h2 class="flex items-center gap-2 text-lg font-bold text-brand">
                <span class="flex size-7 items-center justify-center rounded-full bg-brand text-sm text-white">4</span> {{ __('Vos coordonnées') }}
            </h2>

            {{-- Honeypot --}}
            <div class="hp" aria-hidden="true"><label>Website <input type="text" wire:model="website" tabindex="-1" autocomplete="off"></label></div>

            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label class="label" for="bk-name">{{ __('Nom complet') }} *</label>
                    <input class="input" id="bk-name" wire:model="full_name" maxlength="120" autocomplete="name" required>
                    @error('full_name') <p class="field-error">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="label" for="bk-phone">{{ __('Téléphone') }} *</label>
                    <input class="input" id="bk-phone" type="tel" wire:model="phone" maxlength="32" autocomplete="tel" dir="ltr" required>
                    @error('phone') <p class="field-error">{{ $message }}</p> @enderror
                </div>
                <div class="sm:col-span-2">
                    <label class="label" for="bk-email">{{ __('Email') }} *</label>
                    <input class="input" id="bk-email" type="email" wire:model="email" maxlength="160" autocomplete="email" dir="ltr" required>
                    @error('email') <p class="field-error">{{ $message }}</p> @enderror
                </div>
                <div class="sm:col-span-2">
                    <label class="label" for="bk-notes">{{ __('Message') }} ({{ __('facultatif') }})</label>
                    <textarea class="input" id="bk-notes" wire:model="notes" rows="3" maxlength="500"></textarea>
                    @error('notes') <p class="field-error">{{ $message }}</p> @enderror
                </div>
            </div>

            <button type="submit" class="btn-primary w-full py-3 text-base sm:w-auto" wire:loading.attr="disabled">
                <span wire:loading.remove wire:target="submit">{{ __('Confirmer le rendez-vous') }}</span>
                <span wire:loading wire:target="submit">{{ __('Enregistrement…') }}</span>
            </button>
            <p class="text-xs text-slate-500">{{ __('Vos données servent uniquement à gérer ce rendez-vous (démo : aucune donnée réelle n’est exploitée).') }}</p>
        </form>
    @endif
</div>
