// Chargement des données côté serveur (RSC), partagé par les layouts et les pages.
import { notFound } from 'next/navigation';
import { api, ApiError } from './api';
import { isLang } from './i18n';
import type { Consulate, Lang, PageContent } from './types';

/** Pages légales affichées dans le pied de page (si publiées pour le consulat). */
export const LEGAL_SLUGS = ['mentions-legales', 'confidentialite', 'demo-non-officielle'];

/** Charge le consulat et valide la langue de l'URL. 404 si inconnu ou langue non proposée. */
export async function loadConsulate(slug: string, lang: string): Promise<{ consulate: Consulate; lang: Lang }> {
  if (!isLang(lang)) notFound();

  let consulate: Consulate;
  try {
    consulate = (await api<{ data: Consulate }>(`/${slug}`, { lang, revalidate: 300 })).data;
  } catch (err) {
    if (err instanceof ApiError && err.status === 404) notFound();
    throw err;
  }

  if (!consulate.languages.includes(lang)) notFound();
  return { consulate, lang };
}

/** Pages légales publiées, en ignorant celles qui n'existent pas. */
export async function loadLegalPages(slug: string, lang: Lang): Promise<PageContent[]> {
  const found = await Promise.all(
    LEGAL_SLUGS.map((s) =>
      api<{ data: PageContent }>(`/${slug}/pages/${s}`, { lang, revalidate: 300 })
        .then((r) => r.data)
        .catch(() => null),
    ),
  );
  return found.filter((p): p is PageContent => p !== null);
}

/** Base des URL d'un consulat dans une langue : /tchad-senegal/fr */
export function baseOf(slug: string, lang: Lang): string {
  return `/${slug}/${lang}`;
}
