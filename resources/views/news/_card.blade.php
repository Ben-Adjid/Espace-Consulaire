<a href="{{ route('news.show', $item->slug) }}" class="card group flex flex-col overflow-hidden transition hover:shadow-md">
    @if ($item->imageUrl())
        <img src="{{ $item->imageUrl() }}" alt="" loading="lazy" class="h-40 w-full object-cover">
    @else
        <div class="flex h-40 items-center justify-center bg-brand/10 text-brand"><x-ico name="document" class="size-12 opacity-60" /></div>
    @endif
    <div class="flex flex-1 flex-col p-5">
        <time class="text-xs font-semibold uppercase tracking-wide text-brand-3" datetime="{{ $item->published_at?->toDateString() }}">
            {{ $item->published_at?->isoFormat('LL') }}
        </time>
        <h3 class="mt-1 text-lg font-bold text-slate-900 group-hover:text-brand">{{ $item->title }}</h3>
        <p class="mt-2 text-sm text-slate-600">{{ $item->excerpt }}</p>
    </div>
</a>
