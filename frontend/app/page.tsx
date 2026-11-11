import Link from 'next/link';
import type { Consulate } from '@/lib/types';
import { api } from '@/lib/api';
import Logo from '@/components/Logo';

export const metadata = { title: 'Choisissez un consulat' };

export default async function PlatformHome() {
  let consulates: Consulate[] = [];
  try {
    consulates = (await api<{ data: Consulate[] }>('/consulates', { revalidate: 300 })).data;
  } catch {
    consulates = [];
  }

  return (
    <div className="flex flex-1 flex-col">
      <div role="note" className="bg-amber-300 px-3 py-1.5 text-center text-xs font-semibold text-slate-900 sm:text-sm">
        ⚠ Démo / Maquette non officielle — données fictives, aucun logo officiel.
      </div>

      <section className="bg-slate-900 px-4 py-16 text-center text-white">
        <h1 className="mx-auto max-w-3xl text-3xl font-bold sm:text-4xl">Plateforme multi-consulats</h1>
        <p className="mx-auto mt-4 max-w-2xl text-slate-300">
          Une seule plateforme pour digitaliser plusieurs consulats africains : services, rendez-vous en ligne, actualités et contact.
        </p>
      </section>

      <section className="mx-auto w-full max-w-5xl px-4 py-12">
        <h2 className="mb-6 text-center text-2xl font-bold text-slate-900">Choisissez un consulat</h2>
        <div className="grid gap-6 sm:grid-cols-2">
          {consulates.map((c) => (
            <Link
              key={c.slug}
              href={`/${c.slug}/fr`}
              className="card block overflow-hidden transition hover:-translate-y-0.5 hover:shadow-md"
              style={{ ['--brand' as string]: c.theme.primary, ['--brand-2' as string]: c.theme.secondary, ['--brand-3' as string]: c.theme.accent }}
            >
              <div className="h-2" style={{ background: `linear-gradient(90deg, ${c.theme.primary} 33.3%, ${c.theme.secondary} 33.3% 66.6%, ${c.theme.accent} 66.6%)` }} />
              <div className="flex items-center gap-4 p-6">
                <Logo consulate={c} size={64} />
                <div>
                  <h3 className="text-lg font-bold text-slate-900">{c.name}</h3>
                  <p className="text-sm text-slate-500">{c.represented_country} → {c.host_country}</p>
                  <p className="mt-2 text-sm font-semibold" style={{ color: c.theme.primary }}>Voir le site →</p>
                </div>
              </div>
            </Link>
          ))}
          {consulates.length === 0 && (
            <p className="text-slate-600">L’API n’est pas joignable. Lancez le backend (php artisan serve) et vérifiez NEXT_PUBLIC_API_URL.</p>
          )}
        </div>
      </section>
    </div>
  );
}
