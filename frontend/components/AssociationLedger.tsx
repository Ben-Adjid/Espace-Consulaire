'use client';

import { useEffect, useState } from 'react';
import { ApiError } from '@/lib/api';
import { bff } from '@/lib/bff';
import type { Lang } from '@/lib/types';
import { t } from '@/lib/i18n';

interface Entry {
  id: number;
  kind: 'contribution' | 'expense';
  kind_label: string;
  amount: number;
  label: string;
  occurred_on: string;
  status: 'pending' | 'approved' | 'rejected';
  proposed_by: string | null;
  approved_by: string | null;
  can_approve: boolean;
}
interface Ledger {
  role: string | null;
  balance: number;
  currency: string;
  can_record_contribution: boolean;
  can_propose_expense: boolean;
  entries: Entry[];
}

const fmt = (n: number) => `${n.toLocaleString('fr-FR').replace(/ /g, ' ')} FCFA`;
const errorText = (lang: Lang, err: unknown) => (err instanceof ApiError ? err.message : t(lang, 'error'));

/** Registre financier interne : solde, écritures, cotisations (trésorier), dépenses proposées et approuvées (bureau). */
export default function AssociationLedger({ consulateSlug, lang }: { consulateSlug: string; lang: Lang }) {
  const [ledger, setLedger] = useState<Ledger | null>(null);
  const [error, setError] = useState<string | null>(null);
  const [message, setMessage] = useState<string | null>(null);
  const [busy, setBusy] = useState(false);
  const [form, setForm] = useState({ amount: '', label: '', occurred_on: new Date().toISOString().slice(0, 10) });
  // Le trésorier peut enregistrer une cotisation ET proposer une dépense : il choisit le mode.
  const [mode, setMode] = useState<'contribution' | 'expense'>('contribution');

  function load() {
    bff<{ data: Ledger }>(consulateSlug, 'citizen/association/ledger', { lang })
      .then((r) => setLedger(r.data))
      .catch((err) => setError(errorText(lang, err)));
  }
  useEffect(load, [consulateSlug, lang]);

  async function run(path: string, body: Record<string, unknown>, ok: string) {
    setError(null);
    setMessage(null);
    setBusy(true);
    try {
      await bff(consulateSlug, path, { method: 'POST', lang, body });
      setMessage(ok);
      setForm({ ...form, amount: '', label: '' });
      load();
    } catch (err) {
      setError(errorText(lang, err));
    } finally {
      setBusy(false);
    }
  }

  if (!ledger) {
    return error ? <p role="alert" className="rounded-lg bg-red-50 p-3 text-sm text-red-700">{error}</p> : <p className="text-slate-600">{t(lang, 'loading')}</p>;
  }

  const amount = Number(form.amount);
  const valid = Number.isInteger(amount) && amount > 0 && form.label.trim() !== '';

  return (
    <div className="space-y-6">
      {error && <p role="alert" className="rounded-lg bg-red-50 p-3 text-sm text-red-700">{error}</p>}
      {message && <p role="status" className="rounded-lg bg-green-50 p-3 text-sm text-green-800">{message}</p>}

      <div className="card flex flex-wrap items-center justify-between gap-3 p-5">
        <div>
          <p className="text-sm text-slate-500">{t(lang, 'ledger_balance')}</p>
          <p className="text-3xl font-extrabold text-brand" dir="ltr">{fmt(ledger.balance)}</p>
        </div>
        <p className="text-sm text-slate-600">
          {ledger.role ? `${t(lang, 'ledger_your_role')} : ${ledger.role}` : t(lang, 'ledger_not_member')}
        </p>
      </div>

      {(ledger.can_record_contribution || ledger.can_propose_expense) && (() => {
        const asContribution = ledger.can_record_contribution && (mode === 'contribution' || !ledger.can_propose_expense);
        return (
        <form className="card grid gap-3 p-5 sm:grid-cols-2" onSubmit={(e) => {
          e.preventDefault();
          if (asContribution) {
            run('citizen/association/ledger/contribution', { amount, label: form.label, occurred_on: form.occurred_on }, t(lang, 'ledger_saved'));
          } else {
            run('citizen/association/ledger/expense', { amount, label: form.label, occurred_on: form.occurred_on }, t(lang, 'ledger_proposed'));
          }
        }}>
          {ledger.can_record_contribution && ledger.can_propose_expense ? (
            <div className="flex flex-wrap gap-2 sm:col-span-2" role="tablist">
              <button type="button" role="tab" aria-selected={asContribution} onClick={() => setMode('contribution')}
                className={`rounded-lg px-3 py-1.5 text-sm font-semibold ${asContribution ? 'bg-brand text-white' : 'bg-slate-100 text-slate-700'}`}>
                {t(lang, 'ledger_record_contribution')}
              </button>
              <button type="button" role="tab" aria-selected={!asContribution} onClick={() => setMode('expense')}
                className={`rounded-lg px-3 py-1.5 text-sm font-semibold ${!asContribution ? 'bg-brand text-white' : 'bg-slate-100 text-slate-700'}`}>
                {t(lang, 'ledger_propose_expense')}
              </button>
            </div>
          ) : (
            <p className="font-semibold text-slate-900 sm:col-span-2">
              {asContribution ? t(lang, 'ledger_record_contribution') : t(lang, 'ledger_propose_expense')}
            </p>
          )}
          <div>
            <label htmlFor="ledger-amount" className="label">{t(lang, 'ledger_amount')}</label>
            <input id="ledger-amount" className="input" inputMode="numeric" value={form.amount} onChange={(e) => setForm({ ...form, amount: e.target.value.replace(/\D/g, '') })} dir="ltr" />
          </div>
          <div>
            <label htmlFor="ledger-date" className="label">{t(lang, 'ledger_date')}</label>
            <input id="ledger-date" type="date" className="input" value={form.occurred_on} onChange={(e) => setForm({ ...form, occurred_on: e.target.value })} />
          </div>
          <div className="sm:col-span-2">
            <label htmlFor="ledger-label" className="label">{t(lang, 'ledger_label')}</label>
            <input id="ledger-label" className="input" maxLength={190} value={form.label} onChange={(e) => setForm({ ...form, label: e.target.value })} />
          </div>
          <div className="sm:col-span-2 flex justify-end">
            <button type="submit" disabled={busy || !valid} className="btn-primary px-5 py-2.5">{asContribution ? t(lang, 'ledger_submit') : t(lang, 'ledger_propose')}</button>
          </div>
        </form>
        );
      })()}

      <section className="space-y-3">
        <h2 className="text-lg font-bold text-brand">{t(lang, 'ledger_entries')}</h2>
        {ledger.entries.length === 0 && <p className="text-slate-600">{t(lang, 'ledger_none')}</p>}
        <ul className="space-y-2">
          {ledger.entries.map((e) => (
            <li key={e.id} className="card flex flex-wrap items-center justify-between gap-3 p-4">
              <div className="min-w-0">
                <p className="font-semibold text-slate-900">{e.label}</p>
                <p className="text-xs text-slate-500">
                  {e.kind_label} · {e.occurred_on}
                  {e.proposed_by ? ` · ${t(lang, 'ledger_proposed_by')} ${e.proposed_by}` : ''}
                  {e.approved_by && e.status === 'approved' && e.approved_by !== e.proposed_by ?` · ${t(lang, 'ledger_approved_by')} ${e.approved_by}` : ''}
                </p>
              </div>
              <div className="flex items-center gap-3">
                <span className={`font-bold ${e.kind === 'expense' ? 'text-red-700' : 'text-green-800'}`} dir="ltr">
                  {e.kind === 'expense' ? '−' : '+'}{fmt(e.amount)}
                </span>
                {e.status === 'pending' && <span className="rounded-full bg-amber-100 px-2.5 py-0.5 text-xs font-bold text-amber-800">{t(lang, 'ledger_pending')}</span>}
                {e.can_approve && (
                  <button type="button" disabled={busy} onClick={() => run(`citizen/association/ledger/${e.id}/approve`, {}, t(lang, 'ledger_approved'))}
                    className="rounded-lg bg-brand px-3 py-1.5 text-sm font-semibold text-white">{t(lang, 'ledger_approve')}</button>
                )}
              </div>
            </li>
          ))}
        </ul>
      </section>
    </div>
  );
}
