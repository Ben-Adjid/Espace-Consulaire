'use client';

import Link from 'next/link';
import { useMemo, useState } from 'react';
import type { Consulate, Lang } from '@/lib/types';
import Logo from './Logo';
import { ArrowIcon } from './PlatformChrome';

const norm = (s: string) => s.normalize('NFD').replace(/[̀-ͯ]/g, '').toLowerCase();

export type ConsulateCard = Pick<Consulate, 'slug' | 'name' | 'represented_country' | 'host_country' | 'covered_countries' | 'theme' | 'logo_url' | 'initials' | 'languages'>;

export interface PickerLabels { label: string; placeholder: string; one: string; many: string; empty: string; covered: string }

/** Liste des consulats avec recherche par pays d'accueil, pays couvert ou nom ; ouvre le site dans la langue choisie si possible. */
export default function ConsulatePicker({ consulates, lang, labels }: { consulates: ConsulateCard[]; lang: Lang; labels: PickerLabels }) {
  const [q, setQ] = useState('');

  const shown = useMemo(() => {
    const needle = norm(q.trim());
    if (!needle) return consulates;
    return consulates.filter((c) =>
      [c.name, c.host_country, c.represented_country, ...(c.covered_countries ?? [])].some((v) => v && norm(v).includes(needle)),
    );
  }, [q, consulates]);

  return (
    <div>
      <div className="mb-6 max-w-xl">
        <label htmlFor="consulate-search" className="mb-2 block text-sm font-semibold text-slate-700">{labels.label}</label>
        <input
          id="consulate-search"
          type="search"
          value={q}
          onChange={(e) => setQ(e.target.value)}
          placeholder={labels.placeholder}
          className="w-full rounded-xl border border-slate-300 bg-white px-4 py-3 text-sm shadow-sm outline-none focus:border-[#123b36] focus:ring-2 focus:ring-[#123b36]/20"
        />
        <p className="mt-2 text-xs text-slate-500" aria-live="polite">
          {shown.length} {shown.length === 1 ? labels.one : labels.many}
        </p>
      </div>

      {shown.length > 0 ? (
        <div className="grid gap-5 md:grid-cols-2 xl:grid-cols-3">
          {shown.map((c) => (
            <Link
              key={c.slug}
              href={`/${c.slug}/${c.languages?.includes(lang) ? lang : 'fr'}`}
              className="group relative overflow-hidden rounded-2xl border border-slate-200/80 bg-white p-5 shadow-sm transition duration-200 hover:-translate-y-1 hover:border-slate-300 hover:shadow-xl hover:shadow-slate-900/[0.07] sm:p-6"
            >
              <div aria-hidden="true" className="absolute inset-x-0 top-0 h-1" style={{ background: `linear-gradient(90deg, ${c.theme.primary} 33.3%, ${c.theme.secondary} 33.3% 66.6%, ${c.theme.accent} 66.6%)` }} />
              <div className="flex items-start justify-between gap-4">
                <div className="flex min-w-0 items-center gap-4">
                  <span className="rounded-2xl bg-slate-50 p-1.5 ring-1 ring-slate-100"><Logo consulate={c} size={52} /></span>
                  <div className="min-w-0">
                    <h3 className="truncate text-base font-bold text-slate-900 sm:text-lg">{c.name}</h3>
                    <p className="mt-1 text-sm text-slate-500">{c.represented_country}</p>
                  </div>
                </div>
                <span className="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-slate-50 text-slate-500 transition group-hover:bg-[#123b36] group-hover:text-white"><ArrowIcon /></span>
              </div>
              <div className="mt-5 flex items-center justify-between border-t border-slate-100 pt-4">
                <span className="text-xs font-medium text-slate-500">{labels.covered}</span>
                <span className="text-sm font-semibold text-slate-700">{(c.covered_countries?.length ? c.covered_countries : [c.host_country]).join(', ')}</span>
              </div>
            </Link>
          ))}
        </div>
      ) : (
        <p className="rounded-2xl border border-slate-200 bg-white p-6 text-sm text-slate-600">{labels.empty}</p>
      )}
    </div>
  );
}
