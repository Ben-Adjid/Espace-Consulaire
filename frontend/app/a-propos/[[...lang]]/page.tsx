import Link from 'next/link';
import { notFound } from 'next/navigation';
import HtmlAttributes from '@/components/HtmlAttributes';
import { ArrowIcon, PlatformFooter, PlatformHeader } from '@/components/PlatformChrome';
import { dirOf } from '@/lib/i18n';
import { ABOUT, platformLang, platformPath } from '@/lib/platformText';

type Params = { params: Promise<{ lang?: string[] }> };

export function generateStaticParams() {
  return [{ lang: [] }, { lang: ['en'] }, { lang: ['ar'] }];
}

export async function generateMetadata({ params }: Params) {
  const lang = platformLang((await params).lang) ?? 'fr';
  return { title: ABOUT[lang].meta };
}

/** Icônes discrètes, au trait, cohérentes avec le reste du site. */
const ICONS: Record<string, string> = {
  info: 'M12 8h.01M11 12h1v5h1M12 21a9 9 0 1 0 0-18 9 9 0 0 0 0 18Z',
  compass: 'M12 21a9 9 0 1 0 0-18 9 9 0 0 0 0 18Zm3.5-12.5-2 5-5 2 2-5 5-2Z',
  calendar: 'M7 3v3M17 3v3M4 9h16M5 5h14a1 1 0 0 1 1 1v13a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1V6a1 1 0 0 1 1-1Z',
  doc: 'M8 3h6l5 5v12a1 1 0 0 1-1 1H8a1 1 0 0 1-1-1V4a1 1 0 0 1 1-1Zm6 0v5h5M10 13h6M10 17h6',
  clock: 'M12 7v5l3 2M12 21a9 9 0 1 0 0-18 9 9 0 0 0 0 18Z',
  globe: 'M12 21a9 9 0 1 0 0-18 9 9 0 0 0 0 18ZM3 12h18M12 3c2.5 2.6 3.8 5.6 3.8 9S14.5 18.4 12 21c-2.5-2.6-3.8-5.6-3.8-9S9.5 5.6 12 3Z',
  user: 'M12 11a3.5 3.5 0 1 0 0-7 3.5 3.5 0 0 0 0 7ZM5 20c.8-3.5 3.6-5.5 7-5.5s6.2 2 7 5.5',
  shield: 'M12 3 5 6v6c0 4.2 2.9 7.6 7 9 4.1-1.4 7-4.8 7-9V6l-7-3Zm-3 9 2 2 4-4',
  phone: 'M8 3h8a1 1 0 0 1 1 1v16a1 1 0 0 1-1 1H8a1 1 0 0 1-1-1V4a1 1 0 0 1 1-1Zm3 15h2',
};

function Icon({ name }: { name: string }) {
  return (
    <span aria-hidden="true" className="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-[#123b36]/[0.08] text-[#123b36]">
      <svg viewBox="0 0 24 24" fill="none" className="h-5 w-5"><path d={ICONS[name]} stroke="currentColor" strokeWidth="1.6" strokeLinecap="round" strokeLinejoin="round" /></svg>
    </span>
  );
}

function SectionTitle({ eyebrow, title, intro }: { eyebrow: string; title: string; intro?: string }) {
  return (
    <div className="max-w-2xl">
      <p className="text-xs font-bold uppercase tracking-[0.2em] text-[#39776a]">{eyebrow}</p>
      <h2 className="mt-2 text-2xl font-semibold tracking-tight text-slate-900 sm:text-3xl">{title}</h2>
      {intro && <p className="mt-3 leading-7 text-slate-600">{intro}</p>}
    </div>
  );
}

