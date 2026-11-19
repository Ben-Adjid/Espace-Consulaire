@php($site = rtrim((string) config('app.frontend_url'), '/'))
<div class="mt-6 space-y-4 border-t border-gray-200 pt-5 text-sm">
    <div class="flex items-start gap-3 rounded-xl bg-gray-50 p-3 text-gray-600">
        <x-filament::icon icon="heroicon-o-information-circle" class="mt-0.5 h-5 w-5 shrink-0 text-primary-600" />
        <p>{{ __('Les comptes sont créés par la plateforme (consuls) ou par le consul (agents). Il n’y a pas d’inscription publique.') }}</p>
    </div>
    <div class="flex items-start gap-3 rounded-xl border border-gray-200 p-3">
        <x-filament::icon icon="heroicon-o-user" class="mt-0.5 h-5 w-5 shrink-0 text-gray-500" />
        <p class="text-gray-600">
            {{ __('Vous êtes citoyen ?') }}
            <a href="{{ $site }}/connexion" class="font-semibold text-primary-600 hover:underline">{{ __('Accéder à l’espace citoyen') }}</a>
        </p>
    </div>
    <p class="text-center">
        <a href="{{ $site }}" class="font-semibold text-gray-500 hover:text-primary-600 hover:underline">{{ __('← Retour au site public') }}</a>
    </p>
</div>
