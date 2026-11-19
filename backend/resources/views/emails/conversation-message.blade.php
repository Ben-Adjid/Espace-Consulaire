<!doctype html>
<html lang="{{ app()->getLocale() }}" dir="{{ $dir }}">
<head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"></head>
<body style="font-family: system-ui, Arial, sans-serif; color:#0f172a; background:#f8fafc; padding:24px;">
  <div style="max-width:560px; margin:0 auto; background:#fff; border-radius:12px; padding:24px; border:1px solid #e2e8f0;">
    <h1 style="font-size:20px; margin:0 0 16px;">{{ __('Bonjour :name,', ['name' => $citizen->full_name]) }}</h1>
    <p>{{ __('Le consulat vous a écrit au sujet de : :subject', ['subject' => $conversation->subject]) }}</p>
    <p>{{ __('Pour votre sécurité, le message se lit uniquement dans votre espace citoyen.') }}</p>
    <p><a href="{{ $url }}" style="color:#1d4ed8;">{{ __('Lire le message') }}</a></p>
  </div>
</body>
</html>
