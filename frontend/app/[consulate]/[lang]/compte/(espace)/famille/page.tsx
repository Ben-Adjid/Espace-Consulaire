import { baseOf, loadConsulate } from '@/lib/server';
import FamilySpace from '@/components/FamilySpace';
import { t } from '@/lib/i18n';
import { pageTitle } from '@/lib/pageTitles';
export const instant = false;

export async function generateMetadata({ params }: { params: Promise<{ lang: string }> }) {
  const { lang } = await params;
  return { title: pageTitle(lang, 'Ma famille') };
}

export default async function FamilyPage({ params }: { params: Promise<{ consulate: string; lang: string }> }) {
  const { consulate: slug, lang: rawLang } = await params;
  const { lang } = await loadConsulate(slug, rawLang);
  const base = baseOf(slug, lang);

  return (
    <>
      <h1 className="text-3xl font-bold text-brand">{t(lang, 'family_title')}</h1>
      <FamilySpace consulateSlug={slug} lang={lang} base={base} />
    </>
  );
}
