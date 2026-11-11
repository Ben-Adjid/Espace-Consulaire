import { api } from '@/lib/api';
import { baseOf, loadConsulate } from '@/lib/server';
import { t } from '@/lib/i18n';
import type { Service } from '@/lib/types';
import BookingWizard from '@/components/BookingWizard';

export const metadata = { title: 'Prendre rendez-vous' };

export default async function AppointmentPage({
  params,
  searchParams,
}: {
  params: Promise<{ consulate: string; lang: string }>;
  searchParams: Promise<{ service?: string }>;
}) {
  const { consulate: slug, lang: rawLang } = await params;
  const { service } = await searchParams;
  const { consulate, lang } = await loadConsulate(slug, rawLang);
  const base = baseOf(slug, lang);
  const { data: services } = await api<{ data: Service[] }>(`/${slug}/services`, { lang, revalidate: 300 });

  return (
    <>
      <section className="text-white" style={{ background: 'var(--brand)' }}>
        <div className="mx-auto max-w-4xl px-4 py-10">
          <h1 className="text-3xl font-bold">{t(lang, 'book')}</h1>
          <p className="mt-2 text-white/90">{t(lang, 'documents_hint')}</p>
        </div>
      </section>
      <section className="mx-auto max-w-4xl px-4 py-10">
        <BookingWizard consulateSlug={consulate.slug} lang={lang} base={base} services={services} preselected={service} />
      </section>
    </>
  );
}
