@extends('layouts.app')

@section('title', $item->title)
@section('description', \Illuminate\Support\Str::limit(strip_tags((string) $item->excerpt), 155))
@section('og_image', $item->imageUrl())

@section('content')
<article class="mx-auto max-w-3xl px-4 py-10">
    <nav class="mb-4 text-sm text-slate-500"><a class="text-brand hover:underline" href="{{ route('news.index') }}">← {{ __('Actualités') }}</a></nav>
    <time class="text-sm font-semibold uppercase tracking-wide text-brand-3" datetime="{{ $item->published_at?->toDateString() }}">{{ $item->published_at?->isoFormat('LL') }}</time>
    <h1 class="mt-2 text-3xl font-extrabold text-slate-900">{{ $item->title }}</h1>
    @if ($item->imageUrl())
        <img src="{{ $item->imageUrl() }}" alt="" class="mt-6 w-full rounded-xl object-cover">
    @endif
    @if ($item->excerpt) <p class="mt-6 text-lg font-medium text-slate-700">{{ $item->excerpt }}</p> @endif
    <div class="mt-6 text-slate-700"><x-prose :text="$item->body" /></div>
</article>

@if ($others->isNotEmpty())
<section class="mx-auto max-w-6xl px-4 pb-6">
    <h2 class="section-title mb-5">{{ __('À lire aussi') }}</h2>
    <div class="grid gap-5 md:grid-cols-3">@foreach ($others as $o) @include('news._card', ['item' => $o]) @endforeach</div>
</section>
@endif
@endsection
