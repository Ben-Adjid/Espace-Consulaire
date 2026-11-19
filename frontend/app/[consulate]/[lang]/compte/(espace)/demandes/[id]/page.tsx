import { baseOf, loadConsulate } from '@/lib/server';
import { PreRequestEditor } from '@/components/PreRequests';
import { pageTitle } from '@/lib/pageTitles';
export const instant = false;

export async function generateMetadata({ params }: { params: Promise<{ lang: string }> }) {
  const { lang } = await params;
  return { title: pageTitle(lang, 'Ma pré-demande') };
}

export default async function DemandeEditPage({ params }: { params: Promise<{ consulate: string; lang: string; id: string }> }) {
  const { consulate: slug, lang: rawLang, id } = await params;
  const { lang } = await loadConsulate(slug, rawLang);
  const base = baseOf(slug, lang);

  return (
    <section className="space-y-6">
      <PreRequestEditor consulateSlug={slug} lang={lang} base={base} id={Number(id)} />
    </section>
  );
}
