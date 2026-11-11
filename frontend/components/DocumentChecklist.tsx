'use client';

import { useEffect, useState } from 'react';
import type { Lang } from '@/lib/types';

/** Checklist des documents : l'état coché est mémorisé dans le navigateur uniquement (aucune donnée envoyée). */
export default function DocumentChecklist({ items, lang }: { items: string[]; lang: Lang }) {
  const storageKey = `checklist:${items.join('|').slice(0, 80)}`;
  const [checked, setChecked] = useState<boolean[]>(() => items.map(() => false));

  useEffect(() => {
    try {
      const saved = localStorage.getItem(storageKey);
      if (saved) setChecked(JSON.parse(saved));
    } catch {
      // Stockage indisponible (navigation privée) : la checklist fonctionne sans mémoire.
    }
  }, [storageKey]);

  function toggle(i: number) {
    const next = checked.map((v, idx) => (idx === i ? !v : v));
    setChecked(next);
    try {
      localStorage.setItem(storageKey, JSON.stringify(next));
    } catch {
      // ignore
    }
  }

  const done = checked.filter(Boolean).length;

  return (
    <div>
      <p className="mb-2 text-sm font-semibold text-slate-600" aria-live="polite">{done} / {items.length}</p>
      <ul className="card divide-y divide-slate-100">
        {items.map((doc, i) => (
          <li key={i}>
            <label className="flex cursor-pointer items-start gap-3 p-4" dir={lang === 'ar' ? 'rtl' : 'ltr'}>
              <input type="checkbox" className="mt-1 size-5 shrink-0" checked={checked[i] ?? false} onChange={() => toggle(i)} />
              <span className={checked[i] ? 'text-slate-400 line-through' : 'text-slate-800'}>{doc}</span>
            </label>
          </li>
        ))}
      </ul>
    </div>
  );
}
