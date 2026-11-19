import { cookies } from 'next/headers';
import { NextRequest, NextResponse } from 'next/server';
import { API_URL } from '@/lib/api';

/**
 * Proxy « backend for frontend » : le navigateur appelle cette route (même site), qui relaie vers l'API Laravel.
 * Le jeton du compte citoyen vit dans un cookie httpOnly : il n'est jamais lisible par le JavaScript de la page.
 */

const SLUG = /^[a-z0-9][a-z0-9-]*$/;
const SEGMENT = /^[a-z0-9-]+$/;
const MAX_AGE = 60 * 60 * 24 * 30; // 30 jours

const cookieName = (slug: string) => `citizen_session_${slug}`;

type Ctx = { params: Promise<{ consulate: string; path: string[] }> };

async function relay(req: NextRequest, ctx: Ctx): Promise<NextResponse> {
  const { consulate, path } = await ctx.params;

  // Seuls les chemins de l'API sont relayés : identifiant de consulat et segments alphanumériques.
  if (!SLUG.test(consulate) || path.some((s) => !SEGMENT.test(s))) {
    return NextResponse.json({ message: 'Requête refusée.' }, { status: 400 });
  }

  const route = path.join('/');
  const target = new URL(`${API_URL}/${consulate}/${route}`);
  req.nextUrl.searchParams.forEach((value, key) => target.searchParams.set(key, value));

  const headers = new Headers({ Accept: 'application/json' });
  const contentType = req.headers.get('content-type');
  if (contentType) headers.set('Content-Type', contentType);

  // Un en-tête Authorization explicite (lien personnel) l'emporte sur le cookie de session.
  const explicitAuth = req.headers.get('authorization');
  const session = (await cookies()).get(cookieName(consulate))?.value;
  if (explicitAuth) headers.set('Authorization', explicitAuth);
  else if (session) headers.set('Authorization', `Bearer ${session}`);

  // Corps transmis tel quel : JSON ou téléversement multipart (fichiers binaires non altérés).
  const body = req.method === 'GET' || req.method === 'HEAD' ? undefined : await req.arrayBuffer();

  let upstream: Response;
  try {
    upstream = await fetch(target, { method: req.method, headers, body, cache: 'no-store' });
  } catch {
    return NextResponse.json({ message: 'Le service est momentanément indisponible.' }, { status: 503 });
  }

  const text = await upstream.text();
  let payload: Record<string, unknown> | null = null;
  try {
    payload = JSON.parse(text);
  } catch {
    payload = null;
  }

  // Cookie « Secure » seulement si le site est réellement servi en HTTPS (derrière un proxy : X-Forwarded-Proto).
  // Sinon le navigateur refuse le cookie en HTTP hors localhost, et la connexion semble réussie sans session.
  const forwardedProto = req.headers.get('x-forwarded-proto')?.split(',')[0]?.trim();
  const secure = (forwardedProto ?? req.nextUrl.protocol.replace(':', '')) === 'https';
  const cookieOptions = { httpOnly: true, sameSite: 'lax' as const, secure, path: '/' };

  // Connexion réussie : le jeton passe dans le cookie, il n'est pas renvoyé au navigateur.
  if (route === 'citizen/login' && upstream.ok && typeof payload?.token === 'string') {
    const { token, ...rest } = payload;
    const res = NextResponse.json(rest, { status: upstream.status });
    res.cookies.set(cookieName(consulate), token as string, { ...cookieOptions, maxAge: MAX_AGE });
    return res;
  }

  const res = new NextResponse(text, {
    status: upstream.status,
    headers: { 'Content-Type': upstream.headers.get('content-type') ?? 'application/json' },
  });

  // Déconnexion, ou jeton expiré/révoqué : on retire le cookie.
  if (route === 'citizen/logout' || (upstream.status === 401 && session && !explicitAuth)) {
    res.cookies.set(cookieName(consulate), '', { ...cookieOptions, maxAge: 0 });
  }

  return res;
}

export { relay as GET, relay as POST, relay as PUT, relay as PATCH, relay as DELETE };
