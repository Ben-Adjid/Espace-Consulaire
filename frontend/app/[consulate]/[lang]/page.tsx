import Link from 'next/link';
import { api } from '@/lib/api';
import { baseOf, loadConsulate } from '@/lib/server';
import { t } from '@/lib/i18n';
import type { NewsItem, Service } from '@/lib/types';
import Prose from '@/components/Prose';
import OsmMap from '@/components/OsmMap';
import Logo from '@/components/Logo';

export default async function Home({ params }: { params: Promise<{ consulate: string; lang: string }> }) {
  const { consulate: slug, lang: rawLang } = await params;
  const { consulate, lang } = await loadConsulate(slug, rawLang);
  const base = baseOf(slug, lang);

  const [services, news] = await Promise.all([
    api<{ data: Service[] }>(`/${slug}/services`, { lang, revalidate: 300 }),
    api<{ data: NewsItem[] }>(`/${slug}/news`, { lang, query: { per_page: 3 }, revalidate: 300 }),
  ]);

  return (
    <>
      <section className="text-white" style={{ background: 'var(--brand)' }}>
        <div className="mx-auto grid max-w-6xl items-center gap-8 px-4 py-14 md:grid-cols-5">
          <div className="md:col-span-3">
            <p className="mb-3 inline-block rounded-full bg-white/15 px-3 py-1 text-xs font-semibold">
              {consulate.represented_country} · {consulate.host_country}
            </p>
            <h1 className="text-3xl font-extrabold leading-tight sm:text-4xl lg:text-5xl">{consulate.hero_title}</h1>
            {consulate.hero_subtitle && <p className="mt-4 max-w-xl text-base text-white/90 sm:text-lg">{consulate.hero_subtitle}</p>}
            <div className="mt-7 flex flex-wrap gap-3">
              <Link href={`${base}/appointment`} className="btn-accent px-6 py-3 text-base">{t(lang, 'book')}</Link>
              <Link href={`${base}/services`} className="btn border border-white/60 px-6 py-3 text-base text-white hover:bg-white/10">{t(lang, 'services')}</Link>
            </div>
          </div>
          <div className="hidden justify-self-end md:col-span-2 md:block">
            <div className="rounded-2xl bg-white/10 p-8"><Logo consulate={consulate} size={120} /></div>
          </div>
        </div>
      </section>

      <section className="mx-auto max-w-6xl px-4 py-12">
        <h2 className="section-title mb-6">{t(lang, 'quick_access')}</h2>
        <div className="grid grid-cols-2 gap-4 md:grid-cols-4">
          {services.data.slice(0, 8).map((s) => (
            <Link key={s.slug} href={`${base}/services/${s.slug}`} className="card flex flex-col items-center gap-3 p-5 text-center transition hover:-translate-y-0.5 hover:shadow-md">
              <span className="text-sm font-semibold text-slate-800">{s.name}</span>
            </Link>
          ))}
        </div>
        <p className="mt-5"><Link href={`${base}/services`} className="font-semibold text-brand hover:underline">{t(lang, 'see_all')} →</Link></p>
      </section>

      {(consulate.consul_message || consulate.mission) && (
        <section className="bg-white py-12">
          <div className="mx-auto grid max-w-6xl gap-8 px-4 md:grid-cols-2">
            {consulate.consul_message && (
              <blockquote className="border-s-4 ps-4 text-slate-700" style={{ borderColor: 'var(--brand-2)' }}>
                <h2 className="section-title mb-4">{t(lang, 'consul_word')}</h2>
                <Prose text={consulate.consul_message} />
                {consulate.consul_name && <footer className="mt-2 text-sm font-semibold text-brand">— {consulate.consul_name}</footer>}
              </blockquote>
            )}
            {consulate.mission && (
              <div>
                <h2 className="section-title mb-4">{t(lang, 'mission')}</h2>
                <Prose text={consulate.mission} className="text-slate-700" />
              </div>
            )}
          </div>
        </section>
      )}

      <section className="mx-auto max-w-6xl px-4 py-12">
        <h2 className="section-title mb-6">{t(lang, 'find_us')}</h2>
        <div className="grid gap-6 md:grid-cols-2">
          <div className="card space-y-5 p-6">
            <div><h3 className="font-semibold">{t(lang, 'address')}</h3><p className="whitespace-pre-line text-slate-600">{consulate.address}</p></div>
            <div>
              <h3 className="font-semibold">{t(lang, 'hours')}</h3>
              <p className="whitespace-pre-line text-slate-600">{consulate.opening_hours}</p>
            </div>
            <div>
              <h3 className="font-semibold">{t(lang, 'contacts')}</h3>
              {consulate.phone && <p dir="ltr" className="text-slate-600">{consulate.phone}</p>}
              {consulate.email && <p><a className="text-brand hover:underline" href={`mailto:${consulate.email}`}>{consulate.email}</a></p>}
              {consulate.whatsapp && (
                <p><a className="font-semibold text-green-700 hover:underline" target="_blank" rel="noopener noreferrer" href={`https://wa.me/${consulate.whatsapp}`}>WhatsApp</a></p>
              )}
            </div>
          </div>
          <OsmMap consulate={consulate} lang={lang} />
        </div>
      </section>

      {news.data.length > 0 && (
        <section className="mx-auto max-w-6xl px-4 pb-10">
          <div className="mb-6 flex items-end justify-between">
            <h2 className="section-title">{t(lang, 'latest_news')}</h2>
            <Link href={`${base}/news`} className="text-sm font-semibold text-brand hover:underline">{t(lang, 'see_all')} →</Link>
          </div>
          <div className="grid gap-5 md:grid-cols-3">
            {news.data.map((n) => (
              <Link key={n.slug} href={`${base}/news/${n.slug}`} className="card flex flex-col p-5 transition hover:shadow-md">
                <time className="text-xs font-semibold uppercase" style={{ color: 'var(--brand-3)' }} dateTime={n.published_at ?? undefined}>
                  {n.published_at ? new Date(n.published_at).toLocaleDateString(lang === 'ar' ? 'ar' : lang === 'en' ? 'en-GB' : 'fr-FR') : ''}
                </time>
                <h3 className="mt-1 text-lg font-bold text-slate-900">{n.title}</h3>
                <p className="mt-2 text-sm text-slate-600">{n.excerpt}</p>
              </Link>
            ))}
          </div>
        </section>
      )}
    </>
  );
}
