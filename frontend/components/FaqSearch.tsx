'use client';

import { useMemo, useState } from 'react';
import type { Faq, Lang } from '@/lib/types';
import { t } from '@/lib/i18n';
import Prose from './Prose';

/** Recherche instantanée dans la FAQ, insensible aux accents et à la casse. */
export default function FaqSearch({ faqs, lang }: { faqs: Faq[]; lang: Lang }) {
  const [q, setQ] = useState('');

  const normalize = (s: string) => s.toLowerCase().normalize('NFD').replace(/[̀-ͯ]/g, '');

  const visible = useMemo(() => {
    const needle = normalize(q.trim());
    if (!needle) return faqs;
    return faqs.filter((f) => normalize(`${f.question} ${f.answer}`).includes(needle));
  }, [q, faqs]);

  return (
    <>
      <div className="relative mb-6">
        <input
          type="search"
          className="input ps-4"
          placeholder={t(lang, 'faq_search')}
          aria-label={t(lang, 'faq_search')}
          autoComplete="off"
          value={q}
          onChange={(e) => setQ(e.target.value)}
        />
      </div>

      <div className="space-y-3">
        {visible.map((f) => (
          <details key={f.id} className="card group" open={q.trim() !== '' || undefined}>
            <summary className="flex cursor-pointer list-none items-center justify-between gap-4 p-4 font-semibold text-slate-900">
              <span>{f.question}</span>
              <span aria-hidden="true" className="text-brand transition group-open:rotate-180">▾</span>
            </summary>
            <div className="border-t border-slate-100 p-4 text-slate-700"><Prose text={f.answer} /></div>
          </details>
        ))}
      </div>

      {visible.length === 0 && (
        <p className="mt-6 text-center text-slate-500">
          {t(lang, 'no_result')}
        </p>
      )}
    </>
  );
}
