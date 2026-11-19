'use client';

import { useEffect, useMemo, useState } from 'react';
import type { DocumentItem, Lang } from '@/lib/types';
import { t } from '@/lib/i18n';

type Age = 'adult' | 'minor';
type Kind = 'first' | 'renewal';

/**
 * Checklist des pièces, personnalisée selon la situation (majeur/mineur, première demande/renouvellement)
 * quand le service en définit. L'état coché est mémorisé dans le navigateur uniquement.
 */
export default function DocumentChecklist({ items, detailed, lang }: { items: string[]; detailed?: DocumentItem[]; lang: Lang }) {
  const all: DocumentItem[] = useMemo(
    () => (detailed && detailed.length ? detailed : items.map((label) => ({ label, tags: [] }))),
    [detailed, items],
  );
  const hasAge = all.some((i) => i.tags.includes('adult') || i.tags.includes('minor'));
  const hasKind = all.some((i) => i.tags.includes('first') || i.tags.includes('renewal'));

  const [age, setAge] = useState<Age>('adult');
  const [kind, setKind] = useState<Kind>('first');

  // Une pièce s'affiche si elle n'a pas d'étiquette dans un groupe, ou si l'étiquette correspond au choix.
  const visible = all.filter((i) => {
    const ageTags = i.tags.filter((tag) => tag === 'adult' || tag === 'minor');
    const kindTags = i.tags.filter((tag) => tag === 'first' || tag === 'renewal');
    return (ageTags.length === 0 || ageTags.includes(age)) && (kindTags.length === 0 || kindTags.includes(kind));
  });

  const storageKey = `checklist:${age}:${kind}:${visible.map((i) => i.label).join('|').slice(0, 80)}`;
  const [checked, setChecked] = useState<Record<string, boolean>>({});

  useEffect(() => {
    try {
      const saved = localStorage.getItem(storageKey);
      setChecked(saved ? JSON.parse(saved) : {});
    } catch {
      setChecked({});
    }
  }, [storageKey]);

  function toggle(label: string) {
    const next = { ...checked, [label]: !checked[label] };
    setChecked(next);
    try {
      localStorage.setItem(storageKey, JSON.stringify(next));
    } catch {
      // Stockage indisponible (navigation privée) : la checklist fonctionne sans mémoire.
    }
  }

  const done = visible.filter((i) => checked[i.label]).length;
  const pill = (active: boolean) =>
    `rounded-full border px-3 py-1.5 text-sm font-semibold ${active ? 'border-transparent bg-brand text-white' : 'border-slate-300 bg-white text-slate-700 hover:bg-slate-50'}`;

  return (
    <div className="space-y-3">
      {(hasAge || hasKind) && (
        <div className="space-y-2 rounded-lg border border-slate-200 bg-slate-50 p-3">
          <p className="text-sm font-semibold text-slate-700">{t(lang, 'docs_situation')}</p>
          {hasAge && (
            <div className="flex flex-wrap gap-2" role="radiogroup" aria-label={t(lang, 'docs_for_whom')}>
              <button type="button" role="radio" aria-checked={age === 'adult'} className={pill(age === 'adult')} onClick={() => setAge('adult')}>{t(lang, 'docs_adult')}</button>
              <button type="button" role="radio" aria-checked={age === 'minor'} className={pill(age === 'minor')} onClick={() => setAge('minor')}>{t(lang, 'docs_minor')}</button>
            </div>
          )}
          {hasKind && (
            <div className="flex flex-wrap gap-2" role="radiogroup" aria-label={t(lang, 'docs_request_type')}>
              <button type="button" role="radio" aria-checked={kind === 'first'} className={pill(kind === 'first')} onClick={() => setKind('first')}>{t(lang, 'docs_first')}</button>
              <button type="button" role="radio" aria-checked={kind === 'renewal'} className={pill(kind === 'renewal')} onClick={() => setKind('renewal')}>{t(lang, 'docs_renewal')}</button>
            </div>
          )}
        </div>
      )}

      <p className="text-sm font-semibold text-slate-600" aria-live="polite">{done} / {visible.length}</p>
      <ul className="card divide-y divide-slate-100">
        {visible.map((doc) => (
          <li key={doc.label}>
            <label className="flex cursor-pointer items-start gap-3 p-4" dir={lang === 'ar' ? 'rtl' : 'ltr'}>
              <input type="checkbox" className="mt-1 size-5 shrink-0" checked={checked[doc.label] ?? false} onChange={() => toggle(doc.label)} />
              <span className={checked[doc.label] ? 'text-slate-400 line-through' : 'text-slate-800'}>{doc.label}</span>
            </label>
          </li>
        ))}
      </ul>
    </div>
  );
}
