'use client';

import Link from 'next/link';
import { useEffect, useState } from 'react';
import { ApiError } from '@/lib/api';
import { bff } from '@/lib/bff';
import type { AppointmentView, Lang } from '@/lib/types';
import { t } from '@/lib/i18n';
import CitizenProfileEditor, { AssociationRoleCard, type ProfileData } from './CitizenProfileEditor';

type Profile = ProfileData;

export type CitizenSection = 'accueil' | 'rendez-vous' | 'profil';

/**
 * Pages de l'espace citoyen : accueil, rendez-vous, profil.
 * Charge le profil et les rendez-vous avec le cookie de session (le jeton n'est jamais lu par la page).
 */
export default function CitizenDashboard({ consulateSlug, lang, base, section }: { consulateSlug: string; lang: Lang; base: string; section: CitizenSection }) {
  const [ready, setReady] = useState(false);
  const [authed, setAuthed] = useState(false);
  const [profile, setProfile] = useState<Profile | null>(null);
  const [items, setItems] = useState<AppointmentView[]>([]);
  const [error, setError] = useState<string | null>(null);

  const locale = lang === 'ar' ? 'ar' : lang === 'en' ? 'en-GB' : 'fr-FR';

  useEffect(() => {
    Promise.all([
      bff<{ data: Profile }>(consulateSlug, 'citizen/me', { lang }),
      bff<{ data: AppointmentView[] }>(consulateSlug, 'citizen/appointments', { lang }),
    ])
      .then(([me, appts]) => {
        setProfile(me.data);
        setItems(appts.data);
        setAuthed(true);
      })
      .catch((err) => {
        if (err instanceof ApiError && err.status === 401) {
          setAuthed(false);
          return;
        }
        if (err instanceof ApiError && err.status === 403) {
          // Compte en attente ou refusé : on affiche le statut plutôt qu'une erreur.
          setAuthed(true);
          setError(err.message);
          return;
        }
        setError(t(lang, 'error'));
      })
      .finally(() => setReady(true));
  }, [consulateSlug, lang]);

  if (!ready) return <p className="text-slate-500">{t(lang, 'loading')}</p>;

  if (!authed) {
    return (
      <div className="card space-y-4 p-6 text-center">
        <p className="text-slate-700">{t(lang, 'account_need_account')}</p>
        <div className="flex flex-col justify-center gap-3 sm:flex-row">
          <Link href={`${base}/compte/connexion`} className="btn border border-slate-300 bg-white px-6 py-2.5 text-slate-700 hover:bg-slate-50">{t(lang, 'account_login_cta')}</Link>
          <Link href={`${base}/compte/inscription`} className="btn-primary px-6 py-2.5">{t(lang, 'account_register_cta')}</Link>
        </div>
      </div>
    );
  }

  const isActive = profile?.status === 'active';
  const upcoming = items.filter((a) => a.status !== 'cancelled' && a.status !== 'completed' && new Date(`${a.date}T23:59:59`) >= new Date());
  const past = items.filter((a) => !upcoming.includes(a));

  return (
    <div className="space-y-6">
      {error && <p role="alert" className="rounded-lg bg-red-50 p-3 text-sm text-red-700">{error}</p>}

      {profile && !isActive && (
        <section role="status" className={`rounded-xl border p-5 ${profile.status === 'rejected' ? 'border-red-200 bg-red-50 text-red-800' : 'border-amber-200 bg-amber-50 text-amber-800'}`}>
          <p className="text-sm font-bold">{profile.status_label}</p>
          {profile.status === 'pending' && <p className="mt-1 text-sm">{t(lang, 'account_pending_text')}</p>}
          {profile.status === 'rejected' && (
            <p className="mt-1 text-sm">{t(lang, 'account_rejected_text')}{profile.rejection_reason ? ` — ${profile.rejection_reason}` : ''}</p>
          )}
        </section>
      )}

      {section === 'accueil' && profile && (
        <>
          <p className="text-lg text-slate-700">
            {t(lang, 'hello')}, <span className="font-semibold text-slate-900">{profile.full_name}</span>
            <span className="ms-2 rounded-full bg-slate-100 px-2.5 py-0.5 align-middle text-xs font-semibold text-slate-600">{profile.status_label}</span>
          </p>
          {/* Prochain rendez-vous, en vedette */}
          <section className="card border-l-4 border-l-brand p-5 sm:p-6">
            <p className="text-xs font-semibold uppercase tracking-wide text-slate-500">{t(lang, 'account_upcoming_next')}</p>
            {upcoming[0] ? (
              <div className="mt-2 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div className="min-w-0">
                  <p className="text-lg font-bold text-slate-900">{upcoming[0].service ?? '—'}</p>
                  <p className="text-sm text-slate-600">
                    {new Date(`${upcoming[0].date}T12:00:00`).toLocaleDateString(locale, { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' })}
                    {' · '}<span dir="ltr">{upcoming[0].start_time} – {upcoming[0].end_time}</span>
                  </p>
                  <p className="mt-1 text-xs text-slate-500" dir="ltr">{upcoming[0].reference}</p>
                </div>
                <Link href={`${base}/appointment/confirmation/${upcoming[0].reference}`} className="btn-primary px-5 py-2.5 text-center">{t(lang, 'account_manage')}</Link>
              </div>
            ) : (
              <p className="mt-2 text-slate-600">{t(lang, 'account_no_appointments')}</p>
            )}
          </section>

          {/* Fonction associative, si le citoyen en exerce une */}
          {profile?.association && profile.association.current.length > 0 && (
            <AssociationRoleCard lang={lang} status={profile.association} />
          )}

          {/* Actions rapides */}
          {isActive && (
            <section className="grid gap-4 sm:grid-cols-2">
              <Link href={`${base}/appointment`} className="card flex items-center justify-between p-5 transition hover:shadow-md">
                <span className="font-semibold text-slate-900">{t(lang, 'account_book_new')}</span>
                <span aria-hidden="true" className="text-xl text-brand">→</span>
              </Link>
              <Link href={`${base}/compte/demandes`} className="card flex items-center justify-between p-5 transition hover:shadow-md">
                <span className="font-semibold text-slate-900">{t(lang, 'pre_page_title')}</span>
                <span aria-hidden="true" className="text-xl text-brand">→</span>
              </Link>
            </section>
          )}
        </>
      )}

      {section === 'rendez-vous' && (
        <>
          {isActive && (
            <div>
              <Link href={`${base}/appointment`} className="btn-primary inline-block px-5 py-2.5">{t(lang, 'account_book_new')}</Link>
            </div>
          )}
          {items.length === 0 && <p className="card p-5 text-slate-600">{t(lang, 'account_no_appointments')}</p>}
          {upcoming.map((a) => (
            <AppointmentRow key={a.reference} a={a} base={base} lang={lang} locale={locale} />
          ))}
          {past.length > 0 && <p className="pt-2 text-xs font-semibold uppercase tracking-wide text-slate-500">{t(lang, 'account_past')}</p>}
          {past.map((a) => (
            <AppointmentRow key={a.reference} a={a} base={base} lang={lang} locale={locale} muted />
          ))}
        </>
      )}

      {section === 'profil' && profile && (
        <>
          <section className="card p-5 sm:p-6">
            <dl className="grid gap-5 text-sm sm:grid-cols-2">
              <div>
                <dt className="text-xs uppercase tracking-wide text-slate-500">{t(lang, 'name')}</dt>
                <dd className="font-semibold text-slate-900">{profile.full_name}</dd>
              </div>
              <div>
                <dt className="text-xs uppercase tracking-wide text-slate-500">{t(lang, 'residence_country')}</dt>
                <dd className="font-semibold text-slate-900">{profile.residence_country}</dd>
              </div>
            </dl>
            <p className="mt-6 rounded-lg bg-slate-50 p-3 text-sm text-slate-600">{t(lang, 'account_profile_note')}</p>
          </section>
          <CitizenProfileEditor consulateSlug={consulateSlug} lang={lang} profile={profile} onChange={setProfile} />
        </>
      )}
    </div>
  );
}

function AppointmentRow({ a, base, lang, locale, muted }: { a: AppointmentView; base: string; lang: Lang; locale: string; muted?: boolean }) {
  return (
    <article className={`card flex flex-col gap-3 p-4 sm:flex-row sm:items-center sm:justify-between ${muted ? 'opacity-70' : ''}`}>
      <div className="min-w-0">
        <p className="font-semibold text-slate-900">{a.service ?? '—'}</p>
        <p className="text-sm text-slate-600">
          {new Date(`${a.date}T12:00:00`).toLocaleDateString(locale, { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' })}
          {' · '}<span dir="ltr">{a.start_time} – {a.end_time}</span>
        </p>
        <p className="mt-1 text-xs text-slate-500">{a.status_label} · <span dir="ltr">{a.reference}</span></p>
        {a.on_behalf_name && <p className="mt-1 text-xs text-slate-500">{t(lang, 'on_behalf_for')} {a.on_behalf_name}</p>}
        <div className="mt-2 flex flex-wrap gap-2">
          {a.ticket && <span className="rounded-full bg-sky-100 px-2.5 py-0.5 text-xs font-bold text-sky-800" dir="ltr">{t(lang, 'ticket')} {a.ticket}</span>}
          {a.case_status_label && (
            <span className={`rounded-full px-2.5 py-0.5 text-xs font-bold ${a.case_status === 'ready' ? 'bg-green-100 text-green-800' : a.case_status === 'rejected' ? 'bg-red-100 text-red-800' : 'bg-slate-100 text-slate-700'}`}>
              {t(lang, 'case_status')} : {a.case_status_label}
            </span>
          )}
        </div>
        {a.case_note && <p className="mt-1 text-xs text-slate-600">{a.case_note}</p>}
      </div>
      <Link href={`${base}/appointment/confirmation/${a.reference}`} className="btn border border-slate-300 bg-white px-4 py-2 text-sm text-slate-700 hover:bg-slate-50">
        {t(lang, 'account_manage')}
      </Link>
    </article>
  );
}
