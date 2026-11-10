@extends('layouts.app')

@section('title', __('Actualités'))
@section('description', __('Annonces et actualités du consulat.'))

@section('content')
<section class="bg-brand text-white">
    <div class="mx-auto max-w-6xl px-4 py-10">
        <h1 class="text-3xl font-bold">{{ __('Actualités et annonces') }}</h1>
    </div>
</section>
<section class="mx-auto max-w-6xl px-4 py-10">
    <div class="grid gap-5 md:grid-cols-3">
        @forelse ($news as $item) @include('news._card', ['item' => $item]) @empty
            <p class="text-slate-500">{{ __('Aucune actualité pour le moment.') }}</p>
        @endforelse
    </div>
    <div class="mt-8">{{ $news->links('pagination.simple') }}</div>
</section>
@endsection
