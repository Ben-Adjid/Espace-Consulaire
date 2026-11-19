<!doctype html>
<html lang="{{ app()->getLocale() }}" dir="{{ $dir }}">
<body style="font-family:Arial,sans-serif; color:#0f172a; background:#f8fafc; margin:0; padding:24px;">
  <div style="max-width:560px; margin:0 auto; background:#fff; border-radius:12px; padding:24px;">
    <h2 style="margin:0 0 12px; color:#1e3a8a;">{{ __('Bonjour :name,', ['name' => $citizen->full_name]) }}</h2>
    @if ($kind === 'appointed')
      <p>{!! __('Vous êtes désormais <strong>:role</strong> de l’association <strong>:association</strong>.', ['role' => e($roleLabel), 'association' => e($associationName)]) !!}</p>
      <p>{{ __('Votre rôle apparaît dans votre espace citoyen, rubrique « Mon profil ».') }}</p>
    @else
      @if ($mandateEnd)
        <p>{!! __('Votre mandat de <strong>:role</strong> au sein de l’association <strong>:association</strong> est terminé au :date.', ['role' => e($roleLabel), 'association' => e($associationName), 'date' => e(\Illuminate\Support\Carbon::parse($mandateEnd)->format('d/m/Y'))]) !!}</p>
      @else
        <p>{!! __('Votre mandat de <strong>:role</strong> au sein de l’association <strong>:association</strong> est terminé.', ['role' => e($roleLabel), 'association' => e($associationName)]) !!}</p>
      @endif
      <p>{{ __('Vous êtes à nouveau citoyen simple. Vos rendez-vous et votre espace citoyen ne sont pas modifiés.') }}</p>
    @endif
    <p><a href="{{ $spaceUrl }}" style="color:#1d4ed8;">{{ __('Ouvrir mon espace citoyen') }}</a></p>
    <p style="font-size:12px; color:#64748b; margin-top:24px;">{{ __('Si vous pensez que cette information est erronée, contactez le consulat.') }}</p>
  </div>
</body>
</html>
