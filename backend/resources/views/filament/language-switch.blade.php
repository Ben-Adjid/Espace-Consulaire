{{-- Sélecteur de langue de l'administration (FR / EN / العربية). La préférence est enregistrée pour l'utilisateur. --}}
@php($current = app()->getLocale())
<nav aria-label="{{ __('Langue') }}" style="display:inline-flex;overflow:hidden;border-radius:9999px;border:1px solid rgba(15,23,42,.15);font-size:.75rem;font-weight:600;">
    @foreach (['fr' => 'FR', 'en' => 'EN', 'ar' => 'العربية'] as $code => $name)
        <a href="{{ route('admin.locale', $code) }}" hreflang="{{ $code }}" lang="{{ $code }}"
           @if ($code === $current) aria-current="true" @endif
           style="padding:.35rem .65rem;{{ $code === $current ? 'background:#1b5148;color:#fff;' : 'color:#334155;background:#fff;' }}">{{ $name }}</a>
    @endforeach
</nav>
