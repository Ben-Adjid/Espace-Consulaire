import { baseOf, loadConsulate } from '@/lib/server';
import { t } from '@/lib/i18n';
import { CitizenLoginForm } from '@/components/CitizenAuth';
import Logo from '@/components/Logo';
import { pageTitle } from '@/lib/pageTitles';
export const instant = false;

export async function generateMetadata({ params }: { params: Promise<{ lang: string }> }) {
  const { lang } = await params;
  return { title: pageTitle(lang, 'Connexion') };
}

/** Connexion à l'espace citoyen d'un consulat : carte claire, consulat identifié, orientation des agents. */
export default async function LoginPage({ params }: { params: Promise<{ consulate: string; lang: string }> }) {
  const { consulate: slug, lang: rawLang } = await params;
  const { consulate, lang } = await loadConsulate(slug, rawLang);
  const base = baseOf(slug, lang);

  return (
    <section className="relative isolate overflow-hidden px-4 py-12 sm:py-16">
      <div aria-hidden="true" className="absolute inset-0 -z-10" style={{ background: 'radial-gradient(50rem 24rem at 100% 0%, color-mix(in srgb, var(--brand) 9%, transparent), transparent 60%), radial-gradient(36rem 20rem at 0% 100%, color-mix(in srgb, var(--brand-2) 14%, transparent), transparent 60%)' }} />
      <div className="mx-auto max-w-md">
        <div className="mb-6 flex flex-col items-center text-center">
          <Logo consulate={consulate} size={56} />
          <p className="mt-4 inline-flex items-center gap-2 rounded-full bg-white px-3 py-1 text-xs font-semibold text-brand shadow-sm ring-1 ring-slate-200">
            {t(lang, 'citizen_space')}
          </p>
          <h1 className="mt-3 text-3xl font-bold tracking-tight text-slate-900">{t(lang, 'account_login_title')}</h1>
          <p className="mt-2 text-sm text-slate-600">{t(lang, 'login_subtitle')} <span className="font-semibold text-slate-800">{consulate.name}</span>.</p>
        </div>
        <CitizenLoginForm consulateSlug={slug} lang={lang} base={base} />
      </div>
    </section>
  );
}
