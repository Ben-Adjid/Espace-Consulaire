import Link from 'next/link';
import { api } from '@/lib/api';
import { baseOf, loadConsulate } from '@/lib/server';
import { t } from '@/lib/i18n';
import type { NewsItem, Paginated } from '@/lib/types';
export const instant = false;

export const metadata = { title: 'Actualités' };

export default async function NewsPage({ params, searchParams }: { params: Promise<{ consulate: string; lang: string }>; searchParams: Promise<{ page?: string }> }) {
  const { consulate: slug, lang: rawLang } = await params;
  const { page } = await searchParams;
  const { lang } = await loadConsulate(slug, rawLang);
  const base = baseOf(slug, lang);
  const current = Math.max(1, Number(page) || 1);

  const res = await api<Paginated<NewsItem>>(`/${slug}/news`, { lang, query: { page: current, per_page: 9 }, revalidate: 120 });

  return (
    <>
      <section className="text-white" style={{ background: 'var(--brand)' }}>
        <div className="mx-auto max-w-6xl px-4 py-10"><h1 className="text-3xl font-bold">{t(lang, 'news')}</h1></div>
      </section>
      <section className="mx-auto max-w-6xl px-4 py-10">
        <div className="grid gap-5 md:grid-cols-3">
          {res.data.map((n) => (
            <Link key={n.slug} href={`${base}/news/${n.slug}`} className="card flex flex-col p-5 transition hover:shadow-md">
              <time className="text-xs font-semibold uppercase" style={{ color: 'var(--brand-3)' }}>
                {n.published_at ? new Date(n.published_at).toLocaleDateString(lang === 'ar' ? 'ar' : lang === 'en' ? 'en-GB' : 'fr-FR') : ''}
              </time>
              <h2 className="mt-1 text-lg font-bold text-slate-900">{n.title}</h2>
              <p className="mt-2 text-sm text-slate-600">{n.excerpt}</p>
            </Link>
          ))}
        </div>
        {res.data.length === 0 && <p className="text-slate-500">{t(lang, 'no_result')}</p>}
        <nav className="mt-8 flex items-center justify-between" aria-label="pagination">
          {current > 1 ? <Link className="btn-outline" href={`${base}/news?page=${current - 1}`}>← {t(lang, 'previous')}</Link> : <span />}
          <span className="text-sm text-slate-500">{t(lang, 'page')} {res.meta.current_page} / {res.meta.last_page}</span>
          {res.links.next ? <Link className="btn-outline" href={`${base}/news?page=${current + 1}`}>{t(lang, 'next')} →</Link> : <span />}
        </nav>
      </section>
    </>
  );
}
