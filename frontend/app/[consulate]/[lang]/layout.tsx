import type { Metadata } from 'next';
import HtmlAttributes from '@/components/HtmlAttributes';
import DemoBanner from '@/components/DemoBanner';
import SiteHeader from '@/components/SiteHeader';
import SiteFooter from '@/components/SiteFooter';
import { dirOf, t } from '@/lib/i18n';
import { baseOf, loadConsulate, loadLegalPages } from '@/lib/server';
import type { Lang } from '@/lib/types';

interface Params {
  consulate: string;
  lang: string;
}

export async function generateMetadata({ params }: { params: Promise<Params> }): Promise<Metadata> {
  const { consulate: slug, lang } = await params;
  const { consulate } = await loadConsulate(slug, lang);
  const base = baseOf(slug, consulate.languages.includes(lang as Lang) ? (lang as Lang) : 'fr');
  return {
    title: { default: consulate.name, template: `%s — ${consulate.name}` },
    description: consulate.hero_subtitle ?? undefined,
    alternates: {
      canonical: base,
      languages: Object.fromEntries(consulate.languages.map((l) => [l, baseOf(slug, l)])),
    },
    openGraph: { title: consulate.name, description: consulate.hero_subtitle ?? undefined, locale: lang },
  };
}

/** Coquille commune à toutes les pages d'un consulat : bandeau démo, thème, en-tête, pied de page. */
export default async function ConsulateLayout({ children, params }: { children: React.ReactNode; params: Promise<Params> }) {
  const { consulate: slug, lang: rawLang } = await params;
  const { consulate, lang } = await loadConsulate(slug, rawLang);
  const pages = await loadLegalPages(slug, lang);
  const base = baseOf(slug, lang);
  const dir = dirOf(lang);

  // Variables CSS du thème : couleurs validées par l'API (format #hex uniquement).
  const theme = {
    '--brand': consulate.theme.primary,
    '--brand-2': consulate.theme.secondary,
    '--brand-3': consulate.theme.accent,
  } as React.CSSProperties;

  return (
    <div dir={dir} lang={lang} style={theme} className="flex min-h-screen flex-1 flex-col">
      <HtmlAttributes lang={lang} dir={dir} />
      <a href="#main" className="hp focus:not-sr-only focus:absolute focus:start-2 focus:top-2 focus:z-50 focus:rounded focus:bg-white focus:p-2">
        {t(lang, 'skip')}
      </a>
      <DemoBanner lang={lang} />
      <SiteHeader consulate={consulate} lang={lang} base={base} />
      <main id="main" className="flex-1">{children}</main>
      <SiteFooter consulate={consulate} lang={lang} base={base} pages={pages} />
    </div>
  );
}
