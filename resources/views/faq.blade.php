@extends('layouts.app')

@section('title', __('Questions fréquentes'))
@section('description', __('Réponses aux questions les plus fréquentes sur les services consulaires.'))

@section('content')
<section class="bg-brand text-white">
    <div class="mx-auto max-w-4xl px-4 py-10">
        <h1 class="text-3xl font-bold">{{ __('Questions fréquentes') }}</h1>
    </div>
</section>
<section class="mx-auto max-w-4xl px-4 py-10">
    <div class="relative mb-6">
        <x-ico name="search" class="pointer-events-none absolute start-3 top-1/2 size-5 -translate-y-1/2 text-slate-400" />
        <input id="faq-search" type="search" class="input ps-10" placeholder="{{ __('Rechercher une question…') }}" aria-label="{{ __('Rechercher une question…') }}" autocomplete="off">
    </div>

    <div class="space-y-3">
        @foreach ($faqs as $faq)
            <details data-faq-item class="card group p-0">
                <summary class="flex cursor-pointer list-none items-center justify-between gap-4 p-4 font-semibold text-slate-900">
                    <span>{{ $faq->question }}</span>
                    <x-ico name="chevron" class="size-5 shrink-0 text-brand transition group-open:rotate-180" />
                </summary>
                <div class="border-t border-slate-100 p-4 text-slate-700"><x-prose :text="$faq->answer" class="mb-0" /></div>
            </details>
        @endforeach
    </div>
    <p id="faq-empty" hidden class="mt-6 text-center text-slate-500">
        {{ __('Aucun résultat.') }} <a class="text-brand underline" href="{{ route('contact') }}">{{ __('Contactez-nous') }}</a>
    </p>
</section>
@endsection
