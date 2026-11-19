<!doctype html>
<html lang="{{ app()->getLocale() }}" dir="{{ $dir }}">
<head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"></head>
<body style="font-family: system-ui, Arial, sans-serif; color:#0f172a; background:#f8fafc; padding:24px;">
  <div style="max-width:560px; margin:0 auto; background:#fff; border-radius:12px; padding:24px; border:1px solid #e2e8f0;">
    <p style="font-size:13px; color:#64748b; margin:0 0 8px;">{{ $consulateName }}</p>
    <h1 style="font-size:20px; margin:0 0 16px;">{{ __('Bonjour :name,', ['name' => $citizen->full_name]) }}</h1>
    @if ($kind === 'registered')
      <p>{{ __('Nous avons bien reçu votre demande de compte. Le consulat va vérifier votre résidence dans son pays de compétence.') }}</p>
      <p>{{ __('Vous recevrez un nouvel email dès que votre compte sera activé. Vous ne pourrez prendre rendez-vous qu’après cette validation.') }}</p>
    @elseif ($kind === 'active')
      <p>{{ __('Votre résidence a été vérifiée et votre compte est activé. Vous pouvez maintenant vous connecter et prendre rendez-vous.') }}</p>
    @else
      <p>{{ __('Votre demande de compte n’a pas été acceptée par le consulat.') }}</p>
      @if ($citizen->rejection_reason)
        <p><strong>{{ __('Motif :') }}</strong> {{ $citizen->rejection_reason }}</p>
      @endif
      <p>{{ __('Si vous pensez qu’il s’agit d’une erreur, contactez le consulat.') }}</p>
    @endif
  </div>
</body>
</html>
