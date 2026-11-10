@if ($paginator->hasPages())
    <nav class="flex items-center justify-between gap-3" role="navigation" aria-label="Pagination">
        @if ($paginator->onFirstPage())
            <span class="btn border border-slate-200 text-slate-400">← {{ __('Précédent') }}</span>
        @else
            <a class="btn-outline" href="{{ $paginator->previousPageUrl() }}" rel="prev">← {{ __('Précédent') }}</a>
        @endif
        <span class="text-sm text-slate-500">{{ $paginator->currentPage() }} / {{ $paginator->lastPage() }}</span>
        @if ($paginator->hasMorePages())
            <a class="btn-outline" href="{{ $paginator->nextPageUrl() }}" rel="next">{{ __('Suivant') }} →</a>
        @else
            <span class="btn border border-slate-200 text-slate-400">{{ __('Suivant') }} →</span>
        @endif
    </nav>
@endif
