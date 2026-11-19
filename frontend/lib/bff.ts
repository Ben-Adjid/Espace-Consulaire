// Appels vers le proxy /api/bff (même site). Aucun jeton n'est lu ni stocké dans le navigateur.
import { ApiError } from '@/lib/api';
import type { Lang } from '@/lib/types';

interface BffOptions {
  method?: 'GET' | 'POST' | 'PUT' | 'PATCH' | 'DELETE';
  body?: unknown;
  lang?: Lang;
  /** Lien personnel (jeton du rendez-vous) : envoyé en Authorization, prioritaire sur le cookie. */
  token?: string;
}

function url(slug: string, path: string, lang?: Lang): string {
  return `/api/bff/${slug}/${path}${lang ? `?lang=${lang}` : ''}`;
}

/** Réponse brute (utile pour les fichiers .ics). */
export async function bffRaw(slug: string, path: string, opts: BffOptions = {}): Promise<Response> {
  const headers: Record<string, string> = { Accept: 'application/json' };
  if (opts.body !== undefined) headers['Content-Type'] = 'application/json';
  if (opts.token) headers.Authorization = `Bearer ${opts.token}`;
  return fetch(url(slug, path, opts.lang), {
    method: opts.method ?? 'GET',
    headers,
    body: opts.body !== undefined ? JSON.stringify(opts.body) : undefined,
    credentials: 'same-origin',
    cache: 'no-store',
  });
}

/** Appel JSON via le proxy. Lève une ApiError (avec le corps de la réponse) en cas d'erreur. */
export async function bff<T>(slug: string, path: string, opts: BffOptions = {}): Promise<T> {
  const res = await bffRaw(slug, path, opts);
  const text = await res.text();
  let json: unknown = null;
  try {
    json = JSON.parse(text);
  } catch {
    json = null;
  }
  if (!res.ok) {
    const message = (json as { message?: unknown } | null)?.message;
    throw new ApiError(res.status, typeof message === 'string' ? message : `Erreur ${res.status}`, json);
  }
  return json as T;
}
