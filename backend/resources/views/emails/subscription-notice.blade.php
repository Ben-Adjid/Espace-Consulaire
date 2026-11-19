<!doctype html>
<html lang="{{ app()->getLocale() }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
<head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"></head>
<body style="font-family: system-ui, Arial, sans-serif; color:#0f172a; background:#f8fafc; padding:24px;">
  <div style="max-width:560px; margin:0 auto; background:#fff; border-radius:12px; padding:24px; border:1px solid #e2e8f0;">
    <p style="font-size:13px; color:#64748b; margin:0 0 8px;">{{ $consulateName }}</p>
    <h1 style="font-size:20px; margin:0 0 16px;">{{ __('Bonjour,') }}</h1>
    @if ($stage === 'warning')
      <p>{!! __('L’abonnement annuel du consulat arrive à échéance le <strong>:date</strong>. Merci de prendre contact avec l’administration de la plateforme pour le renouveler.', ['date' => e($until)]) !!}</p>
    @elseif ($stage === 'grace')
      <p>{!! __('L’abonnement est échu depuis le <strong>:date</strong>. Le consulat reste en ligne pendant un délai de <strong>:days jours</strong>, après lequel il sera suspendu automatiquement.', ['date' => e($until), 'days' => (int) $graceDays]) !!}</p>
    @else
      <p>{!! __('Le consulat a été <strong>suspendu</strong> faute de renouvellement de l’abonnement (échéance : :date). Le site public n’accepte plus de nouveaux rendez-vous jusqu’au règlement.', ['date' => e($until)]) !!}</p>
    @endif
    <p>{{ __('Pour le renouvellement, contactez l’administration de la plateforme qui enregistre le paiement.') }}</p>
  </div>
</body>
</html>
