@extends('layouts.app')

@section('title', $page->title)

@section('content')
<article class="mx-auto max-w-3xl px-4 py-10">
    <h1 class="mb-6 text-3xl font-extrabold text-brand">{{ $page->title }}</h1>
    <div class="card p-6 text-slate-700"><x-prose :text="$page->body" /></div>
</article>
@endsection
