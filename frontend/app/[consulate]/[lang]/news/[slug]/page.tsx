import Link from 'next/link';
import { notFound } from 'next/navigation';
import { api, ApiError } from '@/lib/api';
import { baseOf, loadConsulate } from '@/lib/server';
import { t } from '@/lib/i18n';
import type { NewsItem } from '@/lib/types';
import Prose from '@/components/Prose';
export const instant = false;

export default async function NewsDetail({ params }: { params: Promise<{ consulate: string; lang: string; slug: string }> }) {
  const { consulate: slug, lang: rawLang, slug: newsSlug } = await params;
  const { lang } = await loadConsulate(slug, rawLang);
  const base = baseOf(slug, lang);

  let item: NewsItem;
  try {
    item = (await api<{ data: NewsItem }>(`/${slug}/news/${newsSlug}`, { lang, revalidate: 120 })).data;
  } catch (err) {
    if (err instanceof ApiError && err.status === 404) notFound();
    throw err;
  }

  return (
    <article className="mx-auto max-w-3xl px-4 py-10">
      <nav className="mb-4 text-sm"><Link className="text-brand hover:underline" href={`${base}/news`}>← {t(lang, 'news')}</Link></nav>
      <time className="text-sm font-semibold uppercase" style={{ color: 'var(--brand-3)' }}>
        {item.published_at ? new Date(item.published_at).toLocaleDateString(lang === 'ar' ? 'ar' : lang === 'en' ? 'en-GB' : 'fr-FR') : ''}
      </time>
      <h1 className="mt-2 text-3xl font-extrabold text-slate-900">{item.title}</h1>
      {item.image_url && (
        // eslint-disable-next-line @next/next/no-img-element
        <img src={item.image_url} alt="" className="mt-6 w-full rounded-xl object-cover" />
      )}
      {item.excerpt && <p className="mt-6 text-lg font-medium text-slate-700">{item.excerpt}</p>}
      <div className="mt-6 text-slate-700"><Prose text={item.body} /></div>
    </article>
  );
}
