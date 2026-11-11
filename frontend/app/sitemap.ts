import type { MetadataRoute } from 'next';
import { api } from '@/lib/api';
import type { Consulate, NewsItem, Service } from '@/lib/types';

const SITE = (process.env.NEXT_PUBLIC_SITE_URL ?? 'http://localhost:3000').replace(/\/$/, '');
const STATIC = ['', '/services', '/news', '/faq', '/contact', '/appointment'];

/** Sitemap : toutes les pages publiques de chaque consulat actif, dans chaque langue proposée. */
export default async function sitemap(): Promise<MetadataRoute.Sitemap> {
  const entries: MetadataRoute.Sitemap = [{ url: `${SITE}/`, changeFrequency: 'weekly', priority: 1 }];

  let consulates: Consulate[] = [];
  try {
    consulates = (await api<{ data: Consulate[] }>('/consulates', { revalidate: 3600 })).data;
  } catch {
    return entries;
  }

  for (const c of consulates) {
    for (const lang of c.languages) {
      const base = `${SITE}/${c.slug}/${lang}`;
      STATIC.forEach((path) => entries.push({ url: `${base}${path}`, changeFrequency: 'weekly', priority: path === '' ? 0.9 : 0.6 }));

      const [services, news] = await Promise.all([
        api<{ data: Service[] }>(`/${c.slug}/services`, { lang, revalidate: 3600 }).catch(() => ({ data: [] as Service[] })),
        api<{ data: NewsItem[] }>(`/${c.slug}/news`, { lang, query: { per_page: 30 }, revalidate: 3600 }).catch(() => ({ data: [] as NewsItem[] })),
      ]);
      services.data.forEach((s) => entries.push({ url: `${base}/services/${s.slug}`, changeFrequency: 'monthly', priority: 0.7 }));
      news.data.forEach((n) => entries.push({ url: `${base}/news/${n.slug}`, lastModified: n.published_at ?? undefined, changeFrequency: 'monthly', priority: 0.5 }));
    }
  }

  return entries;
}
