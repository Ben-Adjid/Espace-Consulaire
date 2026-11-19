import { notFound } from 'next/navigation';
import HtmlAttributes from '@/components/HtmlAttributes';
import { PlatformFooter, PlatformHeader } from '@/components/PlatformChrome';
import { dirOf } from '@/lib/i18n';
import { CREDITS, platformLang } from '@/lib/platformText';

type Params = { params: Promise<{ lang?: string[] }> };

export function generateStaticParams() {
  return [{ lang: [] }, { lang: ['en'] }, { lang: ['ar'] }];
}

export async function generateMetadata({ params }: Params) {
  const lang = platformLang((await params).lang) ?? 'fr';
  return { title: CREDITS[lang].meta };
}

const ICONS: Record<string, string> = {
  code: 'm8 8-4 4 4 4M16 8l4 4-4 4M13.5 5l-3 14',
  layers: 'm12 3 9 5-9 5-9-5 9-5Zm-9 9 9 5 9-5M3 16l9 5 9-5',
  chart: 'M4 20V10M10 20V4M16 20v-7M22 20H2',
  network: 'M12 3v4M12 17v4M5 12H3M21 12h-2M12 7a5 5 0 1 0 0 10 5 5 0 0 0 0-10ZM5.6 5.6l1.4 1.4M17 17l1.4 1.4M5.6 18.4 7 17M17 7l1.4-1.4',
  compass: 'M12 21a9 9 0 1 0 0-18 9 9 0 0 0 0 18Zm3.5-12.5-2 5-5 2 2-5 5-2Z',
  book: 'M4 5a2 2 0 0 1 2-2h13v16H6a2 2 0 0 0-2 2V5Zm0 16a2 2 0 0 1 2-2h13M9 7h6',
};

function Icon({ name }: { name: string }) {
  return (
    <span aria-hidden="true" className="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-[#123b36]/[0.08] text-[#123b36]">
      <svg viewBox="0 0 24 24" fill="none" className="h-5 w-5"><path d={ICONS[name]} stroke="currentColor" strokeWidth="1.6" strokeLinecap="round" strokeLinejoin="round" /></svg>
    </span>
  );
}

const LAST_NAME = 'Aboubakar Assidick';
const FIRST_NAME = 'Mahamat Adjid Oumar';

