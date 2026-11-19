<!doctype html>
<html lang="{{ app()->getLocale() }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
<head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"></head>
<body style="font-family: system-ui, Arial, sans-serif; color:#0f172a; background:#f8fafc; padding:24px;">
  <div style="max-width:560px; margin:0 auto; background:#fff; border-radius:12px; padding:24px; border:1px solid #e2e8f0;">
    <p style="font-size:13px; color:#64748b; margin:0 0 8px;">{{ $consulateName }}</p>
    <h1 style="font-size:20px; margin:0 0 16px;">{{ __('Bonjour :name,', ['name' => $user->name]) }}</h1>
    <p>{{ __('Un compte d’administration a été créé pour vous sur la plateforme consulaire.') }}</p>
    <p>{{ __('Cliquez sur le bouton ci-dessous pour choisir votre mot de passe et activer votre compte. Ce lien est valable 60 minutes.') }}</p>
    <p style="margin:24px 0;">
      <a href="{{ $url }}" style="background:#1d4ed8; color:#fff; padding:12px 18px; border-radius:8px; text-decoration:none; font-weight:bold;">{{ __('Activer mon compte') }}</a>
    </p>
    <p style="font-size:13px;">{{ __('Si le lien a expiré, demandez un nouvel envoi à l’administrateur de la plateforme.') }}</p>
  </div>
</body>
</html>
