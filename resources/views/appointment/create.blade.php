@extends('layouts.app')

@section('title', __('Prendre rendez-vous'))
@section('description', __('Réservez en ligne un rendez-vous au consulat : choisissez le service, la date et le créneau.'))

@section('content')
<section class="bg-brand text-white">
    <div class="mx-auto max-w-4xl px-4 py-10">
        <h1 class="text-3xl font-bold">{{ __('Prendre rendez-vous') }}</h1>
        <p class="mt-2 text-white/90">{{ __('Choisissez un service, une date puis un créneau disponible. Vous recevrez un email avec votre référence.') }}</p>
    </div>
</section>
<section class="mx-auto max-w-4xl px-4 py-10">
    <livewire:booking-form :consulate-id="$consulate->id" :locale="$locale" :preselected="$preselected" />
</section>
@endsection
