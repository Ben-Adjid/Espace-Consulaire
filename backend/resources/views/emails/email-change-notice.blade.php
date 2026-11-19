<!doctype html>
<html lang="{{ app()->getLocale() }}" dir="{{ $dir }}">
<body style="font-family:Arial,sans-serif; color:#0f172a; background:#f8fafc; margin:0; padding:24px;">
  <div style="max-width:560px; margin:0 auto; background:#fff; border-radius:12px; padding:24px;">
    <h2 style="margin:0 0 12px; color:#1e3a8a;">{{ __('Bonjour :name,', ['name' => $citizen->full_name]) }}</h2>
    <p>{!! __('Une demande de changement d’adresse email a été faite pour votre compte, vers <strong>:email</strong>.', ['email' => e($newEmail)]) !!}</p>
    <p>{{ __('Si vous êtes à l’origine de cette demande, aucune action n’est nécessaire : la confirmation se fait depuis la nouvelle adresse.') }}</p>
    <p>{!! __('<strong>Si ce n’est pas vous</strong>, changez votre mot de passe immédiatement et contactez le consulat.') !!}</p>
  </div>
</body>
</html>
