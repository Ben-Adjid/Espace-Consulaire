@extends('layouts.app')

@section('title', __('Contact'))
@section('description', __('Contactez le consulat : formulaire, téléphone, email et WhatsApp.'))

@section('content')
<section class="bg-brand text-white">
    <div class="mx-auto max-w-5xl px-4 py-10"><h1 class="text-3xl font-bold">{{ __('Nous contacter') }}</h1></div>
</section>

<div class="mx-auto grid max-w-5xl gap-8 px-4 py-10 md:grid-cols-5">
    <div class="md:col-span-3">
        @if (session('success'))
            <div class="mb-6 rounded-lg border border-green-300 bg-green-50 p-4 text-green-800" role="status">
                {{ __('Merci, votre message a bien été envoyé. Le consulat vous répondra dans les meilleurs délais.') }}
            </div>
        @endif

        <form method="POST" action="{{ route('contact.store') }}" class="card space-y-4 p-6" novalidate>
            @csrf
            {{-- Anti-spam : honeypot (champ piège) + horodatage chiffré --}}
            <div class="hp" aria-hidden="true">
                <label>Website <input type="text" name="website" tabindex="-1" autocomplete="off" value=""></label>
            </div>
            <input type="hidden" name="_t" value="{{ $timestamp }}">

            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label class="label" for="name">{{ __('Nom complet') }} *</label>
                    <input class="input" id="name" name="name" value="{{ old('name') }}" required maxlength="120" autocomplete="name">
                    @error('name') <p class="field-error">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="label" for="email">{{ __('Email') }} *</label>
                    <input class="input" id="email" type="email" name="email" value="{{ old('email') }}" required maxlength="160" autocomplete="email" dir="ltr">
                    @error('email') <p class="field-error">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="label" for="phone">{{ __('Téléphone') }}</label>
                    <input class="input" id="phone" type="tel" name="phone" value="{{ old('phone') }}" maxlength="32" autocomplete="tel" dir="ltr">
                    @error('phone') <p class="field-error">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="label" for="subject">{{ __('Sujet') }} *</label>
                    <input class="input" id="subject" name="subject" value="{{ old('subject') }}" required maxlength="160">
                    @error('subject') <p class="field-error">{{ $message }}</p> @enderror
                </div>
            </div>
            <div>
                <label class="label" for="message">{{ __('Message') }} *</label>
                <textarea class="input" id="message" name="message" rows="6" required maxlength="4000">{{ old('message') }}</textarea>
                @error('message') <p class="field-error">{{ $message }}</p> @enderror
            </div>
            <button class="btn-primary px-6 py-3" type="submit">{{ __('Envoyer le message') }}</button>
        </form>
    </div>

    <aside class="space-y-4 md:col-span-2">
        <div class="card space-y-4 p-5 text-sm">
            <div class="flex gap-3"><x-ico name="pin" class="size-5 shrink-0 text-brand" /><span>{{ $consulate->address }}</span></div>
            @if ($consulate->phone) <div class="flex gap-3"><x-ico name="phone" class="size-5 shrink-0 text-brand" /><bdi dir="ltr">{{ $consulate->phone }}</bdi></div> @endif
            @if ($consulate->email) <div class="flex gap-3"><x-ico name="mail" class="size-5 shrink-0 text-brand" /><a class="text-brand hover:underline" href="mailto:{{ $consulate->email }}">{{ $consulate->email }}</a></div> @endif
            <div class="flex gap-3">
                <x-ico name="clock" class="size-5 shrink-0 text-brand" />
                <ul>@foreach (preg_split('/\R/', trim((string) $consulate->opening_hours)) as $line) @if (trim($line) !== '') <li>{{ trim($line) }}</li> @endif @endforeach</ul>
            </div>
        </div>
        @if ($consulate->whatsappNumber())
            <a target="_blank" rel="noopener noreferrer" href="https://wa.me/{{ $consulate->whatsappNumber() }}?text={{ rawurlencode(__('Bonjour, j’ai une question concernant vos services consulaires.')) }}"
               class="btn w-full bg-green-600 py-3 text-white hover:bg-green-700"><x-ico name="chat" class="size-5" /> {{ __('Écrire sur WhatsApp') }}</a>
        @endif
    </aside>
</div>
@endsection
