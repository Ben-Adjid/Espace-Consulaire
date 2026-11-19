'use client';

import Link from 'next/link';
import { useEffect, useState } from 'react';
import { ApiError } from '@/lib/api';
import { bff } from '@/lib/bff';
import type { Lang } from '@/lib/types';
import { t } from '@/lib/i18n';
import { useConfirm } from './ConfirmDialog';

interface Dependent { id: number; full_name: string; relation: string; relation_label: string; birth_date: string | null }
const RELATIONS = ['child', 'spouse', 'parent', 'mandate', 'other'] as const;

/** Espace famille : les personnes dont le citoyen s'occupe, et les rendez-vous pris pour elles. */
export default function FamilySpace({ consulateSlug, lang, base }: { consulateSlug: string; lang: Lang; base: string }) {
  const [items, setItems] = useState<Dependent[] | null>(null);
  const [form, setForm] = useState({ full_name: '', relation: 'child', birth_date: '' });
  const [error, setError] = useState<string | null>(null);
  const [busy, setBusy] = useState(false);
  const [dialog, confirm] = useConfirm(lang);

  // Un échec de chargement s'affiche comme une erreur, jamais comme une liste vide.
  function load() {
    bff<{ data: Dependent[] }>(consulateSlug, 'citizen/dependents', { lang })
      .then((r) => setItems(r.data))
      .catch((err) => setError(err instanceof ApiError ? err.message : t(lang, 'error')));
  }
  useEffect(load, [consulateSlug, lang]);

  async function add(e: React.FormEvent) {
    e.preventDefault();
    setError(null);
    setBusy(true);
    try {
      await bff(consulateSlug, 'citizen/dependents', {
        method: 'POST', lang,
        body: { full_name: form.full_name, relation: form.relation, birth_date: form.birth_date || null },
      });
      setForm({ full_name: '', relation: 'child', birth_date: '' });
      load();
    } catch (err) {
      setError(err instanceof ApiError ? err.message : t(lang, 'error'));
    } finally {
      setBusy(false);
    }
  }

  async function remove(d: Dependent) {
    const ok = await confirm({
      title: t(lang, 'family_remove_confirm'),
      subject: `${d.full_name} · ${t(lang, `relation_${d.relation}`)}`,
      confirmLabel: t(lang, 'pre_remove'),
      danger: true,
    });
    if (!ok) return;
    setError(null);
    try {
      await bff(consulateSlug, `citizen/dependents/${d.id}`, { method: 'DELETE', lang });
      load();
    } catch (err) {
      setError(err instanceof ApiError ? err.message : t(lang, 'remove_failed'));
    }
  }

  return (
    <div className="space-y-6">
      {dialog}
      <p className="text-sm text-slate-600">{t(lang, 'family_intro')}</p>

      {items && items.length === 0 && <p className="card p-5 text-sm text-slate-600">{t(lang, 'family_none')}</p>}
      {items && items.length > 0 && (
        <ul className="grid gap-3 sm:grid-cols-2">
          {items.map((d) => (
            <li key={d.id} className="card flex flex-col gap-4 p-4">
              <div className="flex items-center gap-3">
                <span aria-hidden="true" className="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-slate-100 font-bold text-slate-700">
                  {d.full_name.trim().charAt(0).toUpperCase()}
                </span>
                <div className="min-w-0">
                  <p className="truncate font-semibold text-slate-900">{d.full_name}</p>
                  <p className="text-xs text-slate-500">{t(lang, `relation_${d.relation}`)}{d.birth_date ? ` · ${d.birth_date}` : ''}</p>
                </div>
              </div>
              <div className="flex items-center justify-between gap-3 border-t border-slate-200 pt-3 text-sm">
                <Link href={`${base}/appointment?for=${encodeURIComponent(d.full_name)}&rel=${d.relation}`} className="font-semibold text-brand hover:underline">
                  {t(lang, 'family_book_for')} →
                </Link>
                <button type="button" onClick={() => remove(d)} className="font-semibold text-red-700 hover:underline">{t(lang, 'pre_remove')}</button>
              </div>
            </li>
          ))}
        </ul>
      )}

      <form onSubmit={add} className="card space-y-5 p-5 sm:p-6">
        <h3 className="text-base font-bold text-slate-900">{t(lang, 'family_add')}</h3>

        <div className="grid gap-5 sm:grid-cols-2">
          <div className="sm:col-span-2">
            <label className="label" htmlFor="fam-name">{t(lang, 'family_name')} *</label>
            <input id="fam-name" className="input" required minLength={2} maxLength={120} autoComplete="off" value={form.full_name} onChange={(e) => setForm({ ...form, full_name: e.target.value })} />
          </div>
          <div>
            <label className="label" htmlFor="fam-birth">{t(lang, 'family_birth')}</label>
            <input id="fam-birth" type="date" className="input" value={form.birth_date} onChange={(e) => setForm({ ...form, birth_date: e.target.value })} />
          </div>
        </div>

        <fieldset>
          <legend className="label mb-2">{t(lang, 'on_behalf_relation')} *</legend>
          <div className="flex flex-wrap gap-2">
            {RELATIONS.map((r) => (
              <label key={r} className="cursor-pointer">
                <input
                  type="radio"
                  name="fam-rel"
                  value={r}
                  checked={form.relation === r}
                  onChange={() => setForm({ ...form, relation: r })}
                  className="sr-only"
                />
                <span
                  className={`inline-block rounded-full border px-4 py-1.5 text-sm font-semibold transition ${
                    form.relation === r
                      ? 'border-brand bg-brand text-white'
                      : 'border-slate-300 bg-white text-slate-700 hover:bg-slate-50'
                  }`}
                >
                  {t(lang, `relation_${r}`)}
                </span>
              </label>
            ))}
          </div>
        </fieldset>

        {error && <p role="alert" className="rounded-lg bg-red-50 p-3 text-sm text-red-700">{error}</p>}

        <div className="flex justify-end border-t border-slate-200 pt-4">
          <button type="submit" disabled={busy} className="btn-primary px-6 py-2.5">{busy ? t(lang, 'saving') : t(lang, 'family_add')}</button>
        </div>
      </form>
    </div>
  );
}
