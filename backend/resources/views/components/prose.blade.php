@props(['text'])
{{-- Texte brut → paragraphes. Tout est échappé (e()) : aucun HTML saisi n'est interprété. --}}
@foreach (preg_split('/\R{2,}/', trim((string) $text)) as $paragraph)
    @if (trim($paragraph) !== '')
        <p {{ $attributes->merge(['class' => 'mb-4 leading-relaxed']) }}>{!! nl2br(e(trim($paragraph))) !!}</p>
    @endif
@endforeach
