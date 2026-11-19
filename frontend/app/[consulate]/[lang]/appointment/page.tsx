import { api } from '@/lib/api';
import { baseOf, loadConsulate } from '@/lib/server';
import { t } from '@/lib/i18n';
import type { Service } from '@/lib/types';
import BookingGate from '@/components/BookingGate';
import { pageTitle } from '@/lib/pageTitles';
export const instant = false;

export async function generateMetadata({ params }: { params: Promise<{ lang: string }> }) {
  const { lang } = await params;
  return { title: pageTitle(lang, 'Prendre rendez-vous') };
}

export default async function AppointmentPage({
  params,
  searchParams,
}: {
  params: Promise<{ consulate: string; lang: string }>;
  searchParams: Promise<{ service?: string; for?: string; rel?: string }>;
}) {
  const { consulate: slug, lang: rawLang } = await params;
  const { service, for: forName, rel } = await searchParams;
  // Espace famille : rendez-vous pour une personne à charge (nom et lien limités en longueur).
  const onBehalfInit = forName && forName.trim().length >= 2
    ? { name: forName.slice(0, 120), relation: ['child', 'spouse', 'parent', 'mandate', 'other'].includes(rel ?? '') ? (rel as string) : 'child' }
    : null;
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
        <BookingGate
          consulateSlug={consulate.slug}
          lang={lang}
          base={base}
          services={services}
          preselected={service}
          countries={consulate.covered_countries?.length ? consulate.covered_countries : [consulate.host_country]}
          nationalityLabel={consulate.represented_country}
          onBehalfInit={onBehalfInit}
        />
      </section>
    </>
  );
}
