import { baseOf, loadConsulate } from '@/lib/server';
import { t } from '@/lib/i18n';
import { pageTitle } from '@/lib/pageTitles';
import AssociationBoard from '@/components/AssociationBoard';

export async function generateMetadata({ params }: { params: Promise<{ lang: string }> }) {
  const { lang } = await params;
  return { title: pageTitle(lang, 'Espace du bureau') };
}

export default async function BoardPage({ params }: { params: Promise<{ consulate: string; lang: string }> }) {
  const { consulate: slug, lang: rawLang } = await params;
  const { lang } = await loadConsulate(slug, rawLang);
  baseOf(slug, lang);

  return (
    <>
      <h1 className="text-3xl font-bold text-brand">{t(lang, 'board_page_title')}</h1>
      <AssociationBoard consulateSlug={slug} lang={lang} />
    </>
  );
}
