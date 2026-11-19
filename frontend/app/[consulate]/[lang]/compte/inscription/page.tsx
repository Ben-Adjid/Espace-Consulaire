import { baseOf, loadConsulate } from '@/lib/server';
import { t } from '@/lib/i18n';
import { CitizenRegisterForm } from '@/components/CitizenAuth';
import { pageTitle } from '@/lib/pageTitles';
export const instant = false;

export async function generateMetadata({ params }: { params: Promise<{ lang: string }> }) {
  const { lang } = await params;
  return { title: pageTitle(lang, 'Créer mon compte') };
}

export default async function RegisterPage({ params }: { params: Promise<{ consulate: string; lang: string }> }) {
  const { consulate: slug, lang: rawLang } = await params;
  const { consulate, lang } = await loadConsulate(slug, rawLang);
  const base = baseOf(slug, lang);

  return (
    <section className="mx-auto max-w-2xl space-y-6 px-4 py-10">
      <div>
        <h1 className="text-3xl font-bold text-brand">{t(lang, 'account_register_title')}</h1>
        <p className="mt-2 text-slate-600">{t(lang, 'account_register_intro')}</p>
      </div>
      <CitizenRegisterForm
        consulateSlug={slug}
        lang={lang}
        base={base}
        countries={consulate.covered_countries?.length ? consulate.covered_countries : [consulate.host_country]}
        nationalityLabel={consulate.represented_country}
      />
    </section>
  );
}
