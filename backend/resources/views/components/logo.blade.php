@props(['consulate', 'size' => 44])
{{-- Logo : image téléversée, sinon logo NEUTRE généré (bandes aux couleurs du consulat + initiales). --}}
@if ($consulate->logoUrl())
    <img src="{{ $consulate->logoUrl() }}" alt="" width="{{ $size }}" height="{{ $size }}"
         class="shrink-0 rounded-lg bg-white object-contain" style="width:{{ $size }}px;height:{{ $size }}px">
@else
    <span aria-hidden="true" class="relative inline-flex shrink-0 items-center justify-center overflow-hidden rounded-lg"
          style="width:{{ $size }}px;height:{{ $size }}px;background:linear-gradient(90deg,var(--brand) 33.3%,var(--brand-2) 33.3% 66.6%,var(--brand-3) 66.6%)">
        <span class="flex items-center justify-center rounded-full bg-white font-bold text-slate-800 shadow"
              style="width:{{ round($size * .62) }}px;height:{{ round($size * .62) }}px;font-size:{{ round($size * .27) }}px">{{ $consulate->initials() }}</span>
    </span>
@endif
