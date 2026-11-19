'use client';

import { useRouter } from 'next/navigation';
import { useState } from 'react';
import type { Lang } from '@/lib/types';

interface EntryConsulate { slug: string; name: string; countries: string; languages: Lang[] }

/**
 * Choix du consulat avant la connexion citoyen : le compte est rattaché au consulat du pays de résidence.
 * La connexion s'ouvre dans la langue choisie si le consulat la propose, sinon en français.
 */
export default function CitizenEntry({ consulates, lang, labels }: {
  consulates: EntryConsulate[];
  lang: Lang;
  labels: { consulate: string; placeholder: string; cta: string };
}) {
  const router = useRouter();
  const [slug, setSlug] = useState(consulates.length === 1 ? consulates[0].slug : '');

  return (
    <form
      onSubmit={(e) => {
        e.preventDefault();
        const target = consulates.find((c) => c.slug === slug);
        if (!target) return;
        const siteLang = target.languages.includes(lang) ? lang : 'fr';
        router.push(`/${target.slug}/${siteLang}/compte/connexion`);
      }}
      className="space-y-4"
    >
      <div>
        <label htmlFor="entry-consulate" className="mb-2 block text-sm font-semibold text-slate-700">{labels.consulate}</label>
        <select
          id="entry-consulate"
          required
          value={slug}
          onChange={(e) => setSlug(e.target.value)}
          className="w-full rounded-xl border border-slate-300 bg-white px-4 py-3 text-sm shadow-sm outline-none focus:border-[#123b36] focus:ring-2 focus:ring-[#123b36]/20"
        >
          <option value="" disabled>{labels.placeholder}</option>
          {consulates.map((c) => (
            <option key={c.slug} value={c.slug}>{c.name} — {c.countries}</option>
          ))}
        </select>
      </div>
      <button
        type="submit"
        disabled={!slug}
        className="inline-flex w-full items-center justify-center gap-2 rounded-xl bg-[#123b36] px-5 py-3 text-sm font-semibold text-white transition hover:bg-[#1b5148] disabled:cursor-not-allowed disabled:opacity-50"
      >
        {labels.cta}
      </button>
    </form>
  );
}
