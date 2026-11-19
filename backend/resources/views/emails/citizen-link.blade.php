<!doctype html>
<html lang="{{ app()->getLocale() }}" dir="{{ $dir }}">
<head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"></head>
<body style="font-family: system-ui, Arial, sans-serif; color:#0f172a; background:#f8fafc; padding:24px;">
  <div style="max-width:560px; margin:0 auto; background:#fff; border-radius:12px; padding:24px; border:1px solid #e2e8f0;">
    <p style="font-size:13px; color:#64748b; margin:0 0 8px;">{{ $consulateName }}</p>
    <h1 style="font-size:20px; margin:0 0 16px;">{{ __('Bonjour :name,', ['name' => $citizen->full_name]) }}</h1>
    @if ($purpose === 'verify')
      <p>{{ __('Confirmez votre adresse email pour que le consulat puisse examiner votre demande de compte.') }}</p>
    @else
      <p>{{ __('Vous avez demandé à réinitialiser votre mot de passe. Ce lien est valable 60 minutes.') }}</p>
    @endif
    <p style="margin:24px 0;">
      <a href="{{ $url }}" style="background:#1d4ed8; color:#fff; padding:12px 18px; border-radius:8px; text-decoration:none; font-weight:bold;">
        {{ $purpose === 'verify' ? __('Confirmer mon email') : __('Choisir un nouveau mot de passe') }}
      </a>
    </p>
    <p style="font-size:12px; color:#64748b;">{{ __('Si vous n’êtes pas à l’origine de cette demande, ignorez cet email.') }}</p>
  </div>
</body>
</html>
