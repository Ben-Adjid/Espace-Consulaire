<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" dir="{{ $dir }}">
<body style="margin:0;background:#f1f5f9;font-family:Arial,Helvetica,sans-serif;color:#0f172a;">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0"><tr><td align="center" style="padding:24px 12px;">
<table role="presentation" width="560" cellpadding="0" cellspacing="0" style="max-width:560px;background:#fff;border-radius:10px;overflow:hidden;">
    <tr><td style="background:{{ $consulate->primary_color }};color:#fff;padding:20px 24px;font-size:18px;font-weight:bold;">{{ $consulate->name }}</td></tr>
    <tr><td style="background:#fef3c7;color:#92400e;padding:8px 24px;font-size:12px;">{{ __('Démo / Maquette non officielle') }}</td></tr>
    <tr><td style="padding:24px;">
        <p>{{ __('Bonjour') }} {{ $appointment->full_name }},</p>
        <p>{{ __('Nous avons bien enregistré votre demande de rendez-vous. Elle sera confirmée par le consulat.') }}</p>
        <table role="presentation" width="100%" cellpadding="8" cellspacing="0" style="background:#f8fafc;border-radius:8px;">
            <tr><td><strong>{{ __('Référence') }}</strong></td><td style="font-family:monospace;font-size:16px;">{{ $appointment->reference }}</td></tr>
            <tr><td><strong>{{ __('Service') }}</strong></td><td>{{ $service->name }}</td></tr>
            <tr><td><strong>{{ __('Date') }}</strong></td><td>{{ $appointment->appointment_date->locale(app()->getLocale())->isoFormat('dddd D MMMM YYYY') }}</td></tr>
            <tr><td><strong>{{ __('Heure') }}</strong></td><td>{{ substr($appointment->start_time, 0, 5) }} – {{ substr($appointment->end_time, 0, 5) }}</td></tr>
            <tr><td><strong>{{ __('Adresse') }}</strong></td><td>{{ $consulate->address }}</td></tr>
        </table>
        @if ($manageUrl)
            <p style="margin:20px 0 0;"><a href="{{ $manageUrl }}" style="background:{{ $consulate->primary_color }};color:#fff;padding:10px 16px;border-radius:8px;text-decoration:none;font-weight:bold;">{{ __('Voir ou annuler mon rendez-vous') }}</a></p>
            <p style="font-size:12px;color:#64748b;">{{ __('Ce lien est personnel : ne le transférez à personne.') }}</p>
        @endif
        <p style="font-size:12px;color:#64748b;">{{ __('Démo non officielle : aucun rendez-vous réel n’est enregistré.') }}</p>
    </td></tr>
</table>
</td></tr></table>
</body>
</html>
