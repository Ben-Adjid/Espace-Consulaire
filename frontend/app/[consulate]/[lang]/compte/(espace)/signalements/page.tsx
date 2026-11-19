import { loadConsulate } from '@/lib/server';
import { t } from '@/lib/i18n';
import CitizenReports from '@/components/CitizenReports';
import { pageTitle } from '@/lib/pageTitles';
export const instant = false;

export async function generateMetadata({ params }: { params: Promise<{ lang: string }> }) {
  const { lang } = await params;
  return { title: pageTitle(lang, 'Mes signalements') };
}

export default async function ReportsPage({ params }: { params: Promise<{ consulate: string; lang: string }> }) {
  const { consulate: slug, lang: rawLang } = await params;
  const { lang } = await loadConsulate(slug, rawLang);

  return (
    <>
      <h1 className="text-3xl font-bold text-brand">{t(lang, 'report_title')}</h1>
      <CitizenReports consulateSlug={slug} lang={lang} />
    </>
  );
}
