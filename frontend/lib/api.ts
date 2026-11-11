// Client HTTP de l'API Laravel (dossier /backend). Utilisable côté serveur (RSC) et navigateur.

export const API_URL = (process.env.NEXT_PUBLIC_API_URL ?? 'http://127.0.0.1:8000/api/v1').replace(/\/$/, '');

export class ApiError extends Error {
  constructor(public status: number, message: string) {
    super(message);
  }
}

type Query = Record<string, string | number | undefined>;

interface Options {
  lang?: string;
  method?: 'GET' | 'POST';
  body?: unknown;
  token?: string;
  query?: Query;
  /** Secondes de cache côté serveur (ISR). Ignoré si `noStore` est vrai. */
  revalidate?: number;
  noStore?: boolean;
}

function buildUrl(path: string, query: Query = {}, lang?: string): string {
  const params = new URLSearchParams();
  if (lang) params.set('lang', lang);
  for (const [k, v] of Object.entries(query)) {
    if (v !== undefined && v !== '') params.set(k, String(v));
  }
  const qs = params.toString();
  return `${API_URL}${path}${qs ? `?${qs}` : ''}`;
}

/** Appel JSON. Lève une ApiError avec le message renvoyé par l'API. */
export async function api<T>(path: string, opts: Options = {}): Promise<T> {
  const headers: Record<string, string> = { Accept: 'application/json' };
  if (opts.body !== undefined) headers['Content-Type'] = 'application/json';
  if (opts.token) headers.Authorization = `Bearer ${opts.token}`;

  const init: RequestInit & { next?: { revalidate: number } } = {
    method: opts.method ?? 'GET',
    headers,
    body: opts.body !== undefined ? JSON.stringify(opts.body) : undefined,
    cache: opts.noStore || opts.method === 'POST' ? 'no-store' : 'force-cache',
  };
  if (!opts.noStore && opts.revalidate && (opts.method ?? 'GET') === 'GET') {
    init.cache = 'force-cache';
    init.next = { revalidate: opts.revalidate };
  }

  const res = await fetch(buildUrl(path, opts.query, opts.lang), init);

  if (!res.ok) {
    let message = `Erreur ${res.status}`;
    try {
      const json = await res.json();
      if (typeof json?.message === 'string') message = json.message;
    } catch {
      // Réponse non JSON : on garde le message générique.
    }
    throw new ApiError(res.status, message);
  }

  return (await res.json()) as T;
}

/** Téléchargement binaire (fichier .ics) avec jeton dans l'en-tête, sans le mettre dans l'URL. */
export async function downloadWithToken(path: string, token: string, filename: string): Promise<void> {
  const res = await fetch(buildUrl(path), { headers: { Authorization: `Bearer ${token}` }, cache: 'no-store' });
  if (!res.ok) throw new ApiError(res.status, 'Téléchargement impossible');
  const blob = await res.blob();
  const url = URL.createObjectURL(blob);
  const a = document.createElement('a');
  a.href = url;
  a.download = filename;
  a.click();
  URL.revokeObjectURL(url);
}
