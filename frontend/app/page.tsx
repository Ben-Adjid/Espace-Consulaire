import Link from 'next/link';
import type { Consulate } from '@/lib/types';
import { api } from '@/lib/api';
import Logo from '@/components/Logo';

export const instant = false;

export const metadata = { title: 'Accueil' };

function ArrowIcon() {
  return (
    <svg aria-hidden="true" viewBox="0 0 20 20" fill="none" className="h-5 w-5">
      <path d="M4.167 10h11.666M10 4.167 15.833 10 10 15.833" stroke="currentColor" strokeWidth="1.7" strokeLinecap="round" strokeLinejoin="round" />
    </svg>
  );
}

export default async function PlatformHome() {
  let consulates: Consulate[] = [];
  try {
    consulates = (await api<{ data: Consulate[] }>('/consulates', { revalidate: 300 })).data;
  } catch {
    consulates = [];
  }

  return (
    <main className="flex flex-1 flex-col bg-[#f7f8f5] text-slate-900">

      <header className="border-b border-slate-200/80 bg-white">
        <div className="mx-auto flex max-w-7xl items-center justify-between px-5 py-4 sm:px-8">
          <Link href="/" aria-label="Accueil de la plateforme" className="flex items-center gap-3">
            <span className="flex h-10 w-10 items-center justify-center rounded-xl bg-[#123b36] text-white shadow-sm">
              <svg aria-hidden="true" viewBox="0 0 24 24" fill="none" className="h-5 w-5">
                <path d="M4 20h16M6.5 20V8.5L12 5l5.5 3.5V20M9.5 11h5M9.5 14.5h5M9.5 18h5" stroke="currentColor" strokeWidth="1.6" strokeLinecap="round" strokeLinejoin="round" />
              </svg>
            </span>
            <span>
              <span className="block text-sm font-bold tracking-tight text-slate-900 sm:text-base">Espace consulaire</span>
              <span className="hidden text-xs text-slate-500 sm:block">Vos démarches, en toute simplicité</span>
            </span>
          </Link>
          <a href="#consulats" className="inline-flex items-center gap-2 rounded-full bg-[#123b36] px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-[#1b5148] focus-visible:outline-offset-4">
            Accéder aux consulats
            <ArrowIcon />
          </a>
        </div>
      </header>

      <section className="relative isolate overflow-hidden bg-[#123b36] text-white">
        <div aria-hidden="true" className="absolute -right-36 -top-56 -z-10 h-[34rem] w-[34rem] rounded-full border border-white/10 sm:right-[-5rem] sm:top-[-18rem]">
          <div className="absolute inset-10 rounded-full border border-white/10" />
          <div className="absolute inset-24 rounded-full border border-white/10" />
          <div className="absolute inset-40 rounded-full border border-white/10" />
        </div>
        <div className="mx-auto grid max-w-7xl items-center gap-12 px-5 py-16 sm:px-8 sm:py-20 lg:grid-cols-[1.2fr_0.8fr] lg:gap-16 lg:py-24">
          <div className="max-w-2xl">
            <span className="inline-flex items-center gap-2 rounded-full border border-white/20 bg-white/10 px-3.5 py-1.5 text-xs font-semibold tracking-wide text-emerald-50 sm:text-sm">
              <span className="h-2 w-2 rounded-full bg-[#f3c969]" />
              Le service public, plus proche de vous
            </span>
            <h1 className="mt-6 text-4xl font-semibold leading-[1.08] tracking-tight sm:text-5xl lg:text-6xl">
              Vos démarches consulaires,
              <span className="mt-1 block font-serif italic font-normal text-[#f3c969]">en quelques clics.</span>
            </h1>
            <p className="mt-6 max-w-xl text-base leading-7 text-emerald-50/80 sm:text-lg sm:leading-8">
              Retrouvez les services de votre consulat et préparez vos démarches au même endroit, simplement et à votre rythme.
            </p>
            <a href="#consulats" className="mt-8 inline-flex items-center gap-3 rounded-xl bg-[#f3c969] px-5 py-3.5 text-sm font-bold text-[#183b32] shadow-lg shadow-black/10 transition hover:-translate-y-0.5 hover:bg-[#ffda7c]">
              Trouver mon consulat
              <ArrowIcon />
            </a>
            <div className="mt-10 flex flex-wrap items-center gap-x-6 gap-y-3 text-sm text-emerald-50/75">
              <span className="inline-flex items-center gap-2">
                <svg aria-hidden="true" viewBox="0 0 20 20" fill="none" className="h-5 w-5 text-[#f3c969]">
                  <path d="m5 10 3.2 3.2L15.5 6" stroke="currentColor" strokeWidth="1.8" strokeLinecap="round" strokeLinejoin="round" />
                </svg>
                Services et informations
              </span>
              <span className="inline-flex items-center gap-2">
                <svg aria-hidden="true" viewBox="0 0 20 20" fill="none" className="h-5 w-5 text-[#f3c969]">
                  <path d="M10 17s6-4.1 6-9a6 6 0 1 0-12 0c0 4.9 6 9 6 9Z" stroke="currentColor" strokeWidth="1.5" />
                  <circle cx="10" cy="8" r="2" stroke="currentColor" strokeWidth="1.5" />
                </svg>
                Accès par pays
              </span>
            </div>
          </div>

          <div className="relative mx-auto w-full max-w-md lg:ml-auto">
            <div aria-hidden="true" className="absolute -left-7 -top-7 h-24 w-24 rounded-full bg-[#f3c969]/15 blur-2xl" />
            <div className="relative overflow-hidden rounded-[1.75rem] border border-white/15 bg-white/[0.08] p-5 shadow-2xl shadow-black/20 backdrop-blur-sm sm:p-7">
              <div className="flex items-center justify-between border-b border-white/15 pb-5">
                <div>
                  <p className="text-xs font-medium uppercase tracking-[0.18em] text-emerald-50/60">Un espace unique</p>
                  <p className="mt-1 text-lg font-semibold">À votre service</p>
                </div>
                <span className="flex h-11 w-11 items-center justify-center rounded-2xl bg-[#f3c969] text-[#183b32]">
                  <svg aria-hidden="true" viewBox="0 0 24 24" fill="none" className="h-6 w-6">
                    <path d="M4 19.5h16M6 19.5V9l6-4 6 4v10.5M9.5 12h5M9.5 15.5h5" stroke="currentColor" strokeWidth="1.6" strokeLinecap="round" strokeLinejoin="round" />
                  </svg>
                </span>
              </div>
              <div className="space-y-3 py-5">
                {[
                  { number: '01', title: 'Choisissez votre consulat', subtitle: 'Un accès adapté à votre pays' },
                  { number: '02', title: 'Explorez les services', subtitle: 'Toutes les informations utiles' },
                  { number: '03', title: 'Préparez vos démarches', subtitle: 'À votre rythme, en ligne' },
                ].map((item) => (
                  <div key={item.number} className="flex items-center gap-4 rounded-2xl bg-white/[0.07] p-4">
                    <span className="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-white/10 text-xs font-bold text-[#f3c969]">{item.number}</span>
                    <span>
                      <span className="block text-sm font-semibold">{item.title}</span>
                      <span className="mt-0.5 block text-xs text-emerald-50/60">{item.subtitle}</span>
                    </span>
                  </div>
                ))}
              </div>
              <p className="border-t border-white/15 pt-4 text-xs leading-5 text-emerald-50/55">
                Les démarches et disponibilités varient selon votre consulat.
              </p>
            </div>
            <div className="absolute -bottom-5 -right-3 hidden rounded-2xl bg-white px-4 py-3 text-sm font-semibold text-[#123b36] shadow-xl sm:block">
              Un accompagnement plus simple
            </div>
          </div>
        </div>
      </section>

      <section id="consulats" className="mx-auto w-full max-w-7xl flex-1 px-5 py-16 sm:px-8 sm:py-20">
        <div className="mb-9 flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
          <div>
            <p className="text-xs font-bold uppercase tracking-[0.2em] text-[#39776a]">Commencer ici</p>
            <h2 className="mt-2 text-3xl font-semibold tracking-tight text-slate-900 sm:text-4xl">Choisissez votre consulat</h2>
            <p className="mt-3 max-w-2xl text-sm leading-6 text-slate-600 sm:text-base">
              Sélectionnez votre pays pour découvrir les services et informations qui vous concernent.
            </p>
          </div>
          {consulates.length > 0 && (
            <span className="inline-flex w-fit items-center gap-2 rounded-full border border-slate-200 bg-white px-3.5 py-2 text-xs font-medium text-slate-600">
              <span className="h-2 w-2 rounded-full bg-emerald-500" />
              {consulates.length} {consulates.length === 1 ? 'consulat disponible' : 'consulats disponibles'}
            </span>
          )}
        </div>

        {consulates.length > 0 ? (
          <div className="grid gap-5 md:grid-cols-2 xl:grid-cols-3">
            {consulates.map((c) => (
              <Link
                key={c.slug}
                href={`/${c.slug}/fr`}
                className="group relative overflow-hidden rounded-2xl border border-slate-200/80 bg-white p-5 shadow-sm transition duration-200 hover:-translate-y-1 hover:border-slate-300 hover:shadow-xl hover:shadow-slate-900/[0.07] focus-visible:outline-offset-4 sm:p-6"
              >
                <div
                  aria-hidden="true"
                  className="absolute inset-x-0 top-0 h-1"
                  style={{ background: `linear-gradient(90deg, ${c.theme.primary} 33.3%, ${c.theme.secondary} 33.3% 66.6%, ${c.theme.accent} 66.6%)` }}
                />
                <div className="flex items-start justify-between gap-4">
                  <div className="flex min-w-0 items-center gap-4">
                    <span className="rounded-2xl bg-slate-50 p-1.5 ring-1 ring-slate-100">
                      <Logo consulate={c} size={52} />
                    </span>
                    <div className="min-w-0">
                      <h3 className="truncate text-base font-bold text-slate-900 sm:text-lg">{c.name}</h3>
                      <p className="mt-1 text-sm text-slate-500">{c.represented_country}</p>
                    </div>
                  </div>
                  <span className="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-slate-50 text-slate-500 transition group-hover:bg-[#123b36] group-hover:text-white">
                    <ArrowIcon />
                  </span>
                </div>
                <div className="mt-5 flex items-center justify-between border-t border-slate-100 pt-4">
                  <span className="text-xs font-medium text-slate-500">Pays d’accueil</span>
                  <span className="text-sm font-semibold text-slate-700">{c.host_country}</span>
                </div>
              </Link>
            ))}
          </div>
        ) : (
          <div className="rounded-2xl border border-slate-200 bg-white px-6 py-10 text-center shadow-sm sm:px-10">
            <span className="mx-auto flex h-12 w-12 items-center justify-center rounded-2xl bg-amber-50 text-amber-700">
              <svg aria-hidden="true" viewBox="0 0 24 24" fill="none" className="h-6 w-6">
                <circle cx="12" cy="12" r="9" stroke="currentColor" strokeWidth="1.6" />
                <path d="M12 8v4.5M12 16h.01" stroke="currentColor" strokeWidth="1.8" strokeLinecap="round" />
              </svg>
            </span>
            <h3 className="mt-4 text-lg font-semibold text-slate-900">Les consulats ne sont pas disponibles pour le moment</h3>
            <p className="mx-auto mt-2 max-w-lg text-sm leading-6 text-slate-600">
              La liste n’a pas pu être chargée. Réessayez plus tard ou vérifiez que le serveur de la plateforme est démarré.
            </p>
          </div>
        )}
      </section>

      <footer className="border-t border-slate-200 bg-white">
        <div className="mx-auto flex max-w-7xl flex-col gap-2 px-5 py-6 text-xs text-slate-500 sm:flex-row sm:items-center sm:justify-between sm:px-8">
          <span className="font-semibold text-slate-700">Espace consulaire</span>
          <span>Démo non officielle · Les informations présentées sont fictives.</span>
        </div>
      </footer>
    </main>
  );
}
