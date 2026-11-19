import { baseOf, loadConsulate } from '@/lib/server';
import { t } from '@/lib/i18n';
import { VerifyEmailPanel } from '@/components/CitizenRecovery';
import { pageTitle } from '@/lib/pageTitles';
export const instant = false;

export async function generateMetadata({ params }: { params: Promise<{ lang: string }> }) {
  const { lang } = await params;
  return { title: pageTitle(lang, 'Confirmer mon email') };
}

export default async function VerifyPage({ params }: { params: Promise<{ consulate: string; lang: string }> }) {
  const { consulate: slug, lang: rawLang } = await params;
  const { lang } = await loadConsulate(slug, rawLang);
  const base = baseOf(slug, lang);

  return (
    <section className="mx-auto max-w-md space-y-6 px-4 py-10">
      <h1 className="text-3xl font-bold text-brand">{t(lang, 'account_verify_title')}</h1>
      <VerifyEmailPanel consulateSlug={slug} lang={lang} base={base} />
    </section>
  );
}
