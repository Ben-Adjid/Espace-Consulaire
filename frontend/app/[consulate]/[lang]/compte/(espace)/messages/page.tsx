import { baseOf, loadConsulate } from '@/lib/server';
import { t } from '@/lib/i18n';
import { ConversationList } from '@/components/Messages';
import { pageTitle } from '@/lib/pageTitles';
export const instant = false;

export async function generateMetadata({ params }: { params: Promise<{ lang: string }> }) {
  const { lang } = await params;
  return { title: pageTitle(lang, 'Messagerie') };
}

export default async function MessagesPage({ params, searchParams }: { params: Promise<{ consulate: string; lang: string }>; searchParams: Promise<{ demande?: string }> }) {
  const { consulate: slug, lang: rawLang } = await params;
  const { demande } = await searchParams;
  const { lang } = await loadConsulate(slug, rawLang);
  const base = baseOf(slug, lang);
  const preRequestId = demande && /^\d+$/.test(demande) ? Number(demande) : undefined;

  return (
    <>
      <h1 className="text-3xl font-bold text-brand">{t(lang, 'msg_page_title')}</h1>
      <ConversationList consulateSlug={slug} lang={lang} base={base} preRequestId={preRequestId} />
    </>
  );
}
