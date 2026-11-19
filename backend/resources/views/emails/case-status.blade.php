<!doctype html>
<html lang="{{ app()->getLocale() }}" dir="{{ $dir }}">
<head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"></head>
<body style="font-family: system-ui, Arial, sans-serif; color:#0f172a; background:#f8fafc; padding:24px;">
  <div style="max-width:560px; margin:0 auto; background:#fff; border-radius:12px; padding:24px; border:1px solid #e2e8f0;">
    <p style="font-size:13px; color:#64748b; margin:0 0 8px;">{{ $consulateName }}</p>
    <h1 style="font-size:20px; margin:0 0 16px;">{{ __('Bonjour :name,', ['name' => $appointment->full_name]) }}</h1>
    <p>{!! __('Votre dossier <strong>:reference</strong> (:service) est passé à l’étape :', ['reference' => e($appointment->reference), 'service' => e($appointment->service?->name)]) !!}</p>
    <p style="font-size:18px; font-weight:bold; margin:16px 0;">{{ $appointment->case_status?->label() }}</p>
    @if ($appointment->case_note)
      <p><strong>{{ __('Message du consulat :') }}</strong> {{ $appointment->case_note }}</p>
    @endif
    @if ($appointment->case_status === \App\Enums\CaseStatus::Ready)
      <p>{{ __('Présentez-vous au consulat avec votre pièce d’identité et la référence du dossier pour le retrait.') }}</p>
    @endif
  </div>
</body>
</html>
