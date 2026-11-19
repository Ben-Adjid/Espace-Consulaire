'use client';

import Link from 'next/link';
import { useRouter } from 'next/navigation';
import { useEffect, useState } from 'react';
import { api, ApiError } from '@/lib/api';
import { bff } from '@/lib/bff';
import type { Lang } from '@/lib/types';
import { t } from '@/lib/i18n';

interface SuggestedConsulate {
  slug: string;
  name: string;
}

/** Connexion d'un citoyen à son espace. Le jeton est posé dans un cookie httpOnly par le serveur (jamais lu par la page). */
export function CitizenLoginForm({ consulateSlug, lang, base }: { consulateSlug: string; lang: Lang; base: string }) {
  const router = useRouter();
  const [email, setEmail] = useState('');
  const [password, setPassword] = useState('');
  const [error, setError] = useState<string | null>(null);
  const [unverified, setUnverified] = useState(false);
  const [busy, setBusy] = useState(false);
  const [showPassword, setShowPassword] = useState(false);

  // Déjà connecté (session valide côté serveur) : on va directement à l'espace, sans boucle.
  useEffect(() => {
    bff(consulateSlug, 'citizen/me', { lang })
      .then(() => router.replace(`${base}/compte`))
      .catch(() => undefined); // 401 attendu quand personne n'est connecté : le formulaire reste affiché.
  }, [consulateSlug, lang, base, router]);

  async function submit(e: React.FormEvent) {
    e.preventDefault();
    setError(null);
    setUnverified(false);
    setBusy(true);
    try {
      await bff(consulateSlug, 'citizen/login', { method: 'POST', lang, body: { email, password } });
      router.replace(`${base}/compte`);
    } catch (err) {
      const body = err instanceof ApiError ? (err.body as { status?: string; reason?: string | null }) : null;
      let message = err instanceof ApiError ? err.message : t(lang, 'error');
      if (body?.status === 'rejected' && body.reason) message = `${message} — ${body.reason}`;
      setError(message);
      setUnverified(body?.status === 'unverified');
      setBusy(false);
    }
  }

  return (
    <form method="post" onSubmit={submit} className="space-y-5 rounded-2xl border border-slate-200/80 bg-white p-6 shadow-[0_12px_32px_-12px_rgba(15,23,42,0.15)] sm:p-8" noValidate>
      {error && (
        <div role="alert" className="flex gap-3 rounded-xl border border-red-200 bg-red-50 p-3 text-sm text-red-800">
          <svg aria-hidden="true" viewBox="0 0 20 20" fill="none" className="mt-0.5 h-5 w-5 shrink-0"><circle cx="10" cy="10" r="7.5" stroke="currentColor" strokeWidth="1.5" /><path d="M10 6.5v4M10 13.5h.01" stroke="currentColor" strokeWidth="1.7" strokeLinecap="round" /></svg>
          <div>
            <p>{error}</p>
            {unverified && (
              <Link href={`${base}/compte/verifier`} className="mt-2 inline-block font-semibold underline">{t(lang, 'account_resend_cta')} →</Link>
            )}
          </div>
        </div>
      )}
      <div>
        <label className="label" htmlFor="login-email">{t(lang, 'email')}</label>
        <input id="login-email" type="email" dir="ltr" required autoComplete="email" inputMode="email" className="input py-3" value={email} onChange={(e) => setEmail(e.target.value)} />
      </div>
      <div>
        <div className="flex items-baseline justify-between gap-3">
          <label className="label" htmlFor="login-password">{t(lang, 'account_password')}</label>
          <Link href={`${base}/compte/mot-de-passe-oublie`} className="text-xs font-semibold text-slate-500 hover:text-brand hover:underline">
            {t(lang, 'account_forgot_link')}
          </Link>
        </div>
        <div className="relative">
          <input id="login-password" type={showPassword ? 'text' : 'password'} dir="ltr" required autoComplete="current-password" className="input py-3 pe-12" value={password} onChange={(e) => setPassword(e.target.value)} />
          <button
            type="button"
            onClick={() => setShowPassword((v) => !v)}
            aria-label={showPassword ? t(lang, 'hide_password') : t(lang, 'show_password')}
            aria-pressed={showPassword}
            className="absolute inset-y-0 end-0 flex w-11 items-center justify-center rounded-e-lg text-slate-500 hover:text-slate-800"
          >
            {showPassword ? (
              <svg aria-hidden="true" viewBox="0 0 20 20" fill="none" className="h-5 w-5"><path d="M3 3l14 14M8.6 8.7a2 2 0 0 0 2.7 2.7M6.2 6.3C4.4 7.4 3 9 2.5 10c1.2 2.4 4 5 7.5 5 1.3 0 2.5-.3 3.6-.9M10 5c3.5 0 6.3 2.6 7.5 5-.4.8-1 1.7-1.8 2.5" stroke="currentColor" strokeWidth="1.5" strokeLinecap="round" /></svg>
            ) : (
              <svg aria-hidden="true" viewBox="0 0 20 20" fill="none" className="h-5 w-5"><path d="M2.5 10C3.7 7.6 6.5 5 10 5s6.3 2.6 7.5 5c-1.2 2.4-4 5-7.5 5s-6.3-2.6-7.5-5Z" stroke="currentColor" strokeWidth="1.5" /><circle cx="10" cy="10" r="2.2" stroke="currentColor" strokeWidth="1.5" /></svg>
            )}
          </button>
        </div>
      </div>
      <button type="submit" disabled={busy} aria-busy={busy} className="btn-primary inline-flex w-full items-center justify-center gap-2 py-3 text-base disabled:cursor-wait disabled:opacity-80">
        {busy && <span aria-hidden="true" className="h-4 w-4 animate-spin rounded-full border-2 border-white/40 border-t-white" />}
        {busy ? t(lang, 'logging_in') : t(lang, 'account_login_cta')}
      </button>
      <div className="space-y-2 border-t border-slate-100 pt-4 text-center text-sm">
        <p>
          <span className="text-slate-500">{t(lang, 'account_no_account')}</span>{' '}
          <Link href={`${base}/compte/inscription`} className="font-semibold text-brand hover:underline">{t(lang, 'account_register_cta')}</Link>
        </p>
        <p>
          <span className="text-slate-500">{t(lang, 'agent_hint')}</span>{' '}
          <a href={`${lang === 'fr' ? '/connexion' : `/connexion/${lang}`}#agent`} className="font-semibold text-slate-700 hover:underline">{t(lang, 'agent_link')}</a>
        </p>
      </div>
    </form>
  );
}

