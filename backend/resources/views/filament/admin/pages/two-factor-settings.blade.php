<x-filament-panels::page>
    <x-filament::section>
        <x-slot name="heading">{{ __('Double authentification') }}</x-slot>
        <x-slot name="description">
            {{ __('En plus du mot de passe, un code à 6 chiffres généré par une application (Google Authenticator,') }}
            {{ __('Microsoft Authenticator, FreeOTP…) est demandé à chaque connexion. Fortement recommandé pour tout compte d\'administration.') }}
        </x-slot>

        @if ($required && ! $enabled)
            <div class="mb-4 rounded-lg bg-warning-50 p-3 text-sm text-warning-700 dark:bg-warning-500/10 dark:text-warning-400">
                {{ __('La double authentification est obligatoire pour votre rôle. Activez-la pour accéder au reste de l\'administration.') }}
            </div>
        @endif

        @if ($enabled)
            <p class="text-sm font-semibold text-success-600">{{ __('Activée.') }}</p>

            @if (count($freshRecoveryCodes))
                <div class="mt-4 rounded-lg border border-gray-200 p-4 dark:border-white/10">
                    <p class="mb-2 text-sm font-semibold">{{ __('Codes de secours (à conserver en lieu sûr, affichés une seule fois) :') }}</p>
                    <ul class="grid grid-cols-2 gap-1 font-mono text-sm">
                        @foreach ($freshRecoveryCodes as $c)
                            <li>{{ $c }}</li>
                        @endforeach
                    </ul>
                    <p class="mt-2 text-xs text-gray-500">{{ __('Chaque code ne sert qu\'une fois, si vous perdez votre téléphone.') }}</p>
                </div>
            @endif

            @unless ($required)
                <form wire:submit="disable" class="mt-6 flex flex-col gap-3 sm:flex-row sm:items-end">
                    <div class="sm:w-56">
                        <label class="text-sm font-medium" for="disable-code">{{ __('Code actuel pour désactiver') }}</label>
                        <x-filament::input.wrapper :valid="! $errors->has('code')">
                            <x-filament::input id="disable-code" type="text" inputmode="numeric" autocomplete="one-time-code" wire:model="code" />
                        </x-filament::input.wrapper>
                        @error('code') <p class="mt-1 text-sm text-danger-600">{{ $message }}</p> @enderror
                    </div>
                    <x-filament::button type="submit" color="danger" outlined>{{ __('Désactiver') }}</x-filament::button>
                </form>
            @endunless
        @elseif ($pendingSecret)
            <div class="grid gap-6 md:grid-cols-2">
                <div>
                    <p class="mb-2 text-sm">{{ __('1. Scannez ce QR code avec votre application :') }}</p>
                    <div class="inline-block rounded-lg bg-white p-3">{!! $qr !!}</div>
                    <p class="mt-2 text-xs text-gray-500">{{ __('Ou saisissez la clé :') }} <span class="font-mono">{{ $pendingSecret }}</span></p>
                </div>
                <form wire:submit="confirmEnrollment" class="space-y-3">
                    <label class="text-sm" for="enroll-code">{{ __('2. Saisissez le code à 6 chiffres affiché :') }}</label>
                    <x-filament::input.wrapper :valid="! $errors->has('code')">
                        <x-filament::input id="enroll-code" type="text" inputmode="numeric" autocomplete="one-time-code" wire:model="code" />
                    </x-filament::input.wrapper>
                    @error('code') <p class="text-sm text-danger-600">{{ $message }}</p> @enderror
                    <x-filament::button type="submit">{{ __('Confirmer l\'activation') }}</x-filament::button>
                </form>
            </div>
        @else
            <x-filament::button wire:click="startEnrollment" icon="heroicon-o-shield-check">{{ __('Activer la double authentification') }}</x-filament::button>
        @endif
        @if (auth()->user()?->hasTwoFactorEnabled())
            <div class="mt-4 border-t border-gray-200 pt-4">
                <p class="mb-2 text-sm text-gray-600">{{ __('Appareils de confiance : ce navigateur ne redemande pas le code à 6 chiffres jusqu’au prochain renouvellement annuel.') }}</p>
                <x-filament::button color="gray" wire:click="forgetDevices" icon="heroicon-o-no-symbol">{{ __('Oublier tous les appareils') }}</x-filament::button>
            </div>
        @endif
    </x-filament::section>
</x-filament-panels::page>