/** Crédits (FR / EN / AR) : profil du concepteur, domaines d'intervention et contribution à la plateforme. */
export default async function CreditsPage({ params }: Params) {
  const lang = platformLang((await params).lang);
  if (!lang) notFound();
  const c = CREDITS[lang];
  const dir = dirOf(lang);

  return (
    <main lang={lang} dir={dir} className="flex flex-1 flex-col bg-[#f7f8f5] text-slate-900">
      <HtmlAttributes lang={lang} dir={dir} />
      <PlatformHeader lang={lang} basePath="/credits" />

      <section className="relative isolate overflow-hidden bg-[#123b36] text-white">
        <div aria-hidden="true" className="absolute inset-x-0 top-0 h-1.5 bg-[linear-gradient(90deg,#002664_33.3%,#FECB00_33.3%_66.6%,#C60C30_66.6%)]" />
        <div aria-hidden="true" className="absolute -end-24 -top-24 -z-10 h-80 w-80 rounded-full border border-white/10" />
        <div className="mx-auto grid max-w-6xl items-center gap-10 px-5 py-16 sm:px-8 sm:py-20 md:grid-cols-[auto_1fr]">
          <span aria-hidden="true" dir="ltr" className="flex h-28 w-28 items-center justify-center rounded-3xl bg-[#f3c969] text-4xl font-bold text-[#123b36] shadow-xl sm:h-32 sm:w-32">AA</span>
          <div>
            <p className="text-xs font-bold uppercase tracking-[0.2em] text-[#f3c969]">{c.eyebrow}</p>
            <h1 className="mt-3 text-4xl font-semibold leading-tight tracking-tight sm:text-5xl" dir="ltr">{LAST_NAME}</h1>
            <p className="mt-1 text-2xl font-light text-emerald-50/90" dir="ltr">{FIRST_NAME}</p>
            <dl className="mt-6 grid max-w-xl gap-x-8 gap-y-2 text-sm sm:grid-cols-[auto_1fr]">
              <dt className="text-emerald-50/60">{c.lastNameLabel}</dt><dd className="font-semibold">{LAST_NAME}</dd>
              <dt className="text-emerald-50/60">{c.firstNameLabel}</dt><dd className="font-semibold">{FIRST_NAME}</dd>
              <dt className="text-emerald-50/60">{c.profileLabel}</dt><dd className="font-semibold">{c.profile}</dd>
            </dl>
          </div>
        </div>
      </section>

      <section className="mx-auto w-full max-w-6xl px-5 py-16 sm:px-8">
        <div className="grid gap-10 lg:grid-cols-[1fr_1.2fr]">
          <div>
            <p className="text-xs font-bold uppercase tracking-[0.2em] text-[#39776a]">{c.presEyebrow}</p>
            <h2 className="mt-2 text-2xl font-semibold tracking-tight sm:text-3xl">{c.presTitle}</h2>
          </div>
          <div className="space-y-4 leading-7 text-slate-700">
            {c.pres.map((p) => <p key={p}>{p}</p>)}
          </div>
        </div>
      </section>

      <section className="border-y border-slate-200/70 bg-white">
        <div className="mx-auto max-w-6xl px-5 py-16 sm:px-8">
          <p className="text-xs font-bold uppercase tracking-[0.2em] text-[#39776a]">{c.skillsEyebrow}</p>
          <h2 className="mt-2 text-2xl font-semibold tracking-tight sm:text-3xl">{c.skillsTitle}</h2>
          <div className="mt-8 grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
            {c.skills.map((s) => (
              <article key={s.title} className="rounded-2xl border border-slate-200 bg-[#f7f8f5] p-6 transition hover:-translate-y-0.5 hover:border-slate-300 hover:bg-white hover:shadow-md">
                <Icon name={s.icon} />
                <h3 className="mt-4 font-semibold text-slate-900">{s.title}</h3>
                <p className="mt-2 text-sm leading-6 text-slate-600">{s.text}</p>
              </article>
            ))}
          </div>
        </div>
      </section>

      <section className="mx-auto w-full max-w-6xl px-5 py-16 sm:px-8">
        <div className="grid gap-8 lg:grid-cols-2">
          <div className="rounded-3xl border border-slate-200 bg-white p-7 shadow-sm">
            <p className="text-xs font-bold uppercase tracking-[0.2em] text-[#39776a]">{c.contribEyebrow}</p>
            <h2 className="mt-2 text-2xl font-semibold tracking-tight">{c.contribTitle}</h2>
            <ul className="mt-5 space-y-3">
              {c.contrib.map((item) => (
                <li key={item} className="flex gap-3 text-sm leading-6 text-slate-700">
                  <svg aria-hidden="true" viewBox="0 0 20 20" fill="none" className="mt-0.5 h-5 w-5 shrink-0 text-[#39776a]"><path d="m5 10 3.2 3.2L15.5 6" stroke="currentColor" strokeWidth="1.8" strokeLinecap="round" strokeLinejoin="round" /></svg>
                  {item}
                </li>
              ))}
            </ul>
          </div>
          <figure className="rounded-3xl bg-[#123b36] p-7 text-white shadow-sm">
            <p className="text-xs font-bold uppercase tracking-[0.2em] text-[#f3c969]">{c.wordEyebrow}</p>
            <blockquote className="mt-4 space-y-4 leading-7 text-white/90">
              <p>{c.word}</p>
              <p className="font-semibold text-white">{c.wordStrong}</p>
            </blockquote>
            <figcaption className="mt-5 border-t border-white/15 pt-4 text-sm text-white/70" dir="ltr">{LAST_NAME} {FIRST_NAME}</figcaption>
          </figure>
        </div>
      </section>

      <PlatformFooter lang={lang} />
    </main>
  );
}
