import { api } from '@/lib/api';
import { loadConsulate } from '@/lib/server';
import { t } from '@/lib/i18n';
import type { Faq } from '@/lib/types';
import FaqSearch from '@/components/FaqSearch';
export const instant = false;

export const metadata = { title: 'Questions fréquentes' };

export default async function FaqPage({ params }: { params: Promise<{ consulate: string; lang: string }> }) {
  const { consulate: slug, lang: rawLang } = await params;
  const { lang } = await loadConsulate(slug, rawLang);
  const { data: faqs } = await api<{ data: Faq[] }>(`/${slug}/faqs`, { lang, revalidate: 300 });

  return (
    <>
      <section className="text-white" style={{ background: 'var(--brand)' }}>
        <div className="mx-auto max-w-4xl px-4 py-10"><h1 className="text-3xl font-bold">{t(lang, 'faq')}</h1></div>
      </section>
      <section className="mx-auto max-w-4xl px-4 py-10">
        <FaqSearch faqs={faqs} lang={lang} />
      </section>
    </>
  );
}
