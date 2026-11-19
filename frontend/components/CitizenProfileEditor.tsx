'use client';

import { useEffect, useState } from 'react';
import { ApiError } from '@/lib/api';
import { bff } from '@/lib/bff';
import type { Lang } from '@/lib/types';
import { t } from '@/lib/i18n';

export interface AssociationRole {
  association: string | null;
  role: string;
  mandate_start: string | null;
  mandate_end: string | null;
}

export interface AssociationStatus {
  current: AssociationRole[];
  past: AssociationRole[];
  member: string | null;
}

export interface ProfileData {
  full_name: string;
  email: string;
  phone: string;
  pending_email: string | null;
  residence_country: string;
  nationality: string;
  status: 'pending' | 'active' | 'rejected';
  status_label: string;
  rejection_reason: string | null;
  association?: AssociationStatus;
}

/** Coordonnées modifiables (téléphone, email confirmé par lien) et rôle associatif. */
export default function CitizenProfileEditor({
  consulateSlug, lang, profile, onChange,
}: {
  consulateSlug: string;
  lang: Lang;
  profile: ProfileData;
  onChange: (p: ProfileData) => void;
}) {
  const [phone, setPhone] = useState(profile.phone ?? '');
  const [email, setEmail] = useState(profile.email);
  const [password, setPassword] = useState('');
  const [message, setMessage] = useState<string | null>(null);
  const [error, setError] = useState<string | null>(null);
  const [busy, setBusy] = useState(false);

  // Lien reçu par email : #change-email=TOKEN. Le jeton est retiré de l'adresse dès lecture.
  useEffect(() => {
    const match = window.location.hash.match(/change-email=([A-Za-z0-9]{48})/);
    if (!match) return;
    window.history.replaceState(null, '', window.location.pathname);
    bff<{ message: string; data: ProfileData }>(consulateSlug, 'citizen/email/confirm', {
      method: 'POST', lang, body: { token: match[1] },
    })
      .then((r) => {
        setMessage(r.message);
        onChange(r.data);
        setEmail(r.data.email);
      })
      .catch((err) => setError(err instanceof ApiError ? err.message : t(lang, 'error')));
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [consulateSlug, lang]);

  async function savePhone(e: React.FormEvent) {
    e.preventDefault();
    setError(null);
    setMessage(null);
    setBusy(true);
    try {
      const r = await bff<{ message: string; data: ProfileData }>(consulateSlug, 'citizen/profile', {
        method: 'PATCH', lang, body: { phone: phone.trim() || null },
      });
      setMessage(r.message);
      onChange(r.data);
    } catch (err) {
      setError(err instanceof ApiError ? err.message : t(lang, 'error'));
    } finally {
      setBusy(false);
    }
  }

  async function saveEmail(e: React.FormEvent) {
    e.preventDefault();
    setError(null);
    setMessage(null);
    setBusy(true);
    try {
      const r = await bff<{ message: string; data: ProfileData }>(consulateSlug, 'citizen/profile', {
        method: 'PATCH', lang, body: { email: email.trim(), current_password: password },
      });
      setMessage(r.message);
      setPassword('');
      onChange(r.data);
    } catch (err) {
      setError(err instanceof ApiError ? err.message : t(lang, 'error'));
    } finally {
      setBusy(false);
    }
  }

  return (
    <div className="space-y-6">
      {message && <p role="status" className="rounded-lg bg-green-50 p-3 text-sm text-green-800">{message}</p>}
      {error && <p role="alert" className="rounded-lg bg-red-50 p-3 text-sm text-red-700">{error}</p>}

      <form onSubmit={savePhone} className="card space-y-4 p-5 sm:p-6">
        <h3 className="text-base font-bold text-slate-900">{t(lang, 'profile_phone_title')}</h3>
        <div>
          <label className="label" htmlFor="pf-phone">{t(lang, 'phone')}</label>
          <input id="pf-phone" type="tel" dir="ltr" className="input" value={phone} onChange={(e) => setPhone(e.target.value)} maxLength={32} />
        </div>
        <div className="flex justify-end border-t border-slate-200 pt-4">
          <button type="submit" disabled={busy} className="btn-primary px-6 py-2.5">{busy ? t(lang, 'saving') : t(lang, 'profile_save')}</button>
        </div>
      </form>

      <form onSubmit={saveEmail} className="card space-y-4 p-5 sm:p-6">
        <h3 className="text-base font-bold text-slate-900">{t(lang, 'profile_email_title')}</h3>
        <p className="text-sm text-slate-600">{t(lang, 'profile_email_help')}</p>
        {profile.pending_email && (
          <p className="rounded-lg border border-amber-200 bg-amber-50 p-3 text-sm text-amber-800">
            {t(lang, 'profile_email_pending')} <span dir="ltr" className="font-semibold">{profile.pending_email}</span>
          </p>
        )}
        <div className="grid gap-5 sm:grid-cols-2">
          <div>
            <label className="label" htmlFor="pf-email">{t(lang, 'email')} *</label>
            <input id="pf-email" type="email" dir="ltr" required className="input" value={email} onChange={(e) => setEmail(e.target.value)} />
          </div>
          <div>
            <label className="label" htmlFor="pf-pass">{t(lang, 'profile_current_password')} *</label>
            <input id="pf-pass" type="password" dir="ltr" autoComplete="current-password" required className="input" value={password} onChange={(e) => setPassword(e.target.value)} />
          </div>
        </div>
        <div className="flex justify-end border-t border-slate-200 pt-4">
          <button type="submit" disabled={busy || email.trim() === profile.email || !password} className="btn-primary px-6 py-2.5">
            {busy ? t(lang, 'saving') : t(lang, 'profile_email_cta')}
          </button>
        </div>
      </form>

      <AssociationRoleCard lang={lang} status={profile.association} />
    </div>
  );
}

/** Rôle au sein de l'association : fonction en cours, adhésion, anciens mandats. */
export function AssociationRoleCard({ lang, status }: { lang: Lang; status?: AssociationStatus }) {
  const locale = lang === 'ar' ? 'ar' : lang === 'en' ? 'en-GB' : 'fr-FR';
  const fmt = (d: string | null) => (d ? new Date(`${d}T12:00:00`).toLocaleDateString(locale) : '—');
  const hasRole = (status?.current.length ?? 0) > 0;

  return (
    <section className="card space-y-4 p-5 sm:p-6">
      <h3 className="text-base font-bold text-slate-900">{t(lang, 'association_role_title')}</h3>
      {hasRole ? (
        <ul className="space-y-2">
          {status!.current.map((r, i) => (
            <li key={i} className="rounded-lg border border-slate-200 bg-slate-50 p-3 text-sm">
              <p className="font-semibold text-slate-900">{r.role}</p>
              <p className="text-slate-600">{r.association} · {fmt(r.mandate_start)} → {r.mandate_end ? fmt(r.mandate_end) : t(lang, 'association_mandate_open')}</p>
            </li>
          ))}
        </ul>
      ) : status?.member ? (
        <p className="text-sm text-slate-700">{t(lang, 'association_member_of')} <strong>{status.member}</strong></p>
      ) : (
        <p className="text-sm text-slate-600">{t(lang, 'association_simple_citizen')}</p>
      )}
      {status && status.past.length > 0 && (
        <div className="border-t border-slate-200 pt-3">
          <p className="mb-2 text-xs font-semibold uppercase tracking-wide text-slate-500">{t(lang, 'association_past_mandates')}</p>
          <ul className="space-y-1 text-sm text-slate-600">
            {status.past.map((r, i) => (
              <li key={i}>{r.role} · {r.association} · {fmt(r.mandate_start)} → {fmt(r.mandate_end)}</li>
            ))}
          </ul>
        </div>
      )}
    </section>
  );
}
