import { baseOf, loadConsulate } from '@/lib/server';
import { t } from '@/lib/i18n';
import CitizenDashboard from '@/components/CitizenDashboard';
import { pageTitle } from '@/lib/pageTitles';
export const instant = false;

export async function generateMetadata({ params }: { params: Promise<{ lang: string }> }) {
  const { lang } = await params;
  return { title: pageTitle(lang, 'Mon espace') };
}

/** Accueil de l'espace citoyen : prochain rendez-vous et actions rapides. */
export default async function AccountPage({ params }: { params: Promise<{ consulate: string; lang: string }> }) {
  const { consulate: slug, lang: rawLang } = await params;
  const { lang } = await loadConsulate(slug, rawLang);
  const base = baseOf(slug, lang);

  return (
    <>
      <h1 className="text-3xl font-bold text-brand">{t(lang, 'account_title')}</h1>
      <CitizenDashboard consulateSlug={slug} lang={lang} base={base} section="accueil" />
    </>
  );
}
