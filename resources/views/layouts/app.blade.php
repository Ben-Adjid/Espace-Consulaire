@php
    $consulate = $consulate ?? null;
    $locale = $locale ?? app()->getLocale();
    $dir = $dir ?? 'ltr';
    $siteName = $consulate ? $consulate->name : config('app.name');
    $pageTitle = trim($__env->yieldContent('title'));
    $fullTitle = $pageTitle !== '' ? $pageTitle.' — '.$siteName : $siteName;
    $description = trim($__env->yieldContent('description')) ?: ($consulate ? \Illuminate\Support\Str::limit(strip_tags((string) $consulate->mission), 155) : __('Plateforme de démonstration pour digitaliser des consulats africains.'));
    $ogImage = trim($__env->yieldContent('og_image')) ?: ($consulate?->logoUrl());
    $nav = $consulate ? [
        ['home', 'Accueil'], ['services.index', 'Services'], ['news.index', 'Actualités'],
        ['faq', 'FAQ'], ['contact', 'Contact'],
    ] : [];
    $legalPages = $consulate ? $consulate->pages()->where('is_published', true)->orderBy('id')->get() : collect();
@endphp
<!DOCTYPE html>
<html lang="{{ $locale }}" dir="{{ $dir }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $fullTitle }}</title>
    <meta name="description" content="{{ $description }}">
    <link rel="canonical" href="{{ url()->current() }}">
    @if ($consulate)
        @foreach ($consulate->availableLocales() as $l)
            <link rel="alternate" hreflang="{{ $l }}" href="{{ url()->current() }}?lang={{ $l }}">
        @endforeach
    @endif
    <meta name="theme-color" content="{{ $consulate?->primary_color ?? '#1d4ed8' }}">
    <meta name="robots" content="index,follow">

    {{-- Open Graph / Twitter --}}
    <meta property="og:type" content="website">
    <meta property="og:site_name" content="{{ $siteName }}">
    <meta property="og:title" content="{{ $fullTitle }}">
    <meta property="og:description" content="{{ $description }}">
    <meta property="og:url" content="{{ url()->current() }}">
    <meta property="og:locale" content="{{ $locale }}">
    @if ($ogImage) <meta property="og:image" content="{{ $ogImage }}"> @endif
    <meta name="twitter:card" content="summary">

    @if ($consulate)
        {{-- Thème du consulat : couleurs validées par Consulate::themeCss() --}}
        <style>{!! $consulate->themeCss() !!}</style>
    @endif
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="flex min-h-screen flex-col">
    <a href="#main" class="hp">{{ __('Aller au contenu') }}</a>

    {{-- Bandeau permanent : démo non officielle --}}
    <div class="sticky top-0 z-50 bg-amber-300 px-3 py-1.5 text-center text-xs font-semibold text-slate-900 sm:text-sm" role="note">
        ⚠ {{ __('Démo / Maquette non officielle') }} — {{ __('données fictives, ce site n’est pas un site officiel.') }}
    </div>

    <header class="border-b border-slate-200 bg-white">
        <div class="h-1.5" style="background:linear-gradient(90deg,var(--brand) 33.3%,var(--brand-2) 33.3% 66.6%,var(--brand-3) 66.6%)"></div>
        <div class="mx-auto flex max-w-6xl items-center justify-between gap-3 px-4 py-3">
            @if ($consulate)
                <a href="{{ route('home') }}" class="flex items-center gap-3">
                    <x-logo :consulate="$consulate" :size="44" />
                    <span class="leading-tight">
                        <span class="block text-sm font-bold text-brand sm:text-base">{{ $consulate->name }}</span>
                        <span class="block text-xs text-slate-500">{{ $consulate->host_country }}</span>
                    </span>
                </a>

                <nav class="hidden items-center gap-1 lg:flex" aria-label="{{ __('Navigation principale') }}">
                    @foreach ($nav as [$routeName, $label])
                        <a href="{{ route($routeName) }}"
                           class="rounded-md px-3 py-2 text-sm font-medium {{ request()->routeIs($routeName.'*') ? 'bg-brand/10 text-brand' : 'text-slate-700 hover:bg-slate-100' }}">{{ __($label) }}</a>
                    @endforeach
                    <a href="{{ route('appointment.create') }}" class="btn-accent ms-2">{{ __('Prendre rendez-vous') }}</a>
                </nav>

                <div class="flex items-center gap-2">
                    @if (count($consulate->availableLocales()) > 1)
                        <div class="flex overflow-hidden rounded-md border border-slate-300 text-xs font-semibold" aria-label="{{ __('Langue') }}">
                            @foreach ($consulate->availableLocales() as $l)
                                <a href="{{ request()->fullUrlWithQuery(['lang' => $l]) }}" hreflang="{{ $l }}" lang="{{ $l }}"
                                   class="px-2.5 py-1.5 {{ $l === $locale ? 'bg-brand text-white' : 'bg-white text-slate-700 hover:bg-slate-100' }}">{{ strtoupper($l) }}</a>
                            @endforeach
                        </div>
                    @endif

                    {{-- Menu mobile sans JavaScript --}}
                    <details class="relative lg:hidden">
                        <summary class="flex size-10 cursor-pointer list-none items-center justify-center rounded-md border border-slate-300" aria-label="{{ __('Menu') }}">
                            <x-icon name="menu" class="size-6" />
                        </summary>
                        <div class="absolute end-0 top-12 z-40 w-60 rounded-lg border border-slate-200 bg-white p-2 shadow-lg">
                            @foreach ($nav as [$routeName, $label])
                                <a href="{{ route($routeName) }}" class="block rounded-md px-3 py-2.5 text-sm font-medium hover:bg-slate-100">{{ __($label) }}</a>
                            @endforeach
                            <a href="{{ route('appointment.create') }}" class="btn-accent mt-2 w-full">{{ __('Prendre rendez-vous') }}</a>
                        </div>
                    </details>
                </div>
            @else
                <a href="{{ route('platform') }}" class="text-lg font-bold text-brand">{{ config('app.name') }}</a>
            @endif
        </div>
    </header>

    <main id="main" class="flex-1">
        @yield('content')
    </main>

    <footer class="mt-12 bg-slate-900 text-slate-300">
        <div class="mx-auto grid max-w-6xl gap-8 px-4 py-10 sm:grid-cols-2 lg:grid-cols-3">
            @if ($consulate)
                <div>
                    <div class="mb-3 flex items-center gap-3 text-white">
                        <x-logo :consulate="$consulate" :size="36" />
                        <span class="font-bold">{{ $consulate->name }}</span>
                    </div>
                    <p class="text-sm">{{ $consulate->address }}</p>
                </div>
                <div class="space-y-1 text-sm">
                    <h2 class="mb-2 font-semibold text-white">{{ __('Contact') }}</h2>
                    @if ($consulate->phone) <p><bdi dir="ltr">{{ $consulate->phone }}</bdi></p> @endif
                    @if ($consulate->email) <p><a class="hover:underline" href="mailto:{{ $consulate->email }}">{{ $consulate->email }}</a></p> @endif
                </div>
                <div class="text-sm">
                    <h2 class="mb-2 font-semibold text-white">{{ __('Informations') }}</h2>
                    <ul class="space-y-1">
                        @foreach ($legalPages as $p)
                            <li><a class="hover:underline" href="{{ route('pages.show', $p->slug) }}">{{ $p->title }}</a></li>
                        @endforeach
                        <li><a class="hover:underline" href="{{ route('platform') }}">{{ __('Autres consulats de la démo') }}</a></li>
                    </ul>
                </div>
            @else
                <p class="text-sm sm:col-span-3">{{ __('Plateforme de démonstration pour digitaliser des consulats africains.') }}</p>
            @endif
        </div>
        <div class="border-t border-slate-700 px-4 py-4 text-center text-xs text-slate-400">
            {{ __('Démo / Maquette non officielle') }} — {{ __('Aucun logo ni armoirie officiel n’est utilisé. Adresses, téléphones, frais et délais sont fictifs.') }}
        </div>
    </footer>

    @if ($consulate && $consulate->whatsappNumber())
        <a href="https://wa.me/{{ $consulate->whatsappNumber() }}?text={{ rawurlencode(__('Bonjour, j’ai une question concernant vos services consulaires.')) }}"
           target="_blank" rel="noopener noreferrer"
           class="fixed bottom-4 end-4 z-40 flex items-center gap-2 rounded-full bg-green-600 px-4 py-3 text-sm font-semibold text-white shadow-lg hover:bg-green-700"
           aria-label="WhatsApp">
            <x-icon name="chat" class="size-5" /> <span class="hidden sm:inline">WhatsApp</span>
        </a>
    @endif
</body>
</html>
