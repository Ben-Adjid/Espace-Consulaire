<!doctype html>
<html lang="fr">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Reçu d'abonnement {{ $payment->id }}</title>
  <style>
    body { font-family: system-ui, Arial, sans-serif; color: #0f172a; margin: 0; padding: 32px; }
    .sheet { max-width: 640px; margin: 0 auto; border: 1px solid #cbd5e1; border-radius: 10px; padding: 28px; }
    h1 { font-size: 20px; margin: 0 0 4px; }
    table { width: 100%; border-collapse: collapse; margin-top: 16px; font-size: 14px; }
    td { padding: 8px 4px; border-bottom: 1px solid #e2e8f0; }
    td:first-child { color: #64748b; width: 40%; }
    .foot { margin-top: 24px; font-size: 12px; color: #64748b; }
    @media print { .noprint { display: none; } body { padding: 0; } .sheet { border: 0; } }
  </style>
</head>
<body>
  <div class="sheet">
    <p class="noprint"><button onclick="window.print()">Imprimer</button></p>
    <p style="font-size:13px; color:#64748b; margin:0">Plateforme des consulats · Reçu d'abonnement</p>
    <h1>Reçu n° {{ str_pad((string) $payment->id, 6, '0', STR_PAD_LEFT) }}</h1>
    <table>
      <tr><td>Consulat</td><td>{{ $consulate->getTranslation('name', 'fr') }} ({{ $consulate->slug }})</td></tr>
      <tr><td>Montant reçu</td><td>{{ number_format((float) $payment->amount, 2, ',', ' ') }} {{ $payment->currency }}</td></tr>
      <tr><td>Reçu le</td><td>{{ $payment->paid_at->format('d/m/Y') }}</td></tr>
      <tr><td>Période couverte</td><td>du {{ $payment->period_start->format('d/m/Y') }} au {{ $payment->period_end->format('d/m/Y') }}</td></tr>
      <tr><td>Mode de paiement</td><td>{{ \App\Models\ConsulatePayment::METHODS[$payment->method] ?? $payment->method }}</td></tr>
      <tr><td>Référence</td><td>{{ $payment->reference ?: '—' }}</td></tr>
      <tr><td>Enregistré par</td><td>{{ $payment->recorder?->name ?? '—' }}</td></tr>
    </table>
    <p class="foot">Document généré par la plateforme.</p>
  </div>
</body>
</html>
