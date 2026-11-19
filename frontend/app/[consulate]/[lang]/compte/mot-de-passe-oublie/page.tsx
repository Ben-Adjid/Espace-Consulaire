import { baseOf, loadConsulate } from '@/lib/server';
import { t } from '@/lib/i18n';
import { ForgotPasswordForm } from '@/components/CitizenRecovery';
import { pageTitle } from '@/lib/pageTitles';
export const instant = false;

export async function generateMetadata({ params }: { params: Promise<{ lang: string }> }) {
  const { lang } = await params;
  return { title: pageTitle(lang, 'Mot de passe oublié') };
}

export default async function ForgotPage({ params }: { params: Promise<{ consulate: string; lang: string }> }) {
  const { consulate: slug, lang: rawLang } = await params;
  const { lang } = await loadConsulate(slug, rawLang);
  const base = baseOf(slug, lang);

  return (
    <section className="mx-auto max-w-md space-y-6 px-4 py-10">
      <div>
        <h1 className="text-3xl font-bold text-brand">{t(lang, 'account_forgot_title')}</h1>
        <p className="mt-2 text-slate-600">{t(lang, 'account_forgot_intro')}</p>
      </div>
      <ForgotPasswordForm consulateSlug={slug} lang={lang} base={base} />
    </section>
  );
}
