'use client';

import Link from 'next/link';
import { useEffect, useRef, useState } from 'react';
import { api } from '@/lib/api';
import type { Lang, SearchResult } from '@/lib/types';
import { t } from '@/lib/i18n';

/** Recherche instantanée dans les services, actualités et FAQ du consulat (API, avec anti-rebond). */
export default function SearchBox({ consulateSlug, lang, base }: { consulateSlug: string; lang: Lang; base: string }) {
  const [q, setQ] = useState('');
  const [results, setResults] = useState<SearchResult[]>([]);
  const [open, setOpen] = useState(false);
  const [busy, setBusy] = useState(false);
  const timer = useRef<ReturnType<typeof setTimeout> | null>(null);

  useEffect(() => {
    if (timer.current) clearTimeout(timer.current);
    if (q.trim().length < 2) {
      setResults([]);
      return;
    }
    timer.current = setTimeout(async () => {
      setBusy(true);
      try {
        const res = await api<{ data: SearchResult[] }>(`/${consulateSlug}/search`, { lang, query: { q: q.trim() }, noStore: true });
        setResults(res.data);
      } catch {
        setResults([]);
      } finally {
        setBusy(false);
      }
    }, 250);
    return () => {
      if (timer.current) clearTimeout(timer.current);
    };
  }, [q, consulateSlug, lang]);

  const hrefFor = (r: SearchResult) => {
    if (r.type === 'service' && r.slug) return `${base}/services/${r.slug}`;
    if (r.type === 'news' && r.slug) return `${base}/news/${r.slug}`;
    return `${base}/faq`;
  };

  return (
    <div className="relative">
      <label className="sr-only" htmlFor="site-search">{t(lang, 'search_title')}</label>
      <input
        id="site-search"
        type="search"
        value={q}
        onChange={(e) => {
          setQ(e.target.value);
          setOpen(true);
        }}
        onFocus={() => setOpen(true)}
        onBlur={() => setTimeout(() => setOpen(false), 150)}
        placeholder={t(lang, 'search')}
        autoComplete="off"
        className="input w-32 py-1.5 sm:w-44"
      />
      {open && q.trim().length >= 2 && (
        <div className="absolute end-0 top-10 z-50 w-72 rounded-lg border border-slate-200 bg-white p-2 shadow-xl" role="listbox">
          {busy && <p className="p-2 text-sm text-slate-500">{t(lang, 'loading')}</p>}
          {!busy && results.length === 0 && <p className="p-2 text-sm text-slate-500">{t(lang, 'no_result')}</p>}
          {results.map((r, i) => (
            <Link key={`${r.type}-${i}`} href={hrefFor(r)} className="block rounded-md p-2 hover:bg-slate-100">
              <span className="block text-xs font-semibold uppercase text-slate-500">{r.type}</span>
              <span className="block text-sm font-medium text-slate-900">{r.title}</span>
            </Link>
          ))}
        </div>
      )}
    </div>
  );
}
