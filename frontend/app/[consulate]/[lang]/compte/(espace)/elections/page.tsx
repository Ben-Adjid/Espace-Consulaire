import { baseOf, loadConsulate } from '@/lib/server';
import { t } from '@/lib/i18n';
import AssociationElections from '@/components/AssociationElections';
import { pageTitle } from '@/lib/pageTitles';
export const instant = false;

export async function generateMetadata({ params }: { params: Promise<{ lang: string }> }) {
  const { lang } = await params;
  return { title: pageTitle(lang, 'Élections de l’association') };
}

export default async function ElectionsPage({ params }: { params: Promise<{ consulate: string; lang: string }> }) {
  const { consulate: slug, lang: rawLang } = await params;
  const { lang } = await loadConsulate(slug, rawLang);
  baseOf(slug, lang);

  return (
    <>
      <h1 className="text-3xl font-bold text-brand">{t(lang, 'elec_page_title')}</h1>
      <AssociationElections consulateSlug={slug} lang={lang} />
    </>
  );
}