/** À propos (FR / EN / AR) : présentation, mission, problèmes traités, avantages, fonctionnement, services. */
export default async function AboutPage({ params }: Params) {
  const lang = platformLang((await params).lang);
  if (!lang) notFound();
  const a = ABOUT[lang];
  const dir = dirOf(lang);

  return (
    <main lang={lang} dir={dir} className="flex flex-1 flex-col bg-[#f7f8f5] text-slate-900">
      <HtmlAttributes lang={lang} dir={dir} />
      <PlatformHeader lang={lang} basePath="/a-propos" />

      <section className="border-b border-slate-200/70 bg-white">
        <div className="mx-auto max-w-6xl px-5 py-14 sm:px-8 sm:py-20">
          <p className="text-xs font-bold uppercase tracking-[0.2em] text-[#39776a]">{a.eyebrow}</p>
          <h1 className="mt-3 max-w-3xl text-4xl font-semibold leading-tight tracking-tight sm:text-5xl">{a.title}</h1>
          <p className="mt-5 max-w-2xl text-lg leading-8 text-slate-600">{a.lead}</p>
          <div className="mt-8 flex max-w-3xl gap-4 rounded-2xl border border-amber-200 bg-amber-50/70 p-5">
            <Icon name="info" />
            <p className="text-sm leading-6 text-amber-950"><strong className="font-semibold">{a.noticeStrong}</strong> {a.notice}</p>
          </div>
        </div>
      </section>

      <section className="mx-auto w-full max-w-6xl px-5 py-16 sm:px-8">
        <SectionTitle eyebrow={a.missionEyebrow} title={a.missionTitle} intro={a.missionIntro} />
        <div className="mt-8 grid gap-5 md:grid-cols-3">
          {a.objectives.map((o) => (
            <div key={o.title} className="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm transition hover:-translate-y-0.5 hover:shadow-md">
              <Icon name={o.icon} />
              <h3 className="mt-4 text-lg font-semibold">{o.title}</h3>
              <p className="mt-2 text-sm leading-6 text-slate-600">{o.text}</p>
            </div>
          ))}
        </div>
      </section>

      <section className="border-y border-slate-200/70 bg-white">
        <div className="mx-auto max-w-6xl px-5 py-16 sm:px-8">
          <SectionTitle eyebrow={a.problemsEyebrow} title={a.problemsTitle} />
          <dl className="mt-8 grid gap-4 md:grid-cols-2">
            {a.problems.map((p) => (
              <div key={p.problem} className="rounded-2xl bg-[#f7f8f5] p-6">
                <dt className="flex items-center gap-2 font-semibold text-slate-900"><span aria-hidden="true" className="h-2 w-2 rounded-full bg-[#c98a1a]" />{p.problem}</dt>
                <dd className="mt-2 text-sm leading-6 text-slate-600">{p.answer}</dd>
              </div>
            ))}
          </dl>
        </div>
      </section>

      <section className="mx-auto w-full max-w-6xl px-5 py-16 sm:px-8">
        <SectionTitle eyebrow={a.benefitsEyebrow} title={a.benefitsTitle} />
        <div className="mt-8 grid gap-5 sm:grid-cols-2 lg:grid-cols-4">
          {a.benefits.map((b) => (
            <div key={b.title} className="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
              <Icon name={b.icon} />
              <h3 className="mt-4 font-semibold">{b.title}</h3>
              <p className="mt-2 text-sm leading-6 text-slate-600">{b.text}</p>
            </div>
          ))}
        </div>
      </section>

      <section className="bg-[#123b36] text-white">
        <div className="mx-auto max-w-6xl px-5 py-16 sm:px-8">
          <p className="text-xs font-bold uppercase tracking-[0.2em] text-[#f3c969]">{a.stepsEyebrow}</p>
          <h2 className="mt-2 text-2xl font-semibold tracking-tight sm:text-3xl">{a.stepsTitle}</h2>
          <ol className="mt-10 grid gap-6 md:grid-cols-4">
            {a.steps.map((s, i) => (
              <li key={s.title} className="border-t border-white/20 pt-5">
                <span className="text-sm font-bold text-[#f3c969]">{a.stepLabel} {i + 1}</span>
                <h3 className="mt-2 font-semibold">{s.title}</h3>
                <p className="mt-1 text-sm text-emerald-50/75">{s.text}</p>
              </li>
            ))}
          </ol>
        </div>
      </section>

      <section className="mx-auto w-full max-w-6xl px-5 py-16 sm:px-8">
        <SectionTitle eyebrow={a.servicesEyebrow} title={a.servicesTitle} />
        <div className="mt-8 grid gap-5 md:grid-cols-2">
          {[{ icon: 'globe', title: a.online, items: a.onlineItems }, { icon: 'user', title: a.atConsulate, items: a.consulateItems }].map((col) => (
            <div key={col.title} className="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
              <h3 className="flex items-center gap-3 font-semibold"><Icon name={col.icon} /> {col.title}</h3>
              <ul className="mt-4 space-y-2 text-sm leading-6 text-slate-700">
                {col.items.map((it) => <li key={it} className="flex gap-2"><span aria-hidden="true">•</span>{it}</li>)}
              </ul>
            </div>
          ))}
        </div>
        <div className="mt-12 flex flex-wrap gap-3">
          <Link href="/#consulats" className="inline-flex items-center gap-2 rounded-xl bg-[#123b36] px-5 py-3 text-sm font-semibold text-white hover:bg-[#1b5148]">{a.ctaConsulate} <ArrowIcon /></Link>
          <Link href={platformPath('/connexion', lang)} className="rounded-xl border border-slate-300 bg-white px-5 py-3 text-sm font-semibold text-slate-700 hover:bg-slate-50">{a.ctaLogin}</Link>
        </div>
      </section>

      <PlatformFooter lang={lang} />
    </main>
  );
}
