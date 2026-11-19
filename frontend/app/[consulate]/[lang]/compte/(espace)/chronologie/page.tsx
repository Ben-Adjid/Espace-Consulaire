import { baseOf, loadConsulate } from '@/lib/server';
import { t } from '@/lib/i18n';
import CitizenTimeline from '@/components/CitizenTimeline';
import { pageTitle } from '@/lib/pageTitles';
export const instant = false;

export async function generateMetadata({ params }: { params: Promise<{ lang: string }> }) {
  const { lang } = await params;
  return { title: pageTitle(lang, 'Ma chronologie') };
}

export default async function TimelinePage({ params }: { params: Promise<{ consulate: string; lang: string }> }) {
  const { consulate: slug, lang: rawLang } = await params;
  const { lang } = await loadConsulate(slug, rawLang);
  baseOf(slug, lang);

  return (
    <>
      <h1 className="text-3xl font-bold text-brand">{t(lang, 'timeline_page_title')}</h1>
      <CitizenTimeline consulateSlug={slug} lang={lang} />
    </>
  );
}
