import Link from 'next/link';
import { api } from '@/lib/api';
import { baseOf, loadConsulate } from '@/lib/server';
import { t } from '@/lib/i18n';
import type { Service } from '@/lib/types';
export const instant = false;

export const metadata = { title: 'Services consulaires' };

export default async function ServicesPage({ params }: { params: Promise<{ consulate: string; lang: string }> }) {
  const { consulate: slug, lang: rawLang } = await params;
  const { lang } = await loadConsulate(slug, rawLang);
  const base = baseOf(slug, lang);
  const { data: services } = await api<{ data: Service[] }>(`/${slug}/services`, { lang, revalidate: 300 });

  return (
    <>
      <section className="text-white" style={{ background: 'var(--brand)' }}>
        <div className="mx-auto max-w-6xl px-4 py-10">
          <h1 className="text-3xl font-bold">{t(lang, 'services')}</h1>
          <p className="mt-2 text-white/90">{t(lang, 'documents_hint')}</p>
        </div>
      </section>
      <section className="mx-auto max-w-6xl px-4 py-10">
        <div className="grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
          {services.map((s) => (
            <article key={s.slug} className="card flex flex-col p-6">
              <h2 className="text-lg font-bold text-slate-900">{s.name}</h2>
              <p className="mt-2 flex-1 text-sm text-slate-600">{s.summary}</p>
              <dl className="mt-4 grid grid-cols-2 gap-2 text-xs">
                <div className="rounded-md bg-slate-50 p-2"><dt className="text-slate-500">{t(lang, 'fee')}</dt><dd className="font-semibold">{s.fee ?? '—'}</dd></div>
                <div className="rounded-md bg-slate-50 p-2"><dt className="text-slate-500">{t(lang, 'delay')}</dt><dd className="font-semibold">{s.processing_time ?? '—'}</dd></div>
              </dl>
              <div className="mt-5 flex gap-2">
                <Link href={`${base}/services/${s.slug}`} className="btn-outline flex-1">{t(lang, 'read_more')}</Link>
                {s.allow_appointment && <Link href={`${base}/appointment?service=${s.slug}`} className="btn-primary flex-1">{t(lang, 'book_short')}</Link>}
              </div>
            </article>
          ))}
        </div>
      </section>
    </>
  );
}