/** Inscription : le pays de résidence doit être couvert par ce consulat, sinon l'API propose le bon consulat. */
export function CitizenRegisterForm({
  consulateSlug, lang, base, countries, nationalityLabel,
}: { consulateSlug: string; lang: Lang; base: string; countries: string[]; nationalityLabel: string }) {
  const [form, setForm] = useState({ full_name: '', email: '', phone: '', password: '', password_confirmation: '', residence_proof: '' });
  const [residence, setResidence] = useState(countries[0] ?? '');
  const [nationality, setNationality] = useState(false);
  const [consent, setConsent] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [suggested, setSuggested] = useState<SuggestedConsulate | null>(null);
  const [done, setDone] = useState<string | null>(null);
  const [busy, setBusy] = useState(false);

  async function submit(e: React.FormEvent) {
    e.preventDefault();
    setError(null);
    setSuggested(null);
    if (form.password !== form.password_confirmation) {
      setError(t(lang, 'account_password_mismatch'));
      return;
    }
    if (!nationality || !consent) {
      setError(t(lang, 'account_confirm_required'));
      return;
    }
    setBusy(true);
    try {
      const res = await api<{ message: string }>(`/${consulateSlug}/citizen/register`, {
        method: 'POST', lang, noStore: true,
        body: { ...form, residence_country: residence, nationality, consent },
      });
      setDone(res.message);
    } catch (err) {
      setError(err instanceof ApiError ? err.message : t(lang, 'error'));
      const body = err instanceof ApiError ? (err.body as { suggested_consulate?: SuggestedConsulate | null }) : null;
      setSuggested(body?.suggested_consulate ?? null);
    } finally {
      setBusy(false);
    }
  }

  if (done) {
    return (
      <div className="card space-y-4 p-6 text-center">
        <p className="text-lg font-bold text-slate-900">{t(lang, 'account_received_title')}</p>
        <p className="text-sm text-slate-600">{done}</p>
        <Link href={`${base}/compte/connexion`} className="btn-primary inline-flex px-6 py-2.5">{t(lang, 'account_login_cta')}</Link>
      </div>
    );
  }

  return (
    <form onSubmit={submit} className="card space-y-5 p-6" noValidate>
      {error && (
        <div role="alert" className="rounded-lg bg-red-50 p-3 text-sm text-red-700">
          <p>{error}</p>
          {suggested && (
            <Link href={`/${suggested.slug}/${lang}/compte/inscription`} className="mt-2 inline-block font-semibold underline">{suggested.name} →</Link>
          )}
        </div>
      )}

      <div className="grid gap-4 sm:grid-cols-2">
        <div>
          <label className="label" htmlFor="reg-name">{t(lang, 'name')} *</label>
          <input id="reg-name" required maxLength={120} autoComplete="name" className="input" value={form.full_name} onChange={(e) => setForm({ ...form, full_name: e.target.value })} />
        </div>
        <div>
          <label className="label" htmlFor="reg-phone">{t(lang, 'phone')} *</label>
          <input id="reg-phone" type="tel" dir="ltr" required autoComplete="tel" className="input" value={form.phone} onChange={(e) => setForm({ ...form, phone: e.target.value })} />
        </div>
        <div className="sm:col-span-2">
          <label className="label" htmlFor="reg-email">{t(lang, 'email')} *</label>
          <input id="reg-email" type="email" dir="ltr" required autoComplete="email" className="input" value={form.email} onChange={(e) => setForm({ ...form, email: e.target.value })} />
        </div>
        <div>
          <label className="label" htmlFor="reg-password">{t(lang, 'account_password')} *</label>
          <input id="reg-password" type="password" dir="ltr" required minLength={8} autoComplete="new-password" className="input" value={form.password} onChange={(e) => setForm({ ...form, password: e.target.value })} />
          <p className="mt-1 text-xs text-slate-500">{t(lang, 'account_password_hint')}</p>
        </div>
        <div>
          <label className="label" htmlFor="reg-password2">{t(lang, 'account_password_confirm')} *</label>
          <input id="reg-password2" type="password" dir="ltr" required minLength={8} autoComplete="new-password" className="input" value={form.password_confirmation} onChange={(e) => setForm({ ...form, password_confirmation: e.target.value })} />
        </div>
      </div>

      <div className="space-y-4 rounded-lg border border-slate-200 bg-slate-50 p-4">
        <div>
          <label className="label" htmlFor="reg-residence">{t(lang, 'residence_country')} *</label>
          <select id="reg-residence" className="input" value={residence} onChange={(e) => setResidence(e.target.value)}>
            {countries.map((c) => (
              <option key={c} value={c}>{c}</option>
            ))}
          </select>
          <p className="mt-1 text-xs text-slate-500">{t(lang, 'account_residence_help')}</p>
        </div>
        <div>
          <label className="label" htmlFor="reg-proof">{t(lang, 'account_proof')}</label>
          <input id="reg-proof" maxLength={300} className="input" value={form.residence_proof} onChange={(e) => setForm({ ...form, residence_proof: e.target.value })} />
          <p className="mt-1 text-xs text-slate-500">{t(lang, 'account_proof_help')}</p>
        </div>
        <label className="flex items-start gap-3 text-sm text-slate-700">
          <input type="checkbox" className="mt-1 size-4" checked={nationality} onChange={(e) => setNationality(e.target.checked)} required />
          <span>{t(lang, 'nationality_confirm').replace('{nationality}', nationalityLabel)}</span>
        </label>
      </div>

      <label className="flex items-start gap-3 text-sm text-slate-700">
        <input type="checkbox" className="mt-1 size-4" checked={consent} onChange={(e) => setConsent(e.target.checked)} required />
        <span>{t(lang, 'consent')}</span>
      </label>

      <div className="flex flex-col-reverse gap-3 border-t border-slate-200 pt-4 sm:flex-row sm:items-center sm:justify-between">
        <Link href={`${base}/compte/connexion`} className="text-sm font-semibold text-brand hover:underline">
          {t(lang, 'account_have_account')} {t(lang, 'account_login_cta')} →
        </Link>
        <button type="submit" disabled={busy} className="btn-primary px-6 py-2.5">
          {busy ? t(lang, 'saving') : t(lang, 'account_register_cta')}
        </button>
      </div>
    </form>
  );
}
