@extends('layouts.app')

@section('title', __('Rendez-vous enregistré'))

@section('content')
<section class="mx-auto max-w-2xl px-4 py-12">
    <div class="card overflow-hidden">
        <div class="bg-green-600 p-6 text-center text-white">
            <x-ico name="check" class="mx-auto size-12" />
            <h1 class="mt-2 text-2xl font-bold">{{ __('Votre demande est enregistrée') }}</h1>
        </div>
        <div class="space-y-5 p-6">
            <div class="rounded-lg bg-slate-50 p-4 text-center">
                <p class="text-xs uppercase tracking-wide text-slate-500">{{ __('Référence') }}</p>
                <p class="select-all font-mono text-2xl font-bold tracking-wider text-brand" dir="ltr">{{ $appointment->reference }}</p>
            </div>
            <dl class="grid gap-3 text-sm sm:grid-cols-2">
                <div><dt class="text-slate-500">{{ __('Service') }}</dt><dd class="font-semibold">{{ $appointment->service->name }}</dd></div>
                <div><dt class="text-slate-500">{{ __('Date') }}</dt><dd class="font-semibold">{{ $appointment->appointment_date->isoFormat('dddd D MMMM YYYY') }}</dd></div>
                <div><dt class="text-slate-500">{{ __('Heure') }}</dt><dd class="font-semibold" dir="ltr">{{ substr($appointment->start_time, 0, 5) }} – {{ substr($appointment->end_time, 0, 5) }}</dd></div>
                <div><dt class="text-slate-500">{{ __('Nom complet') }}</dt><dd class="font-semibold">{{ $appointment->full_name }}</dd></div>
                <div class="sm:col-span-2"><dt class="text-slate-500">{{ __('Adresse') }}</dt><dd class="font-semibold">{{ $consulate->address }}</dd></div>
            </dl>
            <p class="rounded-lg bg-amber-50 p-3 text-sm text-amber-900">
                {{ __('Un email de confirmation vous a été envoyé. Conservez votre référence et apportez les documents requis.') }}
            </p>
            <div class="flex flex-wrap gap-3">
                <button type="button" onclick="window.print()" class="btn-outline">{{ __('Imprimer') }}</button>
                <a href="{{ route('home') }}" class="btn-primary">{{ __('Retour à l’accueil') }}</a>
            </div>
        </div>
    </div>
</section>
@endsection
