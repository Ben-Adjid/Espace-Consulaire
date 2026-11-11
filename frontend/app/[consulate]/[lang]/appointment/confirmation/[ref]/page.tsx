import { loadConsulate } from '@/lib/server';
import { t } from '@/lib/i18n';
import AppointmentManager from '@/components/AppointmentManager';
export const instant = false;

export const metadata = { title: 'Rendez-vous enregistré' };

/**
 * Confirmation. Le jeton est dans le fragment (#token=…), lu côté navigateur uniquement :
 * il n'apparaît donc ni dans les logs du serveur ni dans l'historique envoyé au serveur.
 */
export default async function ConfirmationPage({ params }: { params: Promise<{ consulate: string; lang: string; ref: string }> }) {
  const { consulate: slug, lang: rawLang, ref } = await params;
  const { lang } = await loadConsulate(slug, rawLang);

  return (
    <section className="mx-auto max-w-2xl px-4 py-12">
      <div className="card overflow-hidden">
        <div className="bg-green-600 p-6 text-center text-white">
          <h1 className="mt-2 text-2xl font-bold">{t(lang, 'confirmed_title')}</h1>
        </div>
        <div className="space-y-5 p-6">
          <div className="rounded-lg bg-slate-50 p-4 text-center">
            <p className="text-xs uppercase tracking-wide text-slate-500">{t(lang, 'reference')}</p>
            <p className="select-all font-mono text-2xl font-bold tracking-wider text-brand" dir="ltr">{ref}</p>
          </div>
          <p className="text-sm text-slate-600">{t(lang, 'confirmed_hint')}</p>
          <AppointmentManager consulateSlug={slug} lang={lang} reference={ref} />
          <p className="text-xs text-slate-500">{t(lang, 'keep_link')}</p>
        </div>
      </div>
    </section>
  );
}
