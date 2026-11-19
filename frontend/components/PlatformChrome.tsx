import Link from 'next/link';
import CurrentYear from './CurrentYear';
import { LANG_NAMES } from '@/lib/i18n';
import { CHROME, PLATFORM_LANGS, platformPath } from '@/lib/platformText';
import type { Lang } from '@/lib/types';

const ADMIN_URL = process.env.NEXT_PUBLIC_ADMIN_URL ?? 'http://127.0.0.1:8000/admin';

export function ArrowIcon() {
  return (
    <svg aria-hidden="true" viewBox="0 0 20 20" fill="none" className="h-5 w-5 rtl:-scale-x-100">
      <path d="M4.167 10h11.666M10 4.167 15.833 10 10 15.833" stroke="currentColor" strokeWidth="1.7" strokeLinecap="round" strokeLinejoin="round" />
    </svg>
  );
}

function BrandMark() {
  return (
    <span className="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-[#123b36] text-white shadow-sm">
      <svg aria-hidden="true" viewBox="0 0 24 24" fill="none" className="h-5 w-5">
        <path d="M4 20h16M6.5 20V8.5L12 5l5.5 3.5V20M9.5 11h5M9.5 14.5h5M9.5 18h5" stroke="currentColor" strokeWidth="1.6" strokeLinecap="round" strokeLinejoin="round" />
      </svg>
    </span>
  );
}

/** Sélecteur de langue des pages de plateforme : même page, autre langue (/a-propos, /a-propos/en, /a-propos/ar). */
function LanguageSwitch({ lang, basePath }: { lang: Lang; basePath: string }) {
  return (
    <div role="group" aria-label={CHROME[lang].language} className="flex overflow-hidden rounded-full border border-slate-300 text-xs font-semibold">
      {PLATFORM_LANGS.map((code) => (
        <Link
          key={code}
          href={platformPath(basePath, code)}
          hrefLang={code}
          lang={code}
          aria-current={code === lang ? 'page' : undefined}
          className={`px-2.5 py-1.5 ${code === lang ? 'bg-[#123b36] text-white' : 'bg-white text-slate-700 hover:bg-slate-100'}`}
        >
          {code === 'ar' ? LANG_NAMES.ar : code.toUpperCase()}
        </Link>
      ))}
    </div>
  );
}

/**
 * En-tête commun aux pages de la plateforme. `basePath` active le sélecteur de langue
 * (pages traduites : À propos, Crédits, Connexion).
 */
export function PlatformHeader({ lang = 'fr', basePath }: { lang?: Lang; basePath?: string }) {
  const c = CHROME[lang];
  return (
    <header className="sticky top-0 z-30 border-b border-slate-200/80 bg-white/95 backdrop-blur">
      <div className="mx-auto flex max-w-7xl items-center justify-between gap-3 px-5 py-3.5 sm:px-8">
        <Link href={platformPath('/', lang)} aria-label={c.home} className="flex min-w-0 items-center gap-3">
          <BrandMark />
          <span className="min-w-0">
            <span className="block truncate text-sm font-bold tracking-tight text-slate-900 sm:text-base">{c.brand}</span>
            <span className="hidden text-xs text-slate-500 sm:block">{c.tagline}</span>
          </span>
        </Link>
        <nav aria-label={c.nav} className="hidden items-center gap-6 text-sm font-medium text-slate-600 lg:flex">
          <Link href={`${platformPath('/', lang)}#consulats`} className="hover:text-slate-900">{c.consulates}</Link>
          <Link href={`${platformPath('/', lang)}#fonctionnement`} className="hover:text-slate-900">{c.howItWorks}</Link>
          <Link href={platformPath('/a-propos', lang)} className="hover:text-slate-900">{c.about}</Link>
        </nav>
        <div className="flex items-center gap-2">
          {basePath && <LanguageSwitch lang={lang} basePath={basePath} />}
          {/* Deux espaces distincts : la page de connexion oriente vers le bon (citoyen par consulat, ou agents). */}
          <Link href={`${platformPath('/connexion', lang)}#agent`} className="hidden rounded-full border border-[#123b36] px-4 py-2 text-sm font-semibold text-[#123b36] transition hover:bg-[#123b36] hover:text-white md:inline-flex">
            {c.agents}
          </Link>
          <Link href={`${platformPath('/connexion', lang)}#citoyen`} className="inline-flex items-center gap-2 whitespace-nowrap rounded-full bg-[#123b36] px-4 py-2 text-sm font-semibold text-white transition hover:bg-[#1b5148]">
            {c.citizen}
          </Link>
        </div>
      </div>
    </header>
  );
}

/** Pied de page de la plateforme : navigation, accès, et crédits de conception en bas. */
export function PlatformFooter({ lang = 'fr' }: { lang?: Lang }) {
  const c = CHROME[lang];
  return (
    <footer className="mt-auto bg-[#0d2c28] text-emerald-50/80">
      <div className="mx-auto grid max-w-7xl gap-10 px-5 py-12 sm:grid-cols-2 sm:px-8 lg:grid-cols-4">
        <div className="lg:col-span-2">
          <div className="flex items-center gap-3 text-white">
            <BrandMark />
            <span className="font-bold">{c.brand}</span>
          </div>
          <p className="mt-4 max-w-md text-sm leading-6">{c.footerText}</p>
        </div>
        <div>
          <h2 className="text-sm font-semibold text-white">{c.platform}</h2>
          <ul className="mt-3 space-y-2 text-sm">
            <li><Link className="hover:text-white hover:underline" href={`${platformPath('/', lang)}#consulats`}>{c.chooseConsulate}</Link></li>
            <li><Link className="hover:text-white hover:underline" href={`${platformPath('/', lang)}#fonctionnement`}>{c.howItWorks}</Link></li>
            <li><Link className="hover:text-white hover:underline" href={platformPath('/a-propos', lang)}>{c.about}</Link></li>
            <li><Link className="hover:text-white hover:underline" href={platformPath('/credits', lang)}>{c.credits}</Link></li>
          </ul>
        </div>
        <div>
          <h2 className="text-sm font-semibold text-white">{c.access}</h2>
          <ul className="mt-3 space-y-2 text-sm">
            <li><Link className="hover:text-white hover:underline" href={`${platformPath('/connexion', lang)}#citoyen`}>{c.citizen}</Link></li>
            <li><a className="hover:text-white hover:underline" href={`${ADMIN_URL}/login`}>{c.agentsLong}</a></li>
          </ul>
          <p className="mt-4 text-xs text-emerald-50/60">Français · English · العربية</p>
        </div>
      </div>
      <div className="border-t border-white/10 px-5 py-4 text-center text-xs text-emerald-50/60 sm:px-8">
        © <CurrentYear /> {c.rights} ·{' '}
        <Link href={platformPath('/credits', lang)} className="hover:text-white hover:underline">{c.design} : Aboubakar Assidick Mahamat Adjid Oumar</Link>
      </div>
    </footer>
  );
}
