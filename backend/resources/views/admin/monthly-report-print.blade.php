<!doctype html>
<html lang="{{ app()->getLocale() }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ __('Rapport mensuel') }} — {{ $consulate->getTranslation('name', app()->getLocale()) }} — {{ $month->format('m/Y') }}</title>
    <style>
        body { font-family: system-ui, Arial, sans-serif; color: #0f172a; margin: 0; background: #f7f8f5; }
        .page { max-width: 820px; margin: 24px auto; background: #fff; padding: 32px; border-radius: 12px; box-shadow: 0 1px 3px rgba(0,0,0,.08); }
        h1 { font-size: 22px; margin: 0; } h2 { font-size: 16px; margin: 28px 0 8px; color: #1b5148; }
        .muted { color: #64748b; font-size: 13px; }
        table { width: 100%; border-collapse: collapse; font-size: 14px; }
        th, td { padding: 8px 10px; border-bottom: 1px solid #e2e8f0; text-align: start; }
        th { background: #f1f5f9; font-weight: 600; } td.n, th.n { text-align: end; font-variant-numeric: tabular-nums; }
        tr.total td { font-weight: 700; background: #f8fafc; }
        .kpis { display: grid; grid-template-columns: repeat(4, 1fr); gap: 10px; }
        .kpi { border: 1px solid #e2e8f0; border-radius: 10px; padding: 10px; } .kpi b { display: block; font-size: 20px; }
        .actions { text-align: end; margin-bottom: 12px; }
        button { background: #1b5148; color: #fff; border: 0; border-radius: 8px; padding: 10px 16px; font-weight: 600; cursor: pointer; }
        .sign { margin-top: 40px; display: flex; justify-content: space-between; gap: 24px; font-size: 13px; }
        @media print { body { background: #fff; } .page { box-shadow: none; margin: 0; max-width: none; } .actions { display: none; } }
    </style>
</head>
<body>
<div class="page">
    <div class="actions"><button type="button" onclick="window.print()">{{ __('Imprimer / enregistrer en PDF') }}</button></div>

    <h1>{{ __('Rapport mensuel') }} — {{ $month->locale(app()->getLocale())->isoFormat('MMMM YYYY') }}</h1>
    <p class="muted">{{ $consulate->getTranslation('name', app()->getLocale()) }} · {{ __('Édité le :date par :name', ['date' => now()->format('d/m/Y H:i'), 'name' => $user->name]) }}</p>

    <h2>{{ __('Rendez-vous par service') }}</h2>
    <table>
        <thead><tr><th>{{ __('Service') }}</th><th class="n">{{ __('Rendez-vous') }}</th><th class="n">{{ __('Présents') }}</th><th class="n">{{ __('Absences') }}</th><th class="n">{{ __('Annulés') }}</th></tr></thead>
        <tbody>
        @forelse ($report['rows'] as $row)
            <tr><td>{{ $row['service'] }}</td><td class="n">{{ $row['rendez_vous'] }}</td><td class="n">{{ $row['presents'] }}</td><td class="n">{{ $row['absences'] }}</td><td class="n">{{ $row['annules'] }}</td></tr>
        @empty
            <tr><td colspan="5" class="muted">{{ __('Aucun rendez-vous ce mois-ci.') }}</td></tr>
        @endforelse
        <tr class="total"><td>{{ __('Total') }}</td><td class="n">{{ $report['totals']['rendez_vous'] }}</td><td class="n">{{ $report['totals']['presents'] }}</td><td class="n">{{ $report['totals']['absences'] }}</td><td class="n">{{ $report['totals']['annules'] }}</td></tr>
        </tbody>
    </table>
    <p class="muted">{{ __('Total encaissé (hors annulés)') }} : {{ $report['totals']['encaissement'] }}</p>

    <h2>{{ __('Pré-demandes') }}</h2>
    <div class="kpis">
        <div class="kpi"><span class="muted">{{ __('Soumises') }}</span><b>{{ $cases['submitted'] }}</b></div>
        <div class="kpi"><span class="muted">{{ __('Validées') }}</span><b>{{ $cases['validated'] }}</b></div>
        <div class="kpi"><span class="muted">{{ __('Renvoyées pour complément') }}</span><b>{{ $cases['returned'] }}</b></div>
        <div class="kpi"><span class="muted">{{ __('Refusées') }}</span><b>{{ $cases['rejected'] }}</b></div>
        <div class="kpi"><span class="muted">{{ __('Délai moyen de décision') }}</span><b>{{ $cases['avg_days'] !== null ? __(':days j', ['days' => $cases['avg_days']]) : '—' }}</b></div>
        <div class="kpi"><span class="muted">{{ __('En retard aujourd’hui') }}</span><b>{{ $cases['overdue'] }}</b></div>
    </div>

    <div class="sign">
        <span>{{ __('Visa du consul') }} : ______________________</span>
        <span>{{ __('Date') }} : ____ / ____ / ________</span>
    </div>
</div>
</body>
</html>
