@php
    $user = auth()->user();
    $consulate = $user?->consulate;
    $blocked = $user && ! $user->isSuperAdmin() && $consulate && ! $consulate->is_active;
    $suspended = $blocked && $consulate->suspended_for_nonpayment;
@endphp
<!doctype html>
<html lang="fr">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="robots" content="noindex">
  <title>{{ $suspended ? "Consulat suspendu" : ($blocked ? "Consulat désactivé" : "Accès refusé") }}</title>
  <style>
    :root { color-scheme: light dark; --ink:#0f172a; --muted:#475569; --card:#ffffff; --bg:#f1f5f9; --line:#e2e8f0; --warn:#b45309; --warn-bg:#fef3c7; }
    @media (prefers-color-scheme: dark) { :root:not([data-theme="light"]) { --ink:#e2e8f0; --muted:#94a3b8; --card:#0f172a; --bg:#020617; --line:#1e293b; --warn:#fbbf24; --warn-bg:#422006; } }
    :root[data-theme="dark"] { --ink:#e2e8f0; --muted:#94a3b8; --card:#0f172a; --bg:#020617; --line:#1e293b; --warn:#fbbf24; --warn-bg:#422006; }
    body { margin:0; min-height:100vh; display:grid; place-items:center; padding:16px; background:var(--bg); color:var(--ink); font-family: system-ui, -apple-system, "Segoe UI", Arial, sans-serif; }
    .card { max-width:480px; width:100%; background:var(--card); border:1px solid var(--line); border-radius:14px; padding:28px; box-shadow:0 1px 3px rgba(0,0,0,.06); }
    h1 { font-size:20px; margin:0 0 12px; }
    p { line-height:1.6; margin:0 0 12px; color:var(--muted); }
    .badge { display:inline-block; background:var(--warn-bg); color:var(--warn); font-weight:600; font-size:13px; padding:4px 10px; border-radius:999px; margin-bottom:14px; }
    a { color:inherit; }
  </style>
</head>
<body>
  <main class="card">
    @if ($suspended)
      <span class="badge">Suspendu pour non-paiement</span>
      <h1>Le consulat {{ $consulate->getTranslation('name', 'fr') }} est suspendu</h1>
      <p>L'abonnement annuel à la plateforme n'a pas été renouvelé. Le site public et l'espace de gestion sont fermés jusqu'au règlement.</p>
      <p>Pour renouveler, contactez l'administration de la plateforme. Dès le paiement enregistré, votre accès est rétabli automatiquement.</p>
    @elseif ($blocked)
      <span class="badge">Consulat désactivé</span>
      <h1>Le consulat {{ $consulate->getTranslation('name', 'fr') }} est désactivé</h1>
      <p>Son accès est fermé par l'administration de la plateforme. Pour toute question, contactez-la directement.</p>
    @else
      <h1>Accès refusé</h1>
      <p>Vous n'avez pas l'autorisation d'accéder à cette page.</p>
    @endif
  </main>
</body>
</html>
