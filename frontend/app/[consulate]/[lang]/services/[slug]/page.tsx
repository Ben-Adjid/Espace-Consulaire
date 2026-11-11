import Link from 'next/link';
import { notFound } from 'next/navigation';
import { api, ApiError } from '@/lib/api';
import { baseOf, loadConsulate } from '@/lib/server';
import { t } from '@/lib/i18n';
import type { Service } from '@/lib/types';
import Prose from '@/components/Prose';
import DocumentChecklist from '@/components/DocumentChecklist';

export default async function ServicePage({ params }: { params: Promise<{ consulate: string; lang: string; slug: string }> }) {
  const { consulate: slug, lang: rawLang, slug: serviceSlug } = await params;
  const { lang } = await loadConsulate(slug, rawLang);
  const base = baseOf(slug, lang);

  let service: Service;
  try {
    service = (await api<{ data: Service }>(`/${slug}/services/${serviceSlug}`, { lang, revalidate: 300 })).data;
  } catch (err) {
    if (err instanceof ApiError && err.status === 404) notFound();
    throw err;
  }

  return (
    <>
      <section className="text-white" style={{ background: 'var(--brand)' }}>
        <div className="mx-auto max-w-4xl px-4 py-10">
          <nav className="mb-3 text-sm text-white/80" aria-label="breadcrumb">
            <Link className="hover:underline" href={`${base}/services`}>{t(lang, 'services')}</Link> <span aria-hidden="true">/</span> {service.name}
          </nav>
          <h1 className="text-3xl font-bold">{service.name}</h1>
          <p className="mt-3 max-w-2xl text-white/90">{service.summary}</p>
        </div>
      </section>

      <div className="mx-auto grid max-w-4xl gap-8 px-4 py-10 md:grid-cols-3">
        <div className="space-y-8 md:col-span-2">
          {service.description && (
            <section>
              <h2 className="section-title mb-3 text-xl">{t(lang, 'read_more')}</h2>
              <Prose text={service.description} className="text-slate-700" />
            </section>
          )}
          {service.documents.length > 0 && (
            <section>
              <h2 className="section-title mb-3 text-xl">{t(lang, 'documents_required')}</h2>
              <p className="mb-3 text-sm text-slate-500">{t(lang, 'documents_hint')}</p>
              <DocumentChecklist items={service.documents} lang={lang} />
            </section>
          )}
        </div>

        <aside className="space-y-4">
          <div className="card space-y-4 p-5">
            <div><p className="text-xs uppercase tracking-wide text-slate-500">{t(lang, 'fee')}</p><p className="text-lg font-bold">{service.fee ?? '—'}</p></div>
            <div><p className="text-xs uppercase tracking-wide text-slate-500">{t(lang, 'delay')}</p><p className="text-lg font-bold">{service.processing_time ?? '—'}</p></div>
            {service.allow_appointment && (
              <Link href={`${base}/appointment?service=${service.slug}`} className="btn-accent w-full py-3">{t(lang, 'book')}</Link>
            )}
          </div>
        </aside>
      </div>
    </>
  );
}
