import { api } from '@/lib/api';
import { baseOf, loadConsulate } from '@/lib/server';
import { t } from '@/lib/i18n';
import type { Service } from '@/lib/types';
import { PreRequestList } from '@/components/PreRequests';
import { pageTitle } from '@/lib/pageTitles';
export const instant = false;

export async function generateMetadata({ params }: { params: Promise<{ lang: string }> }) {
  const { lang } = await params;
  return { title: pageTitle(lang, 'Mes démarches') };
}

export default async function DemandesPage({ params }: { params: Promise<{ consulate: string; lang: string }> }) {
  const { consulate: slug, lang: rawLang } = await params;
  const { lang } = await loadConsulate(slug, rawLang);
  const base = baseOf(slug, lang);
  const { data } = await api<{ data: Service[] }>(`/${slug}/services`, { lang, revalidate: 300 });

  return (
    <>
      <h1 className="text-3xl font-bold text-brand">{t(lang, 'pre_page_title')}</h1>
      <PreRequestList consulateSlug={slug} lang={lang} base={base} services={data.map((s) => ({ slug: s.slug, name: s.name }))} />
    </>
  );
}
