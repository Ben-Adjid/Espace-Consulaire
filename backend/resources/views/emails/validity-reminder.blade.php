<!doctype html>
<html lang="{{ app()->getLocale() }}" dir="{{ $dir }}">
<head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"></head>
<body style="font-family: system-ui, Arial, sans-serif; color:#0f172a; background:#f8fafc; padding:24px;">
  <div style="max-width:560px; margin:0 auto; background:#fff; border-radius:12px; padding:24px; border:1px solid #e2e8f0;">
    <h1 style="font-size:20px; margin:0 0 16px;">{{ __('Bonjour :name,', ['name' => $citizen->full_name]) }}</h1>
    <p>{{ __('Une échéance approche pour votre démarche : :what, valable jusqu’au :date.', ['what' => $what, 'date' => $date]) }}</p>
    <p>{{ __('Pensez à renouveler ou à déposer le document à jour dans votre espace citoyen.') }}</p>
    <p><a href="{{ $url }}" style="color:#1d4ed8;">{{ __('Ouvrir mon espace') }}</a></p>
  </div>
</body>
</html>
