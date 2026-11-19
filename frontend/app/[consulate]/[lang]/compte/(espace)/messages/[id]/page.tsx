import { baseOf, loadConsulate } from '@/lib/server';
import { ConversationThread } from '@/components/Messages';
import { pageTitle } from '@/lib/pageTitles';
export const instant = false;

export async function generateMetadata({ params }: { params: Promise<{ lang: string }> }) {
  const { lang } = await params;
  return { title: pageTitle(lang, 'Messagerie') };
}

export default async function ConversationPage({ params }: { params: Promise<{ consulate: string; lang: string; id: string }> }) {
  const { consulate: slug, lang: rawLang, id } = await params;
  const { lang } = await loadConsulate(slug, rawLang);
  const base = baseOf(slug, lang);

  return (
    <section className="space-y-6">
      <ConversationThread consulateSlug={slug} lang={lang} base={base} id={Number(id)} />
    </section>
  );
}
