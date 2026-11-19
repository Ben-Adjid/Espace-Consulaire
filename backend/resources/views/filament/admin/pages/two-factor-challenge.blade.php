<x-filament-panels::page>
    <x-filament::section class="mx-auto max-w-md">
        <x-slot name="heading">{{ __('Vérification en deux étapes') }}</x-slot>
        <x-slot name="description">
            {{ $useRecovery ? 'Saisissez un de vos codes de secours.' : 'Saisissez le code à 6 chiffres affiché par votre application d’authentification.' }}
        </x-slot>

        <form wire:submit="verify" class="space-y-4">
            <x-filament::input.wrapper :valid="! $errors->has('code')">
                <x-filament::input type="text" autocomplete="one-time-code" :inputmode="$useRecovery ? 'text' : 'numeric'" wire:model="code" autofocus />
            </x-filament::input.wrapper>
            @error('code') <p class="text-sm text-danger-600">{{ $message }}</p> @enderror

            <label class="flex items-start gap-2 text-sm text-gray-700">
                <input type="checkbox" wire:model="rememberDevice" class="mt-1 rounded border-gray-300" />
                <span>{{ __('Ne plus redemander le code sur ce navigateur jusqu’au renouvellement annuel (le mot de passe reste demandé).') }}</span>
            </label>

            <div class="flex items-center justify-between gap-3">
                <button type="button" wire:click="$toggle('useRecovery')" class="text-sm text-gray-600 underline">
                    {{ $useRecovery ? 'Utiliser le code de l’application' : 'Utiliser un code de secours' }}
                </button>
                <x-filament::button type="submit">{{ __('Vérifier') }}</x-filament::button>
            </div>
        </form>
    </x-filament::section>
</x-filament-panels::page>
