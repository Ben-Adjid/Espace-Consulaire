import Link from 'next/link';
import { notFound } from 'next/navigation';
import type { Consulate } from '@/lib/types';
import { api } from '@/lib/api';
import CitizenEntry from '@/components/CitizenEntry';
import HtmlAttributes from '@/components/HtmlAttributes';
import { PlatformFooter, PlatformHeader } from '@/components/PlatformChrome';
import { dirOf } from '@/lib/i18n';
import { CONNEXION, platformLang } from '@/lib/platformText';

export const instant = false;

type Params = { params: Promise<{ lang?: string[] }> };

export function generateStaticParams() {
  return [{ lang: [] }, { lang: ['en'] }, { lang: ['ar'] }];
}

export async function generateMetadata({ params }: Params) {
  const lang = platformLang((await params).lang) ?? 'fr';
  return { title: CONNEXION[lang].meta };
}

const ADMIN_URL = process.env.NEXT_PUBLIC_ADMIN_URL ?? 'http://127.0.0.1:8000/admin';

/**
 * Entrée « Se connecter » (FR / EN / AR) : deux espaces distincts, deux systèmes d'identification distincts.
 * Citoyen → espace citoyen de son consulat, dans la langue choisie ; agent ou consul → administration.
 */
export default async function ConnexionPage({ params }: Params) {
  const lang = platformLang((await params).lang);
  if (!lang) notFound();
  const c = CONNEXION[lang];
  const dir = dirOf(lang);

  let consulates: Consulate[] = [];
  let failed = false;
  try {
    consulates = (await api<{ data: Consulate[] }>('/consulates', { revalidate: 60 })).data;
  } catch {
    failed = true;
  }

  return (
    <main lang={lang} dir={dir} className="flex flex-1 flex-col bg-[#f7f8f5] text-slate-900">
      <HtmlAttributes lang={lang} dir={dir} />
      <PlatformHeader lang={lang} basePath="/connexion" />
      <section className="mx-auto w-full max-w-5xl px-5 py-14 sm:px-8 sm:py-20">
        <div className="mx-auto max-w-2xl text-center">
          <p className="text-xs font-bold uppercase tracking-[0.2em] text-[#39776a]">{c.eyebrow}</p>
          <h1 className="mt-2 text-3xl font-semibold tracking-tight sm:text-4xl">{c.title}</h1>
          <p className="mt-3 text-slate-600">{c.intro}</p>
        </div>

        <div className="mt-12 grid gap-6 md:grid-cols-2">
          <article id="citoyen" className="flex flex-col rounded-3xl border border-slate-200 bg-white p-7 shadow-sm">
            <span aria-hidden="true" className="flex h-12 w-12 items-center justify-center rounded-2xl bg-[#123b36]/10 text-[#123b36]">
              <svg viewBox="0 0 24 24" fill="none" className="h-6 w-6"><circle cx="12" cy="8" r="3.5" stroke="currentColor" strokeWidth="1.6" /><path d="M5 20c.8-3.5 3.6-5.5 7-5.5s6.2 2 7 5.5" stroke="currentColor" strokeWidth="1.6" strokeLinecap="round" /></svg>
            </span>
            <h2 className="mt-5 text-xl font-semibold">{c.citizenTitle}</h2>
            <p className="mt-2 text-sm leading-6 text-slate-600">{c.citizenText}</p>
            <div className="mt-6">
              {failed ? (
                <p role="status" className="rounded-xl bg-amber-50 p-3 text-sm text-amber-900">{c.unavailable}</p>
              ) : consulates.length === 0 ? (
                <p className="rounded-xl bg-slate-50 p-3 text-sm text-slate-600">{c.none}</p>
              ) : (
                <CitizenEntry
                  lang={lang}
                  labels={{ consulate: c.consulateLabel, placeholder: c.consulatePlaceholder, cta: c.continueCta }}
                  consulates={consulates.map((x) => ({
                    slug: x.slug, name: x.name, languages: x.languages,
                    countries: (x.covered_countries?.length ? x.covered_countries : [x.host_country]).join(', '),
                  }))}
                />
              )}
            </div>
          </article>

          <article id="agent" className="flex flex-col rounded-3xl border border-slate-200 bg-white p-7 shadow-sm">
            <span aria-hidden="true" className="flex h-12 w-12 items-center justify-center rounded-2xl bg-[#f3c969]/25 text-[#7a5a12]">
              <svg viewBox="0 0 24 24" fill="none" className="h-6 w-6"><path d="M4 20h16M6.5 20V8.5L12 5l5.5 3.5V20M9.5 11h5M9.5 14.5h5" stroke="currentColor" strokeWidth="1.6" strokeLinecap="round" strokeLinejoin="round" /></svg>
            </span>
            <h2 className="mt-5 text-xl font-semibold">{c.agentTitle}</h2>
            <p className="mt-2 text-sm leading-6 text-slate-600">{c.agentText}</p>
            <a href={`${ADMIN_URL}/login`} className="mt-auto inline-flex w-full items-center justify-center rounded-xl border border-[#123b36] px-5 py-3 text-sm font-semibold text-[#123b36] transition hover:bg-[#123b36] hover:text-white sm:mt-6">
              {c.agentCta}
            </a>
          </article>
        </div>

        <p className="mt-10 text-center text-sm text-slate-500">
          {c.noAccount} <Link href="/#consulats" className="font-semibold text-[#123b36] hover:underline">{c.homeLink}</Link>{c.noAccountEnd}
        </p>
      </section>
      <PlatformFooter lang={lang} />
    </main>
  );
}
