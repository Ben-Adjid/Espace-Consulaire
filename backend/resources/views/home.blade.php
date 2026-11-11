@extends('layouts.app')

@section('title', __('Accueil'))
@section('description', \Illuminate\Support\Str::limit(strip_tags((string) $consulate->hero_subtitle), 155))

@section('content')
{{-- Hero --}}
<section class="bg-brand text-white">
    <div class="mx-auto grid max-w-6xl items-center gap-8 px-4 py-14 md:grid-cols-5">
        <div class="md:col-span-3">
            <p class="mb-3 inline-block rounded-full bg-white/15 px-3 py-1 text-xs font-semibold tracking-wide">
                {{ $consulate->represented_country }} · {{ $consulate->host_country }}
            </p>
            <h1 class="text-3xl font-extrabold leading-tight sm:text-4xl lg:text-5xl">{{ $consulate->hero_title ?: $consulate->name }}</h1>
            @if ($consulate->hero_subtitle)
                <p class="mt-4 max-w-xl text-base text-white/90 sm:text-lg">{{ $consulate->hero_subtitle }}</p>
            @endif
            <div class="mt-7 flex flex-wrap gap-3">
                <a href="{{ route('appointment.create') }}" class="btn-accent px-6 py-3 text-base">
                    <x-ico name="calendar" class="size-5" /> {{ __('Prendre rendez-vous') }}
                </a>
                <a href="{{ route('services.index') }}" class="btn border border-white/60 px-6 py-3 text-base text-white hover:bg-white/10">
                    {{ __('Nos services') }}
                </a>
            </div>
        </div>
        <div class="hidden justify-self-end md:col-span-2 md:block">
            <div class="rounded-2xl bg-white/10 p-8 backdrop-blur">
                <x-logo :consulate="$consulate" :size="120" />
            </div>
        </div>
    </div>
</section>

{{-- Accès rapide aux services --}}
<section class="mx-auto max-w-6xl px-4 py-12">
    <h2 class="section-title mb-6">{{ __('Accès rapide aux services') }}</h2>
    <div class="grid grid-cols-2 gap-4 md:grid-cols-4">
        @foreach ($services->take(8) as $service)
            <a href="{{ route('services.show', $service->slug) }}" class="card group flex flex-col items-center gap-3 p-5 text-center transition hover:-translate-y-0.5 hover:border-brand hover:shadow-md">
                <span class="flex size-12 items-center justify-center rounded-full bg-brand/10 text-brand group-hover:bg-brand group-hover:text-white">
                    <x-ico :name="$service->icon" class="size-6" />
                </span>
                <span class="text-sm font-semibold text-slate-800">{{ $service->name }}</span>
            </a>
        @endforeach
    </div>
    <p class="mt-5"><a href="{{ route('services.index') }}" class="font-semibold text-brand hover:underline">{{ __('Tous les services') }} →</a></p>
</section>

{{-- Mot du consul + mission --}}
<section class="bg-white py-12">
    <div class="mx-auto grid max-w-6xl gap-8 px-4 md:grid-cols-2">
        @if ($consulate->consul_message)
            <div>
                <h2 class="section-title mb-4">{{ __('Le mot du consul') }}</h2>
                <blockquote class="border-s-4 border-brand-2 ps-4 text-slate-700">
                    <x-prose :text="$consulate->consul_message" />
                    @if ($consulate->consul_name)
                        <footer class="mt-2 text-sm font-semibold text-brand">— {{ $consulate->consul_name }}</footer>
                    @endif
                </blockquote>
            </div>
        @endif
        @if ($consulate->mission)
            <div>
                <h2 class="section-title mb-4">{{ __('Notre mission') }}</h2>
                <x-prose :text="$consulate->mission" class="text-slate-700" />
            </div>
        @endif
    </div>
</section>

{{-- Horaires, adresse, contacts, carte --}}
<section class="mx-auto max-w-6xl px-4 py-12">
    <h2 class="section-title mb-6">{{ __('Nous trouver') }}</h2>
    <div class="grid gap-6 md:grid-cols-2">
        <div class="card space-y-5 p-6">
            <div class="flex gap-3">
                <x-ico name="pin" class="mt-0.5 size-6 shrink-0 text-brand" />
                <div><h3 class="font-semibold">{{ __('Adresse') }}</h3><p class="text-slate-600">{{ $consulate->address }}</p></div>
            </div>
            <div class="flex gap-3">
                <x-ico name="clock" class="mt-0.5 size-6 shrink-0 text-brand" />
                <div>
                    <h3 class="font-semibold">{{ __('Horaires d’ouverture') }}</h3>
                    <ul class="text-slate-600">
                        @foreach (preg_split('/\R/', trim((string) $consulate->opening_hours)) as $line)
                            @if (trim($line) !== '') <li>{{ trim($line) }}</li> @endif
                        @endforeach
                    </ul>
                </div>
            </div>
            <div class="flex gap-3">
                <x-ico name="phone" class="mt-0.5 size-6 shrink-0 text-brand" />
                <div>
                    <h3 class="font-semibold">{{ __('Contacts') }}</h3>
                    @if ($consulate->phone) <p class="text-slate-600"><bdi dir="ltr">{{ $consulate->phone }}</bdi></p> @endif
                    @if ($consulate->email) <p><a class="text-brand hover:underline" href="mailto:{{ $consulate->email }}">{{ $consulate->email }}</a></p> @endif
                    @if ($consulate->whatsappNumber())
                        <p><a class="font-semibold text-green-700 hover:underline" target="_blank" rel="noopener noreferrer" href="https://wa.me/{{ $consulate->whatsappNumber() }}">WhatsApp</a></p>
                    @endif
                </div>
            </div>
        </div>
        <div class="card overflow-hidden">
            @if ($consulate->latitude && $consulate->longitude)
                <div id="map" data-lat="{{ $consulate->latitude }}" data-lng="{{ $consulate->longitude }}" data-label="{{ $consulate->name }}" class="bg-slate-100"></div>
                <p class="p-3 text-xs text-slate-500">
                    <a class="underline" target="_blank" rel="noopener noreferrer"
                       href="https://www.openstreetmap.org/?mlat={{ $consulate->latitude }}&mlon={{ $consulate->longitude }}#map=16/{{ $consulate->latitude }}/{{ $consulate->longitude }}">{{ __('Ouvrir dans OpenStreetMap') }}</a>
                    — {{ __('position fictive à titre de démonstration') }}
                </p>
            @endif
        </div>
    </div>
</section>

{{-- Dernières actualités --}}
@if ($news->isNotEmpty())
<section class="mx-auto max-w-6xl px-4 pb-6">
    <div class="mb-6 flex items-end justify-between">
        <h2 class="section-title">{{ __('Dernières actualités') }}</h2>
        <a href="{{ route('news.index') }}" class="text-sm font-semibold text-brand hover:underline">{{ __('Toutes les actualités') }} →</a>
    </div>
    <div class="grid gap-5 md:grid-cols-3">
        @foreach ($news as $item) @include('news._card', ['item' => $item]) @endforeach
    </div>
</section>
@endif
@endsection
