@extends('layouts.app')

@section('title', __('Services consulaires'))
@section('description', __('Liste des services consulaires : documents requis, frais et délais.'))

@section('content')
<section class="bg-brand text-white">
    <div class="mx-auto max-w-6xl px-4 py-10">
        <h1 class="text-3xl font-bold">{{ __('Services consulaires') }}</h1>
        <p class="mt-2 text-white/90">{{ __('Consultez les documents requis, les frais et les délais avant de vous déplacer.') }}</p>
    </div>
</section>
<section class="mx-auto max-w-6xl px-4 py-10">
    <div class="grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
        @foreach ($services as $service)
            <article class="card flex flex-col p-6">
                <span class="mb-4 flex size-12 items-center justify-center rounded-full bg-brand/10 text-brand"><x-ico :name="$service->icon" /></span>
                <h2 class="text-lg font-bold text-slate-900">{{ $service->name }}</h2>
                <p class="mt-2 flex-1 text-sm text-slate-600">{{ $service->summary }}</p>
                <dl class="mt-4 grid grid-cols-2 gap-2 text-xs">
                    <div class="rounded-md bg-slate-50 p-2"><dt class="text-slate-500">{{ __('Frais') }}</dt><dd class="font-semibold">{{ $service->fee ?: '—' }}</dd></div>
                    <div class="rounded-md bg-slate-50 p-2"><dt class="text-slate-500">{{ __('Délai') }}</dt><dd class="font-semibold">{{ $service->processing_time ?: '—' }}</dd></div>
                </dl>
                <div class="mt-5 flex gap-2">
                    <a href="{{ route('services.show', $service->slug) }}" class="btn-outline flex-1">{{ __('Détails') }}</a>
                    @if ($service->allow_appointment)
                        <a href="{{ route('appointment.create', ['service' => $service->slug]) }}" class="btn-primary flex-1">{{ __('Rendez-vous') }}</a>
                    @endif
                </div>
            </article>
        @endforeach
    </div>
</section>
@endsection
