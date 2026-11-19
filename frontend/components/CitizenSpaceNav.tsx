'use client';

import Link from 'next/link';
import { usePathname, useRouter } from 'next/navigation';
import { useEffect, useState } from 'react';
import { bff } from '@/lib/bff';
import { t } from '@/lib/i18n';
import type { Lang } from '@/lib/types';

/** Navigation de l'espace citoyen : menu latéral sur ordinateur, bandeau défilant sur téléphone. */
export default function CitizenSpaceNav({ consulateSlug, lang, base }: { consulateSlug: string; lang: Lang; base: string }) {
  const pathname = usePathname();
  const router = useRouter();
  // « Espace du bureau » n'est proposé qu'aux membres du bureau en exercice.
  const [inOffice, setInOffice] = useState(false);

  useEffect(() => {
    bff<{ association?: { current?: unknown[] } }>(consulateSlug, 'citizen/me', { lang })
      .then((r) => setInOffice((r.association?.current?.length ?? 0) > 0))
      .catch(() => setInOffice(false));
  }, [consulateSlug, lang]);

  const links = [
    { href: `${base}/compte`, label: t(lang, 'account_nav_home'), exact: true },
    { href: `${base}/compte/rendez-vous`, label: t(lang, 'account_nav_appointments') },
    { href: `${base}/compte/demandes`, label: t(lang, 'pre_page_title') },
    { href: `${base}/compte/messages`, label: t(lang, 'msg_page_title') },
    { href: `${base}/compte/elections`, label: t(lang, 'elec_nav') },
    { href: `${base}/compte/finances`, label: t(lang, 'ledger_page_title') },
    ...(inOffice ? [{ href: `${base}/compte/bureau`, label: t(lang, 'board_page_title') }] : []),
    { href: `${base}/compte/chronologie`, label: t(lang, 'timeline_page_title') },
    { href: `${base}/compte/famille`, label: t(lang, 'family_title') },
    { href: `${base}/compte/signalements`, label: t(lang, 'report_title') },
    { href: `${base}/compte/profil`, label: t(lang, 'account_nav_profile') },
  ];

  const isActive = (href: string, exact?: boolean) =>
    exact ? pathname === href : pathname === href || pathname.startsWith(`${href}/`);

  async function logout() {
    try {
      await bff(consulateSlug, 'citizen/logout', { method: 'POST', lang });
    } catch {
      // Session déjà expirée : on quitte quand même l'espace.
    }
    router.push(`${base}/compte/connexion`);
  }

  return (
    <aside className="md:sticky md:top-6 md:self-start">
      <nav aria-label={t(lang, 'account_nav')} className="card overflow-x-auto p-2 md:p-3">
        <ul className="flex gap-1 md:flex-col">
          {links.map((l) => {
            const active = isActive(l.href, l.exact);
            return (
              <li key={l.href} className="shrink-0">
                <Link
                  href={l.href}
                  aria-current={active ? 'page' : undefined}
                  className={`block whitespace-nowrap rounded-lg px-3 py-2 text-sm font-semibold transition ${
                    active ? 'bg-brand text-white' : 'text-slate-700 hover:bg-slate-100'
                  }`}
                >
                  {l.label}
                </Link>
              </li>
            );
          })}
        </ul>
        <div className="mt-2 hidden border-t border-slate-200 pt-2 md:block">
          <button type="button" onClick={logout} className="w-full rounded-lg px-3 py-2 text-start text-sm font-semibold text-slate-600 hover:bg-slate-100">
            {t(lang, 'account_logout')}
          </button>
        </div>
      </nav>
    </aside>
  );
}
