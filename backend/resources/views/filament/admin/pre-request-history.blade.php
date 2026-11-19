{{-- Historique d'une pré-demande : étapes, auteurs et notes, du plus ancien au plus récent. --}}
<ol class="space-y-3" style="border-inline-start:2px solid rgba(15,23,42,.1);padding-inline-start:1rem;">
    @forelse ($request->events as $event)
        <li>
            <p class="text-sm font-semibold text-gray-900">{{ $event->label() }}</p>
            <p class="text-xs text-gray-500">
                {{ $event->created_at?->format('d/m/Y H:i') }} ·
                {{ $event->actor === 'citizen' ? __('Citoyen') : ($event->user?->name ?? __('Consulat')) }}
            </p>
            @if ($event->note)
                <p class="mt-1 text-sm text-gray-700">{{ $event->note }}</p>
            @endif
        </li>
    @empty
        <li class="text-sm text-gray-500">{{ __('Aucun événement enregistré pour ce dossier.') }}</li>
    @endforelse
</ol>
