import { notFound } from 'next/navigation';
import { api, ApiError } from '@/lib/api';
import { loadConsulate } from '@/lib/server';
import type { PageContent } from '@/lib/types';
import Prose from '@/components/Prose';

/** Pages libres : mentions légales, confidentialité, démo non officielle… */
export default async function StaticPage({ params }: { params: Promise<{ consulate: string; lang: string; slug: string }> }) {
  const { consulate: slug, lang: rawLang, slug: pageSlug } = await params;
  const { lang } = await loadConsulate(slug, rawLang);

  let page: PageContent;
  try {
    page = (await api<{ data: PageContent }>(`/${slug}/pages/${pageSlug}`, { lang, revalidate: 300 })).data;
  } catch (err) {
    if (err instanceof ApiError && err.status === 404) notFound();
    throw err;
  }

  return (
    <article className="mx-auto max-w-3xl px-4 py-10">
      <h1 className="mb-6 text-3xl font-extrabold text-brand">{page.title}</h1>
      <div className="card p-6 text-slate-700"><Prose text={page.body} /></div>
    </article>
  );
}
