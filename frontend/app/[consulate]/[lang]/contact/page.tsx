import { loadConsulate } from '@/lib/server';
import { t } from '@/lib/i18n';
import ContactForm from '@/components/ContactForm';

export const metadata = { title: 'Contact' };

export default async function ContactPage({ params }: { params: Promise<{ consulate: string; lang: string }> }) {
  const { consulate: slug, lang: rawLang } = await params;
  const { consulate, lang } = await loadConsulate(slug, rawLang);

  return (
    <>
      <section className="text-white" style={{ background: 'var(--brand)' }}>
        <div className="mx-auto max-w-5xl px-4 py-10"><h1 className="text-3xl font-bold">{t(lang, 'contact_us')}</h1></div>
      </section>
      <div className="mx-auto grid max-w-5xl gap-8 px-4 py-10 md:grid-cols-5">
        <div className="md:col-span-3">
          <ContactForm consulateSlug={consulate.slug} lang={lang} />
        </div>
        <aside className="space-y-4 text-sm md:col-span-2">
          <div className="card space-y-3 p-5">
            <p className="whitespace-pre-line">{consulate.address}</p>
            {consulate.phone && <p dir="ltr">{consulate.phone}</p>}
            {consulate.email && <p><a className="text-brand hover:underline" href={`mailto:${consulate.email}`}>{consulate.email}</a></p>}
            <p className="whitespace-pre-line text-slate-600">{consulate.opening_hours}</p>
          </div>
          {consulate.whatsapp && (
            <a target="_blank" rel="noopener noreferrer" href={`https://wa.me/${consulate.whatsapp}`} className="btn w-full bg-green-600 py-3 text-white hover:bg-green-700">
              {t(lang, 'whatsapp_cta')}
            </a>
          )}
        </aside>
      </div>
    </>
  );
}
