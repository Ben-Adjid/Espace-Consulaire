'use client';

import { useEffect, useState } from 'react';
import { ApiError } from '@/lib/api';
import { bff } from '@/lib/bff';
import type { Lang } from '@/lib/types';
import { t } from '@/lib/i18n';

interface Item { at: string | null; title: string; detail: string | null; service: string | null; kind: string }

const KIND_STYLE: Record<string, string> = {
  demarche: 'bg-slate-100 text-slate-700',
  'rendez-vous': 'bg-sky-100 text-sky-800',
  guichet: 'bg-indigo-100 text-indigo-800',
  suivi: 'bg-green-100 text-green-800',
  echeance: 'bg-amber-100 text-amber-900',
};

/** Chronologie unique de la démarche : dossiers, rendez-vous, passage au guichet, suivi, échéances. */
export default function CitizenTimeline({ consulateSlug, lang }: { consulateSlug: string; lang: Lang }) {
  const [items, setItems] = useState<Item[] | null>(null);
  const [error, setError] = useState<string | null>(null);

  useEffect(() => {
    bff<{ data: Item[] }>(consulateSlug, 'citizen/timeline', { lang })
      .then((r) => setItems(r.data))
      .catch((err) => setError(err instanceof ApiError ? err.message : t(lang, 'error')));
  }, [consulateSlug, lang]);

  if (error) return <p role="alert" className="rounded-lg bg-red-50 p-3 text-sm text-red-700">{error}</p>;
  if (!items) return <p className="text-slate-600">{t(lang, 'loading')}</p>;
  if (items.length === 0) return <p className="card p-5 text-slate-600">{t(lang, 'timeline_none')}</p>;

  return (
    <ol className="space-y-3 border-s-2 border-slate-200 ps-5">
      {items.map((it, i) => (
        <li key={i} className="relative">
          <span className={`inline-block rounded-full px-2.5 py-0.5 text-xs font-bold ${KIND_STYLE[it.kind] ?? KIND_STYLE.demarche}`}>{t(lang, `timeline_kind_${it.kind}`)}</span>
          <p className="mt-1 font-semibold text-slate-900">{it.title}{it.service ? ` — ${it.service}` : ''}</p>
          {it.detail && <p className="text-sm text-slate-600">{it.detail}</p>}
          {it.at && (
            <p className="text-xs text-slate-500">
              {new Date(it.at).toLocaleString(lang === 'ar' ? 'ar' : lang === 'en' ? 'en-GB' : 'fr-FR', { dateStyle: 'medium', timeStyle: it.kind === 'echeance' ? undefined : 'short' })}
            </p>
          )}
        </li>
      ))}
    </ol>
  );
}
