@extends('layouts.app')

@section('title', $service->name)
@section('description', \Illuminate\Support\Str::limit(strip_tags((string) ($service->summary ?: $service->description)), 155))

@section('content')
<section class="bg-brand text-white">
    <div class="mx-auto max-w-4xl px-4 py-10">
        <nav class="mb-3 text-sm text-white/80" aria-label="{{ __('Fil d’Ariane') }}">
            <a class="hover:underline" href="{{ route('services.index') }}">{{ __('Services consulaires') }}</a> <span aria-hidden="true">/</span> {{ $service->name }}
        </nav>
        <h1 class="flex items-center gap-3 text-3xl font-bold"><x-icon :name="$service->icon" class="size-9" /> {{ $service->name }}</h1>
        <p class="mt-3 max-w-2xl text-white/90">{{ $service->summary }}</p>
    </div>
</section>

<div class="mx-auto grid max-w-4xl gap-8 px-4 py-10 md:grid-cols-3">
    <div class="space-y-8 md:col-span-2">
        @if ($service->description)
            <section>
                <h2 class="mb-3 text-xl font-bold text-brand">{{ __('Description') }}</h2>
                <x-prose :text="$service->description" class="text-slate-700" />
            </section>
        @endif

        @if (count($service->documentsList()))
            <section>
                <h2 class="mb-3 text-xl font-bold text-brand">{{ __('Documents requis') }}</h2>
                <p class="mb-3 text-sm text-slate-500">{{ __('Cochez les documents au fur et à mesure que vous les rassemblez.') }}</p>
                <ul class="card divide-y divide-slate-100">
                    @foreach ($service->documentsList() as $i => $doc)
                        <li>
                            <label class="flex cursor-pointer items-start gap-3 p-4">
                                <input type="checkbox" class="mt-1 size-5 shrink-0 accent-[var(--brand)]">
                                <span class="text-slate-800">{{ $doc }}</span>
                            </label>
                        </li>
                    @endforeach
                </ul>
            </section>
        @endif
    </div>

    <aside class="space-y-4">
        <div class="card space-y-4 p-5">
            <div><p class="text-xs uppercase tracking-wide text-slate-500">{{ __('Frais') }}</p><p class="text-lg font-bold">{{ $service->fee ?: '—' }}</p></div>
            <div><p class="text-xs uppercase tracking-wide text-slate-500">{{ __('Délai de traitement') }}</p><p class="text-lg font-bold">{{ $service->processing_time ?: '—' }}</p></div>
            <p class="text-xs text-slate-400">{{ __('Montants et délais fictifs (démo).') }}</p>
            @if ($service->allow_appointment)
                <a href="{{ route('appointment.create', ['service' => $service->slug]) }}" class="btn-accent w-full py-3">
                    <x-icon name="calendar" class="size-5" /> {{ __('Prendre rendez-vous') }}
                </a>
            @endif
        </div>
        @if ($others->isNotEmpty())
            <div class="card p-5">
                <h2 class="mb-2 font-bold">{{ __('Autres services') }}</h2>
                <ul class="space-y-1 text-sm">
                    @foreach ($others as $o)
                        <li><a class="text-brand hover:underline" href="{{ route('services.show', $o->slug) }}">{{ $o->name }}</a></li>
                    @endforeach
                </ul>
            </div>
        @endif
    </aside>
</div>
@endsection
