@extends('layouts.app')

@section('title', __('Choisissez votre consulat'))

@section('content')
<section class="bg-slate-900 px-4 py-14 text-center text-white">
    <h1 class="mx-auto max-w-3xl text-3xl font-bold sm:text-4xl">{{ __('Plateforme multi-consulats') }}</h1>
    <p class="mx-auto mt-4 max-w-2xl text-slate-300">
        {{ __('Une seule plateforme pour digitaliser plusieurs consulats africains : services, rendez-vous en ligne, actualités et contact. Choisissez un consulat de démonstration.') }}
    </p>
</section>

<section class="mx-auto max-w-5xl px-4 py-10">
    <div class="grid gap-6 sm:grid-cols-2">
        @forelse ($consulates as $c)
            <a href="{{ url('/'.$c->slug) }}" class="card block overflow-hidden transition hover:-translate-y-0.5 hover:shadow-md">
                <div class="h-2" style="background:linear-gradient(90deg,{{ $c->primary_color }} 33.3%,{{ $c->secondary_color }} 33.3% 66.6%,{{ $c->accent_color }} 66.6%)"></div>
                <div class="flex items-center gap-4 p-6">
                    <span style="--brand:{{ $c->primary_color }};--brand-2:{{ $c->secondary_color }};--brand-3:{{ $c->accent_color }}">
                        <x-logo :consulate="$c" :size="64" />
                    </span>
                    <div>
                        <h2 class="text-lg font-bold text-slate-900">{{ $c->name }}</h2>
                        <p class="text-sm text-slate-500">{{ $c->represented_country }} → {{ $c->host_country }}</p>
                        <p class="mt-2 text-sm font-semibold text-blue-700">{{ __('Voir le site') }} →</p>
                    </div>
                </div>
            </a>
        @empty
            <p class="text-slate-500">{{ __('Aucun consulat actif.') }}</p>
        @endforelse
    </div>
    <p class="mt-8 text-center text-sm text-slate-500">
        {{ __('Administration') }} : <a class="text-blue-700 underline" href="{{ url('/admin') }}">/admin</a>
    </p>
</section>
@endsection
