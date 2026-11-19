<!doctype html>
<html lang="{{ app()->getLocale() }}" dir="{{ $dir }}">
<head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"></head>
<body style="font-family: system-ui, Arial, sans-serif; color:#0f172a; background:#f8fafc; padding:24px;">
  <div style="max-width:560px; margin:0 auto; background:#fff; border-radius:12px; padding:24px; border:1px solid #e2e8f0;">
    <h1 style="font-size:20px; margin:0 0 16px;">{{ __('Bonjour :name,', ['name' => $citizen->full_name]) }}</h1>
    <p>{{ __('Le vote de l’élection :year de :association est ouvert.', ['year' => $year, 'association' => $associationName]) }}</p>
    <p>{{ __('Il ferme le :date. Vous pouvez voter dans votre espace citoyen, avec votre code à 6 chiffres si demandé.', ['date' => $closesAt]) }}</p>
    <p><a href="{{ $url }}" style="color:#1d4ed8;">{{ __('Voter dans mon espace') }}</a></p>
    <p style="font-size:12px;color:#64748b;">{{ __('Votre vote est anonyme : le consulat ne voit que les totaux.') }}</p>
  </div>
</body>
</html>
