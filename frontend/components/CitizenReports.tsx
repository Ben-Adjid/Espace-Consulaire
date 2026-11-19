'use client';

import { useEffect, useState } from 'react';
import { ApiError } from '@/lib/api';
import { bff } from '@/lib/bff';
import type { CitizenReportView, Lang } from '@/lib/types';
import { t } from '@/lib/i18n';

const CATEGORIES = ['reception', 'delay', 'information', 'website', 'integrity', 'other'] as const;

/**
 * Signalements du citoyen : envoi et suivi des réponses.
 * La catégorie « intégrité » part directement à l'administration centrale, jamais au consulat concerné.
 */
export default function CitizenReports({ consulateSlug, lang }: { consulateSlug: string; lang: Lang }) {
  const [items, setItems] = useState<CitizenReportView[] | null>(null);
  const [open, setOpen] = useState(false);
  const [form, setForm] = useState({ category: 'reception', subject: '', message: '' });
  const [notice, setNotice] = useState<string | null>(null);
  const [error, setError] = useState<string | null>(null);
  const [busy, setBusy] = useState(false);

  function load() {
    bff<{ data: CitizenReportView[] }>(consulateSlug, 'citizen/reports', { lang })
      .then((r) => setItems(r.data))
      .catch(() => setItems([]));
  }

  useEffect(load, [consulateSlug, lang]);

  async function submit(e: React.FormEvent) {
    e.preventDefault();
    setError(null);
    setBusy(true);
    try {
      const r = await bff<{ message: string }>(consulateSlug, 'citizen/reports', { method: 'POST', lang, body: form });
      setNotice(r.message);
      setForm({ category: 'reception', subject: '', message: '' });
      setOpen(false);
      load();
    } catch (err) {
      setError(err instanceof ApiError ? err.message : t(lang, 'error'));
    } finally {
      setBusy(false);
    }
  }

  return (
    <div id="signalement" className="space-y-6">
      {!open && (
        <div className="flex flex-wrap items-center justify-between gap-3">
          <p className="text-sm text-slate-600">{t(lang, 'report_intro')}</p>
          <button type="button" onClick={() => { setOpen(true); setNotice(null); }} className="btn-primary px-5 py-2.5">
            {t(lang, 'report_new')}
          </button>
        </div>
      )}

      {notice && <p role="status" className="rounded-lg bg-green-50 p-3 text-sm text-green-800">{notice}</p>}

      {open && (
        <form onSubmit={submit} className="card space-y-5 p-5 sm:p-6">
          <h3 className="text-base font-bold text-slate-900">{t(lang, 'report_new')}</h3>
          {error && <p role="alert" className="rounded-lg bg-red-50 p-3 text-sm text-red-700">{error}</p>}

          <div>
            <label className="label" htmlFor="rep-cat">{t(lang, 'report_category')} *</label>
            <select id="rep-cat" className="input" value={form.category} onChange={(e) => setForm({ ...form, category: e.target.value })}>
              {CATEGORIES.map((c) => (
                <option key={c} value={c}>{t(lang, `report_cat_${c}`)}</option>
              ))}
            </select>
            {form.category === 'integrity' && (
              <p className="mt-3 rounded-lg border border-amber-200 bg-amber-50 p-3 text-sm text-amber-800">{t(lang, 'report_integrity_hint')}</p>
            )}
          </div>

          <div>
            <label className="label" htmlFor="rep-subject">{t(lang, 'report_subject')} *</label>
            <input id="rep-subject" className="input" required minLength={4} maxLength={160} value={form.subject} onChange={(e) => setForm({ ...form, subject: e.target.value })} />
          </div>

          <div>
            <div className="flex items-baseline justify-between">
              <label className="label" htmlFor="rep-message">{t(lang, 'report_message')} *</label>
              <span className="text-xs text-slate-500" aria-live="polite">{form.message.length}/3000</span>
            </div>
            <textarea id="rep-message" className="input" rows={6} required minLength={10} maxLength={3000} value={form.message} onChange={(e) => setForm({ ...form, message: e.target.value })} />
          </div>

          <div className="flex flex-col-reverse gap-3 border-t border-slate-200 pt-4 sm:flex-row sm:justify-between">
            <button type="button" onClick={() => setOpen(false)} className="btn border border-slate-300 bg-white px-5 py-2.5 text-slate-700 hover:bg-slate-50">← {t(lang, 'btn_back')}</button>
            <button type="submit" disabled={busy} className="btn-primary px-6 py-2.5">{busy ? t(lang, 'saving') : t(lang, 'report_send')}</button>
          </div>
        </form>
      )}

      {items && items.length === 0 && !open && <p className="card p-5 text-sm text-slate-600">{t(lang, 'report_none')}</p>}
      {items && items.length > 0 && (
        <ul className="space-y-3">
          {items.map((r) => (
            <li key={r.id} className="card space-y-2 p-4">
              <div className="flex flex-wrap items-center justify-between gap-2">
                <p className="font-semibold text-slate-900">{r.subject}</p>
                <span className="rounded-full bg-slate-100 px-2.5 py-0.5 text-xs font-semibold text-slate-700">{r.status_label}</span>
              </div>
              <p className="text-xs text-slate-500">{r.category_label} · {new Date(r.created_at).toLocaleDateString(lang === 'ar' ? 'ar' : lang === 'en' ? 'en-GB' : 'fr-FR')}</p>
              {r.response && (
                <p className="rounded-lg bg-slate-50 p-3 text-sm text-slate-700"><span className="font-semibold">{t(lang, 'report_response')} : </span>{r.response}</p>
              )}
            </li>
          ))}
        </ul>
      )}
    </div>
  );
}
