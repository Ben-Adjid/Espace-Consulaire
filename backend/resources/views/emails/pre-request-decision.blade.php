<!doctype html>
<html lang="{{ app()->getLocale() }}" dir="{{ $dir }}">
<head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"></head>
<body style="font-family: system-ui, Arial, sans-serif; color:#0f172a; background:#f8fafc; padding:24px;">
  <div style="max-width:560px; margin:0 auto; background:#fff; border-radius:12px; padding:24px; border:1px solid #e2e8f0;">
    <h1 style="font-size:20px; margin:0 0 16px;">{{ __('Bonjour :name,', ['name' => $citizen->full_name]) }}</h1>
    <p>{{ $preRequest->service ? __('Le consulat a examiné votre pré-demande (:service).', ['service' => $preRequest->service->name]) : __('Le consulat a examiné votre pré-demande.') }}</p>
    <p style="font-size:18px; font-weight:bold; margin:16px 0;">{{ $statusLabel }}</p>
    @if ($preRequest->staff_note)
      <p><strong>{{ __('Message du consulat :') }}</strong> {{ $preRequest->staff_note }}</p>
    @endif
    @php($refused = $preRequest->documents->where('status', 'rejected'))
    @if ($refused->isNotEmpty())
      <p><strong>{{ __('Pièces à remplacer :') }}</strong></p>
      <ul>
        @foreach ($refused as $doc)
          <li>{{ $doc->label }} — {{ $doc->review_note }}</li>
        @endforeach
      </ul>
    @endif
    @if ($preRequest->status === 'validated')
      <p>{{ __('Vous pouvez maintenant prendre rendez-vous depuis votre espace citoyen.') }}</p>
    @elseif ($preRequest->status === 'returned')
      <p>{{ __('Complétez votre pré-demande depuis votre espace citoyen, puis soumettez-la à nouveau.') }}</p>
    @endif
    <p><a href="{{ $url }}" style="color:#1d4ed8;">{{ __('Ouvrir ma pré-demande') }}</a></p>
  </div>
</body>
</html>
