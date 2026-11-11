import Link from 'next/link';
import { api } from '@/lib/api';
import { baseOf, loadConsulate } from '@/lib/server';
import { t } from '@/lib/i18n';
import type { NewsItem, Service } from '@/lib/types';
import Prose from '@/components/Prose';
import OsmMap from '@/components/OsmMap';
import Logo from '@/components/Logo';
export const instant = false;

export default async function Home({ params }: { params: Promise<{ consulate: string; lang: string }> }) {
  const { consulate: slug, lang: rawLang } = await params;
  const { consulate, lang } = await loadConsulate(slug, rawLang);
  const base = baseOf(slug, lang);

  const [servicesResult, newsResult] = await Promise.allSettled([
    api<{ data: Service[] }>(`/${slug}/services`, { lang, revalidate: 300 }),
    api<{ data: NewsItem[] }>(`/${slug}/news`, { lang, query: { per_page: 3 }, revalidate: 300 }),
  ]);
  const services = servicesResult.status === 'fulfilled' ? servicesResult.value.data : [];
  const news = newsResult.status === 'fulfilled' ? newsResult.value.data : [];

  return (
    <>
      <section className="relative isolate overflow-hidden text-white" style={{ background: `linear-gradient(125deg, var(--brand), color-mix(in srgb, var(--brand) 72%, #101827))` }}>
        <div aria-hidden="true" className="absolute -end-24 -top-32 -z-10 h-96 w-96 rounded-full border border-white/10 sm:-end-10 sm:-top-48 sm:h-[32rem] sm:w-[32rem]">
          <div className="absolute inset-10 rounded-full border border-white/10" />
          <div className="absolute inset-24 rounded-full border border-white/10" />
          <div className="absolute inset-40 rounded-full border border-white/10" />
        </div>
        <div className="mx-auto grid max-w-7xl items-center gap-10 px-5 py-14 sm:px-8 sm:py-20 lg:grid-cols-[1.2fr_0.8fr] lg:gap-16 lg:py-24">
          <div className="max-w-3xl">
            <p className="mb-5 inline-flex items-center gap-2 rounded-full border border-white/20 bg-white/10 px-3.5 py-1.5 text-xs font-semibold tracking-wide sm:text-sm">
              <span className="h-2 w-2 rounded-full" style={{ backgroundColor: 'var(--brand-2)' }} />
              {consulate.represented_country} <span aria-hidden="true">·</span> {consulate.host_country}
            </p>
            <h1 className="text-4xl font-bold leading-[1.08] tracking-tight sm:text-5xl lg:text-6xl">{consulate.hero_title}</h1>
            {consulate.hero_subtitle && <p className="mt-5 max-w-2xl text-base leading-7 text-white/80 sm:text-lg sm:leading-8">{consulate.hero_subtitle}</p>}
            <div className="mt-8 flex flex-wrap gap-3">
              <Link href={`${base}/appointment`} className="btn-accent rounded-xl px-5 py-3.5 text-sm shadow-lg shadow-black/10 transition hover:-translate-y-0.5 sm:px-6 sm:text-base">
                {t(lang, 'book')}
                <span aria-hidden="true">→</span>
              </Link>
              <Link href={`${base}/services`} className="btn rounded-xl border border-white/40 bg-white/5 px-5 py-3.5 text-sm text-white transition hover:bg-white/15 sm:px-6 sm:text-base">
                {t(lang, 'services')}
              </Link>
            </div>
          </div>
          <div className="relative mx-auto w-full max-w-sm lg:ms-auto">
            <div className="absolute -inset-4 rounded-[2rem] bg-white/5 blur-xl" />
            <div className="relative overflow-hidden rounded-[1.75rem] border border-white/20 bg-white/[0.08] p-6 shadow-2xl shadow-black/15 backdrop-blur-sm sm:p-8">
              <div className="flex items-center justify-between border-b border-white/15 pb-5">
                <div>
                  <p className="text-xs font-medium uppercase tracking-[0.18em] text-white/60">{t(lang, 'consular_services')}</p>
                  <p className="mt-1 text-lg font-semibold">{consulate.name}</p>
                </div>
                <Logo consulate={consulate} size={56} />
              </div>
              <div className="space-y-3 py-5">
                <Link href={`${base}/services`} className="group flex items-center justify-between gap-4 rounded-2xl bg-white/[0.08] p-4 transition hover:bg-white/[0.14]">
                  <span className="flex items-center gap-3">
                    <span className="flex h-10 w-10 items-center justify-center rounded-xl bg-white/10 text-sm font-bold" aria-hidden="true">01</span>
                    <span>
                      <span className="block text-sm font-semibold">{t(lang, 'services')}</span>
                      <span className="mt-0.5 block text-xs text-white/60">{t(lang, 'discover_services')}</span>
                    </span>
                  </span>
                  <span className="text-white/60 transition group-hover:translate-x-1 group-hover:text-white" aria-hidden="true">→</span>
                </Link>
                <Link href={`${base}/appointment`} className="group flex items-center justify-between gap-4 rounded-2xl bg-white/[0.08] p-4 transition hover:bg-white/[0.14]">
                  <span className="flex items-center gap-3">
                    <span className="flex h-10 w-10 items-center justify-center rounded-xl bg-white/10 text-sm font-bold" aria-hidden="true">02</span>
                    <span>
                      <span className="block text-sm font-semibold">{t(lang, 'book_short')}</span>
                      <span className="mt-0.5 block text-xs text-white/60">{t(lang, 'appointment_online')}</span>
                    </span>
                  </span>
                  <span className="text-white/60 transition group-hover:translate-x-1 group-hover:text-white" aria-hidden="true">→</span>
                </Link>
              </div>
              <p className="border-t border-white/15 pt-4 text-xs leading-5 text-white/55">{t(lang, 'service_info_note')}</p>
            </div>
          </div>
        </div>
      </section>

      <section className="mx-auto w-full max-w-7xl px-5 py-14 sm:px-8 sm:py-16">
        <div className="mb-7 flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
          <div>
            <p className="text-xs font-bold uppercase tracking-[0.18em] text-brand">{t(lang, 'start_here')}</p>
            <h2 className="mt-2 text-2xl font-bold tracking-tight text-slate-900 sm:text-3xl">{t(lang, 'quick_access')}</h2>
            <p className="mt-2 max-w-2xl text-sm leading-6 text-slate-600">{t(lang, 'discover_services')}</p>
          </div>
          <Link href={`${base}/services`} className="inline-flex items-center gap-2 text-sm font-semibold text-brand hover:underline">
            {t(lang, 'see_all')} <span aria-hidden="true">→</span>
          </Link>
        </div>
        {servicesResult.status === 'rejected' ? (
          <p role="status" className="rounded-2xl border border-amber-200 bg-amber-50 p-5 text-sm text-amber-900">{t(lang, 'services_unavailable')}</p>
        ) : services.length > 0 ? (
          <div className="grid grid-cols-2 gap-3 sm:grid-cols-3 sm:gap-4 lg:grid-cols-4">
            {services.slice(0, 8).map((s) => (
              <Link key={s.slug} href={`${base}/services/${s.slug}`} className="group rounded-2xl border border-slate-200/80 bg-white p-4 shadow-sm transition hover:-translate-y-1 hover:border-slate-300 hover:shadow-lg hover:shadow-slate-900/[0.06] sm:p-5">
                <span className="flex h-11 w-11 items-center justify-center rounded-xl text-sm font-bold text-brand" style={{ backgroundColor: 'color-mix(in srgb, var(--brand) 10%, white)' }} aria-hidden="true">
                  {s.name.slice(0, 2).toLocaleUpperCase(lang)}
                </span>
                <span className="mt-4 block text-sm font-semibold leading-5 text-slate-900">{s.name}</span>
                {s.summary && <span className="mt-2 line-clamp-2 block text-xs leading-5 text-slate-500">{s.summary}</span>}
                <span className="mt-4 inline-flex items-center gap-1 text-xs font-semibold text-brand">{t(lang, 'read_more')} <span aria-hidden="true">→</span></span>
              </Link>
            ))}
          </div>
        ) : (
          <p className="rounded-2xl border border-slate-200 bg-white p-5 text-sm text-slate-600">{t(lang, 'no_services_yet')}</p>
        )}
      </section>

      {(consulate.consul_message || consulate.mission) && (
        <section className="border-y border-slate-200/70 bg-white py-14 sm:py-16">
          <div className="mx-auto grid max-w-7xl gap-6 px-5 sm:px-8 md:grid-cols-2">
            {consulate.consul_message && (
              <blockquote className="rounded-3xl bg-slate-50 p-6 sm:p-8">
                <span aria-hidden="true" className="text-4xl leading-none text-brand">“</span>
                <h2 className="mb-4 text-xl font-bold text-slate-900">{t(lang, 'consul_word')}</h2>
                <Prose text={consulate.consul_message} className="text-slate-600" />
                {consulate.consul_name && <footer className="mt-5 border-s-2 ps-3 text-sm font-semibold text-brand" style={{ borderColor: 'var(--brand-2)' }}>{consulate.consul_name}</footer>}
              </blockquote>
            )}
            {consulate.mission && (
              <div className="rounded-3xl p-6 sm:p-8">
                <p className="text-xs font-bold uppercase tracking-[0.18em] text-brand">{t(lang, 'about_consulate')}</p>
                <h2 className="mb-4 mt-2 text-2xl font-bold tracking-tight text-slate-900">{t(lang, 'mission')}</h2>
                <Prose text={consulate.mission} className="text-slate-600" />
              </div>
            )}
          </div>
        </section>
      )}

      <section className="mx-auto w-full max-w-7xl px-5 py-14 sm:px-8 sm:py-16">
        <div className="mb-7">
          <p className="text-xs font-bold uppercase tracking-[0.18em] text-brand">{t(lang, 'practical_info')}</p>
          <h2 className="mt-2 text-2xl font-bold tracking-tight text-slate-900 sm:text-3xl">{t(lang, 'find_us')}</h2>
        </div>
        <div className="grid gap-5 lg:grid-cols-2">
          <div className="grid gap-4 sm:grid-cols-2">
            {consulate.address && (
              <div className="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <span className="flex h-10 w-10 items-center justify-center rounded-xl bg-slate-100 text-sm font-bold text-brand" aria-hidden="true">01</span>
                <h3 className="mt-4 font-semibold text-slate-900">{t(lang, 'address')}</h3>
                <p className="mt-2 whitespace-pre-line text-sm leading-6 text-slate-600">{consulate.address}</p>
              </div>
            )}
            {consulate.opening_hours && (
              <div className="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <span className="flex h-10 w-10 items-center justify-center rounded-xl bg-slate-100 text-sm font-bold text-brand" aria-hidden="true">02</span>
                <h3 className="mt-4 font-semibold text-slate-900">{t(lang, 'hours')}</h3>
                <p className="mt-2 whitespace-pre-line text-sm leading-6 text-slate-600">{consulate.opening_hours}</p>
              </div>
            )}
            {(consulate.phone || consulate.email || consulate.whatsapp) && (
              <div className="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:col-span-2">
                <span className="flex h-10 w-10 items-center justify-center rounded-xl bg-slate-100 text-sm font-bold text-brand" aria-hidden="true">@</span>
                <h3 className="mt-4 font-semibold text-slate-900">{t(lang, 'contacts')}</h3>
                <div className="mt-2 flex flex-wrap gap-x-5 gap-y-2 text-sm">
                  {consulate.phone && <a dir="ltr" className="text-slate-600 hover:text-brand hover:underline" href={`tel:${consulate.phone.replace(/[^\d+]/g, '')}`}>{consulate.phone}</a>}
                  {consulate.email && <a className="text-brand hover:underline" href={`mailto:${consulate.email}`}>{consulate.email}</a>}
                  {consulate.whatsapp && <a className="font-semibold text-green-700 hover:underline" target="_blank" rel="noopener noreferrer" href={`https://wa.me/${consulate.whatsapp}`}>{t(lang, 'whatsapp')}</a>}
                </div>
              </div>
            )}
            {!consulate.address && !consulate.opening_hours && !consulate.phone && !consulate.email && !consulate.whatsapp && (
              <p className="rounded-2xl border border-slate-200 bg-white p-5 text-sm text-slate-600 sm:col-span-2">{t(lang, 'contact_info_unavailable')}</p>
            )}
          </div>
          <OsmMap consulate={consulate} lang={lang} />
        </div>
      </section>

      <section className="bg-white py-14 sm:py-16">
        <div className="mx-auto max-w-7xl px-5 sm:px-8">
          <div className="mb-7 flex items-end justify-between gap-4">
            <div>
              <p className="text-xs font-bold uppercase tracking-[0.18em] text-brand">{t(lang, 'news')}</p>
              <h2 className="mt-2 text-2xl font-bold tracking-tight text-slate-900 sm:text-3xl">{t(lang, 'latest_news')}</h2>
            </div>
            <Link href={`${base}/news`} className="shrink-0 text-sm font-semibold text-brand hover:underline">{t(lang, 'see_all')} →</Link>
          </div>
          {newsResult.status === 'rejected' ? (
            <p role="status" className="rounded-2xl border border-amber-200 bg-amber-50 p-5 text-sm text-amber-900">{t(lang, 'news_unavailable')}</p>
          ) : news.length > 0 ? (
            <div className="grid gap-4 md:grid-cols-3">
              {news.map((n) => (
                <Link key={n.slug} href={`${base}/news/${n.slug}`} className="group rounded-2xl border border-slate-200 bg-slate-50 p-5 transition hover:-translate-y-1 hover:border-slate-300 hover:bg-white hover:shadow-lg hover:shadow-slate-900/[0.06]">
                  <time className="text-xs font-semibold uppercase tracking-wide text-brand" dateTime={n.published_at ?? undefined}>
                    {n.published_at ? new Date(n.published_at).toLocaleDateString(lang === 'ar' ? 'ar' : lang === 'en' ? 'en-GB' : 'fr-FR') : ''}
                  </time>
                  <h3 className="mt-3 text-lg font-bold leading-6 text-slate-900">{n.title}</h3>
                  {n.excerpt && <p className="mt-2 line-clamp-3 text-sm leading-6 text-slate-600">{n.excerpt}</p>}
                  <span className="mt-4 inline-flex items-center gap-1 text-sm font-semibold text-brand">{t(lang, 'read_more')} <span aria-hidden="true" className="transition group-hover:translate-x-1">→</span></span>
                </Link>
              ))}
            </div>
          ) : (
            <p className="rounded-2xl border border-slate-200 bg-slate-50 p-5 text-sm text-slate-600">{t(lang, 'no_news_yet')}</p>
          )}
        </div>
      </section>
    </>
  );
}
