<!doctype html>
<html lang="{{ app()->getLocale() }}" dir="{{ $dir }}">
<head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"></head>
<body style="font-family: system-ui, Arial, sans-serif; color:#0f172a; background:#f8fafc; padding:24px;">
  <div style="max-width:560px; margin:0 auto; background:#fff; border-radius:12px; padding:24px; border:1px solid #e2e8f0;">
    <p style="font-size:13px; color:#64748b; margin:0 0 8px;">{{ $consulateName }}</p>
    <h1 style="font-size:20px; margin:0 0 16px;">{{ __('Bonjour :name,', ['name' => $appointment->full_name]) }}</h1>
    <p>{!! __('Le consulat sera <strong>fermé le :date</strong> (:reason).', ['date' => e($closure->date->format('d/m/Y')), 'reason' => e($closure->label)]) !!}</p>
    <p>{!! __('Votre rendez-vous <strong>:reference</strong> du même jour (:service) est donc à reporter.', ['reference' => e($appointment->reference), 'service' => e($appointment->service?->name)]) !!}</p>
    <p>{{ __('Connectez-vous à votre espace pour choisir un autre créneau, ou annulez-le en ligne pour libérer la place.') }}</p>
  </div>
</body>
</html>
