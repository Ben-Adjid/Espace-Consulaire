import Link from 'next/link';
import { api, ApiError, API_URL } from '@/lib/api';
import { loadConsulate } from '@/lib/server';
import { t } from '@/lib/i18n';
import type { Association } from '@/lib/types';
import { pageTitle } from '@/lib/pageTitles';
export const instant = false;

export async function generateMetadata({ params }: { params: Promise<{ lang: string }> }) {
  const { lang } = await params;
  return { title: pageTitle(lang, 'Association des étudiants') };
}

/** Dates de mandat affichées telles que publiées (AAAA-MM-JJ), sans conversion de fuseau. */
function mandateText(start: string | null, end: string | null) {
  if (!start && !end) return null;
  return `${start ?? '…'} → ${end ?? '…'}`;
}

/** Association des étudiants et stagiaires tchadiens : informations publiques, bureau, activités et archives. */
export default async function AssociationPage({ params, searchParams }: { params: Promise<{ consulate: string; lang: string }>; searchParams: Promise<{ page?: string }> }) {
  const { consulate: slug, lang: rawLang } = await params;
  const { page } = await searchParams;
  const { consulate, lang } = await loadConsulate(slug, rawLang);
  const requested = page && /^\d+$/.test(page) ? Number(page) : 1;

  let association: Association | null = null;
  try {
    association = (await api<{ data: Association }>(`/${slug}/association?page=${requested}`, { lang, revalidate: 300 })).data;
  } catch (err) {
    if (!(err instanceof ApiError) || err.status !== 404) throw err;
  }

  const pages = association?.activities_page;

  // Registre financier public : solde et dernières écritures (aucun nom).
  type PublicLedger = { balance: number; entries: { kind_label: string; amount: number; label: string; occurred_on: string; kind: string }[] };
  let ledger: PublicLedger | null = null;
  if (association) {
    try {
      ledger = (await api<{ data: PublicLedger }>(`/${slug}/association/ledger`, { lang, revalidate: 300 })).data;
    } catch (err) {
      if (!(err instanceof ApiError) || err.status !== 404) throw err;
    }
  }
  const money = (n: number) => `${n.toLocaleString('fr-FR').replace(/ /g, ' ')} FCFA`;

  return (
    <>
      <section className="text-white" style={{ background: 'var(--brand)' }}>
        <div className="mx-auto max-w-4xl px-4 py-10">
          <h1 className="text-3xl font-bold">{association?.name ?? t(lang, 'association_title')}</h1>
          <p className="mt-2 text-white/90">{consulate.name}</p>
        </div>
      </section>

      <section className="mx-auto max-w-4xl space-y-8 px-4 py-10">
        {!association && <p className="card p-6 text-slate-600">{t(lang, 'association_none')}</p>}

        {association && (
          <>
            <div className="card space-y-3 p-6">
              <div className="flex flex-wrap items-center gap-2">
                {association.acronym && <span className="rounded-full bg-slate-100 px-3 py-1 text-sm font-semibold text-slate-700">{association.acronym}</span>}
                {association.is_recognized && (
                  <span className="rounded-full bg-green-100 px-3 py-1 text-sm font-semibold text-green-800">{t(lang, 'association_recognized')}</span>
                )}
              </div>
              {association.description && <p className="whitespace-pre-line text-slate-700">{association.description}</p>}
            </div>

            <div className="space-y-3">
              <h2 className="text-xl font-bold text-brand">{t(lang, 'association_bureau')}</h2>
              {association.officers.length === 0 ? (
                <p className="card p-4 text-slate-600">{t(lang, 'association_no_board')}</p>
              ) : (
                <ul className="grid gap-3 sm:grid-cols-2">
                  {association.officers.map((o) => (
                    <li key={`${o.role}-${o.full_name}`} className="card p-4">
                      <p className="text-xs font-semibold uppercase tracking-wide text-slate-500">{o.role_label}</p>
                      <p className="font-semibold text-slate-900">{o.full_name}</p>
                      {o.school && <p className="text-sm text-slate-600">{o.school}</p>}
                      {mandateText(o.mandate_start, o.mandate_end) && (
                        <p className="mt-2 text-xs text-slate-500" dir="ltr">
                          {t(lang, 'association_mandate')} : {mandateText(o.mandate_start, o.mandate_end)}
                        </p>
                      )}
                    </li>
                  ))}
                </ul>
              )}
            </div>

            {association.activities.length > 0 && (
              <div className="space-y-3">
                <h2 className="text-xl font-bold text-brand">{t(lang, 'association_activities')}</h2>
                <ul className="space-y-3">
                  {association.activities.map((a) => (
                    <li key={`${a.starts_on}-${a.title}`} className="card p-4">
                      <p className="text-xs font-semibold uppercase tracking-wide text-slate-500" dir="ltr">
                        {a.starts_on}{a.location ? ` · ${a.location}` : ''}
                      </p>
                      <p className="font-semibold text-slate-900">{a.title}</p>
                      {a.description && <p className="whitespace-pre-line text-sm text-slate-600">{a.description}</p>}
                    </li>
                  ))}
                </ul>
                {pages && pages.last_page > 1 && (
                  <nav className="flex items-center justify-between gap-3 text-sm" aria-label={t(lang, 'association_activities')}>
                    {pages.page > 1 ? (
                      <Link className="font-semibold text-brand hover:underline" href={`?page=${pages.page - 1}`}>← {t(lang, 'association_page_prev')}</Link>
                    ) : <span />}
                    <span className="text-slate-500">{t(lang, 'association_page_of').replace(':page', String(pages.page)).replace(':last', String(pages.last_page))}</span>
                    {pages.page < pages.last_page ? (
                      <Link className="font-semibold text-brand hover:underline" href={`?page=${pages.page + 1}`}>{t(lang, 'association_page_next')} →</Link>
                    ) : <span />}
                  </nav>
                )}
              </div>
            )}

            {ledger && (
              <div className="space-y-3">
                <div className="flex flex-wrap items-end justify-between gap-3">
                  <h2 className="text-xl font-bold text-brand">{t(lang, 'ledger_public')}</h2>
                  <a className="text-sm font-semibold text-brand hover:underline" href={`${API_URL}/${slug}/association/export.csv`}>CSV ↓</a>
                </div>
                <p className="card p-4 text-lg font-bold text-slate-900" dir="ltr">{t(lang, 'ledger_balance')} : {money(ledger.balance)}</p>
                {ledger.entries.length > 0 && (
                  <ul className="space-y-2">
                    {ledger.entries.map((e, i) => (
                      <li key={i} className="card flex flex-wrap items-center justify-between gap-2 p-3 text-sm">
                        <span><span className="text-slate-500">{e.occurred_on} · {e.kind_label}</span> — {e.label}</span>
                        <span className={`font-semibold ${e.kind === 'expense' ? 'text-red-700' : 'text-green-800'}`} dir="ltr">{e.kind === 'expense' ? '−' : '+'}{money(e.amount)}</span>
                      </li>
                    ))}
                  </ul>
                )}
              </div>
            )}

            {association.archives.length > 0 && (
              <div className="space-y-3">
                <h2 className="text-xl font-bold text-brand">{t(lang, 'association_archives')}</h2>
                <ul className="grid gap-3 sm:grid-cols-2">
                  {association.archives.map((p) => (
                    <li key={p.person_name} className="card p-4">
                      <p className="font-semibold text-slate-900">{p.person_name}</p>
                      {p.title && <p className="text-sm text-slate-600">{p.title}</p>}
                      {p.summary && <p className="mt-2 whitespace-pre-line text-sm text-slate-700">{p.summary}</p>}
                    </li>
                  ))}
                </ul>
              </div>
            )}

            {(association.email || association.phone) && (
              <div className="card space-y-1 p-6">
                <h2 className="mb-2 text-lg font-bold text-brand">{t(lang, 'association_contact')}</h2>
                {association.email && <p><a className="text-brand hover:underline" href={`mailto:${association.email}`}>{association.email}</a></p>}
                {association.phone && <p dir="ltr">{association.phone}</p>}
              </div>
            )}
          </>
        )}
      </section>
    </>
  );
}
