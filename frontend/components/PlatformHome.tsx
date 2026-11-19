import Link from 'next/link';
import type { Consulate, Lang } from '@/lib/types';
import { api } from '@/lib/api';
import { dirOf } from '@/lib/i18n';
import { HOME, platformPath } from '@/lib/platformText';
import ConsulatePicker from './ConsulatePicker';
import HtmlAttributes from './HtmlAttributes';
import { ArrowIcon, PlatformFooter, PlatformHeader } from './PlatformChrome';

/** Accueil de la plateforme dans une langue (/, /en, /ar). */
export default async function PlatformHome({ lang }: { lang: Lang }) {
  const h = HOME[lang];
  const dir = dirOf(lang);
  let consulates: Consulate[] = [];
  let failed = false;
  try {
    consulates = (await api<{ data: Consulate[] }>('/consulates', { revalidate: 300 })).data;
  } catch {
    failed = true;
  }

  return (
    <main lang={lang} dir={dir} className="flex flex-1 flex-col bg-[#f7f8f5] text-slate-900">
      <HtmlAttributes lang={lang} dir={dir} />
      <PlatformHeader lang={lang} basePath="/" />

      <section className="relative isolate overflow-hidden bg-[#123b36] text-white">
        <div aria-hidden="true" className="absolute -end-36 -top-56 -z-10 h-[34rem] w-[34rem] rounded-full border border-white/10 sm:end-[-5rem] sm:top-[-18rem]">
          <div className="absolute inset-10 rounded-full border border-white/10" />
          <div className="absolute inset-24 rounded-full border border-white/10" />
          <div className="absolute inset-40 rounded-full border border-white/10" />
        </div>
        <div className="mx-auto grid max-w-7xl items-center gap-12 px-5 py-16 sm:px-8 sm:py-20 lg:grid-cols-[1.15fr_0.85fr] lg:gap-16 lg:py-24">
          <div className="max-w-2xl">
            <span className="inline-flex items-center gap-2 rounded-full border border-white/20 bg-white/10 px-3.5 py-1.5 text-xs font-semibold tracking-wide text-emerald-50 sm:text-sm">
              <span className="h-2 w-2 rounded-full bg-[#f3c969]" />
              {h.badge}
            </span>
            <h1 className="mt-6 text-4xl font-semibold leading-[1.15] tracking-tight sm:text-5xl lg:text-6xl">
              {h.heroTitle}
              <span className="mt-1 block font-serif font-normal italic text-[#f3c969]">{h.heroAccent}</span>
            </h1>
            <p className="mt-6 max-w-xl text-base leading-7 text-emerald-50/80 sm:text-lg sm:leading-8">{h.heroText}</p>
            <div className="mt-8 flex flex-wrap gap-3">
              <a href="#consulats" className="inline-flex items-center gap-3 rounded-xl bg-[#f3c969] px-5 py-3.5 text-sm font-bold text-[#183b32] shadow-lg shadow-black/10 transition hover:-translate-y-0.5 hover:bg-[#ffda7c]">
                {h.findCta} <ArrowIcon />
              </a>
              <a href="#fonctionnement" className="inline-flex items-center gap-2 rounded-xl border border-white/30 px-5 py-3.5 text-sm font-semibold text-white transition hover:bg-white/10">{h.howCta}</a>
            </div>
          </div>
          <div className="relative mx-auto w-full max-w-md lg:ms-auto">
            <div aria-hidden="true" className="absolute -start-7 -top-7 h-24 w-24 rounded-full bg-[#f3c969]/15 blur-2xl" />
            <div className="relative overflow-hidden rounded-[1.75rem] border border-white/15 bg-white/[0.08] p-5 shadow-2xl shadow-black/20 backdrop-blur-sm sm:p-7">
              <p className="text-xs font-medium uppercase tracking-[0.18em] text-emerald-50/60">{h.journey}</p>
              <ol className="mt-4 space-y-3">
                {h.steps.slice(0, 3).map((s) => (
                  <li key={s.n} className="flex items-center gap-4 rounded-2xl bg-white/[0.07] p-4">
                    <span className="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-white/10 text-xs font-bold text-[#f3c969]">{s.n}</span>
                    <span>
                      <span className="block text-sm font-semibold">{s.title}</span>
                      <span className="mt-0.5 block text-xs text-emerald-50/60">{s.text}</span>
                    </span>
                  </li>
                ))}
              </ol>
              <p className="mt-4 border-t border-white/15 pt-4 text-xs leading-5 text-emerald-50/55">{h.journeyNote}</p>
            </div>
          </div>
        </div>
      </section>

      <section id="consulats" className="mx-auto w-full max-w-7xl scroll-mt-20 px-5 py-16 sm:px-8 sm:py-20">
        <div className="mb-8">
          <p className="text-xs font-bold uppercase tracking-[0.2em] text-[#39776a]">{h.startEyebrow}</p>
          <h2 className="mt-2 text-3xl font-semibold tracking-tight text-slate-900 sm:text-4xl">{h.chooseTitle}</h2>
          <p className="mt-3 max-w-2xl text-sm leading-6 text-slate-600 sm:text-base">{h.chooseText}</p>
        </div>
        {failed ? (
          <div role="status" className="rounded-2xl border border-amber-200 bg-amber-50 px-6 py-8 text-center text-sm text-amber-900">{h.loadError}</div>
        ) : consulates.length === 0 ? (
          <p className="rounded-2xl border border-slate-200 bg-white p-6 text-sm text-slate-600">{h.noConsulate}</p>
        ) : (
          <ConsulatePicker
            lang={lang}
            labels={h.picker}
            // Seuls les champs affichés partent vers le navigateur (pas de messages, coordonnées ni contacts).
            consulates={consulates.map((c) => ({
              slug: c.slug, name: c.name, represented_country: c.represented_country, host_country: c.host_country,
              covered_countries: c.covered_countries, theme: c.theme, logo_url: c.logo_url, initials: c.initials, languages: c.languages,
            }))}
          />
        )}
      </section>

      <section id="fonctionnement" className="scroll-mt-20 border-y border-slate-200/70 bg-white">
        <div className="mx-auto max-w-7xl px-5 py-16 sm:px-8 sm:py-20">
          <p className="text-xs font-bold uppercase tracking-[0.2em] text-[#39776a]">{h.howEyebrow}</p>
          <h2 className="mt-2 text-3xl font-semibold tracking-tight text-slate-900">{h.howTitle}</h2>
          <ol className="mt-10 grid gap-6 sm:grid-cols-2 lg:grid-cols-4">
            {h.steps.map((s) => (
              <li key={s.n} className="relative rounded-2xl border border-slate-200 bg-[#f7f8f5] p-6">
                <span className="text-3xl font-semibold text-[#39776a]/40">{s.n}</span>
                <h3 className="mt-3 font-semibold text-slate-900">{s.title}</h3>
                <p className="mt-2 text-sm leading-6 text-slate-600">{s.text}</p>
              </li>
            ))}
          </ol>
        </div>
      </section>

      <section className="mx-auto w-full max-w-7xl px-5 py-16 sm:px-8 sm:py-20">
        <p className="text-xs font-bold uppercase tracking-[0.2em] text-[#39776a]">{h.servicesEyebrow}</p>
        <h2 className="mt-2 text-3xl font-semibold tracking-tight text-slate-900">{h.servicesTitle}</h2>
        <div className="mt-10 grid gap-5 sm:grid-cols-2">
          {h.features.map((f) => (
            <div key={f.title} className="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
              <span aria-hidden="true" className="flex h-10 w-10 items-center justify-center rounded-xl bg-[#123b36]/10 text-[#123b36]">
                <svg viewBox="0 0 20 20" fill="none" className="h-5 w-5"><path d="m5 10 3.2 3.2L15.5 6" stroke="currentColor" strokeWidth="1.8" strokeLinecap="round" strokeLinejoin="round" /></svg>
              </span>
              <h3 className="mt-4 font-semibold text-slate-900">{f.title}</h3>
              <p className="mt-2 text-sm leading-6 text-slate-600">{f.text}</p>
            </div>
          ))}
        </div>
      </section>

      <section className="mx-auto w-full max-w-7xl px-5 pb-16 sm:px-8 sm:pb-20">
        <div className="grid gap-6 rounded-3xl bg-[#123b36] p-8 text-white sm:p-10 lg:grid-cols-[1.4fr_1fr] lg:items-center">
          <div>
            <h2 className="text-2xl font-semibold">{h.goodToKnow}</h2>
            <ul className="mt-4 space-y-2 text-sm leading-6 text-emerald-50/85">
              {h.tips.map((tip) => <li key={tip} className="flex gap-2"><span aria-hidden="true">•</span>{tip}</li>)}
            </ul>
          </div>
          <div className="flex flex-col gap-3 sm:flex-row lg:flex-col lg:items-end">
            <a href="#consulats" className="inline-flex items-center justify-center gap-2 rounded-xl bg-[#f3c969] px-5 py-3 text-sm font-bold text-[#183b32] hover:bg-[#ffda7c]">{h.findCta} <ArrowIcon /></a>
            <Link href={platformPath('/a-propos', lang)} className="inline-flex items-center justify-center rounded-xl border border-white/30 px-5 py-3 text-sm font-semibold hover:bg-white/10">{h.aboutCta}</Link>
          </div>
        </div>
      </section>

      <PlatformFooter lang={lang} />
    </main>
  );
}
