'use client';

import Link from 'next/link';
import { useEffect, useRef, useState } from 'react';
import { api, ApiError } from '@/lib/api';
import type { Lang } from '@/lib/types';
import { t } from '@/lib/i18n';

/** Lit un paramètre dans le fragment (#token=…&email=…), jamais envoyé au serveur ni journalisé. */
function fragmentParam(name: string): string | null {
  const m = window.location.hash.match(new RegExp(`(?:^#|&)${name}=([^&]+)`));
  return m ? decodeURIComponent(m[1]) : null;
}

/** Confirmation de l'email (lien reçu), ou renvoi du lien si aucun jeton n'est présent. */
export function VerifyEmailPanel({ consulateSlug, lang, base }: { consulateSlug: string; lang: Lang; base: string }) {
  const [ready, setReady] = useState(false);
  const [token, setToken] = useState<string | null>(null);
  const [email, setEmail] = useState('');
  const [message, setMessage] = useState<string | null>(null);
  const [error, setError] = useState<string | null>(null);
  const [done, setDone] = useState(false);
  const [busy, setBusy] = useState(false);
  // Le lien est à usage unique : l'appel ne doit partir qu'une fois (React peut rejouer l'effet).
  const started = useRef(false);

  useEffect(() => {
    const tk = fragmentParam('token');
    setToken(tk);
    setEmail(fragmentParam('email') ?? '');
    setReady(true);
    if (!tk || started.current) return;
    started.current = true;
    setBusy(true);
    api<{ message: string }>(`/${consulateSlug}/citizen/verify-email`, {
      method: 'POST', lang, noStore: true, body: { token: tk, email: fragmentParam('email') ?? '' },
    })
      .then((r) => { setMessage(r.message); setDone(true); setError(null); })
      .catch((err) => setError(err instanceof ApiError ? err.message : t(lang, 'error')))
      .finally(() => setBusy(false));
  }, [consulateSlug, lang]);

  async function resend(e: React.FormEvent) {
    e.preventDefault();
    setError(null);
    setBusy(true);
    try {
      const r = await api<{ message: string }>(`/${consulateSlug}/citizen/resend-verification`, {
        method: 'POST', lang, noStore: true, body: { email },
      });
      setMessage(r.message);
    } catch (err) {
      setError(err instanceof ApiError ? err.message : t(lang, 'error'));
    } finally {
      setBusy(false);
    }
  }

  if (!ready) return <p className="text-slate-500">{t(lang, 'loading')}</p>;

  return (
    <div className="card space-y-5 p-6">
      {busy && <p className="text-slate-500">{t(lang, 'loading')}</p>}
      {message && <p role="status" className="rounded-lg bg-green-50 p-3 text-sm text-green-800">{message}</p>}
      {error && <p role="alert" className="rounded-lg bg-red-50 p-3 text-sm text-red-700">{error}</p>}

      {done && (
        <Link href={`${base}/compte/connexion`} className="btn-primary inline-flex px-6 py-2.5">{t(lang, 'account_login_cta')}</Link>
      )}

      {!token && !done && (
        <form onSubmit={resend} className="space-y-4">
          <p className="text-sm text-slate-600">{t(lang, 'account_resend_intro')}</p>
          <div>
            <label className="label" htmlFor="resend-email">{t(lang, 'email')} *</label>
            <input id="resend-email" type="email" dir="ltr" required className="input" value={email} onChange={(e) => setEmail(e.target.value)} />
          </div>
          <button type="submit" disabled={busy} className="btn-primary px-6 py-2.5">{t(lang, 'account_resend_cta')}</button>
        </form>
      )}
    </div>
  );
}

/** Demande de lien de réinitialisation. La réponse est identique que le compte existe ou non. */
export function ForgotPasswordForm({ consulateSlug, lang, base }: { consulateSlug: string; lang: Lang; base: string }) {
  const [email, setEmail] = useState('');
  const [message, setMessage] = useState<string | null>(null);
  const [error, setError] = useState<string | null>(null);
  const [busy, setBusy] = useState(false);

  async function submit(e: React.FormEvent) {
    e.preventDefault();
    setError(null);
    setBusy(true);
    try {
      const r = await api<{ message: string }>(`/${consulateSlug}/citizen/forgot-password`, {
        method: 'POST', lang, noStore: true, body: { email },
      });
      setMessage(r.message);
    } catch (err) {
      setError(err instanceof ApiError ? err.message : t(lang, 'error'));
    } finally {
      setBusy(false);
    }
  }

  return (
    <form onSubmit={submit} className="card space-y-4 p-6">
      {message && <p role="status" className="rounded-lg bg-green-50 p-3 text-sm text-green-800">{message}</p>}
      {error && <p role="alert" className="rounded-lg bg-red-50 p-3 text-sm text-red-700">{error}</p>}
      <div>
        <label className="label" htmlFor="forgot-email">{t(lang, 'email')} *</label>
        <input id="forgot-email" type="email" dir="ltr" required className="input" value={email} onChange={(e) => setEmail(e.target.value)} />
      </div>
      <div className="flex flex-col-reverse gap-3 border-t border-slate-200 pt-4 sm:flex-row sm:items-center sm:justify-between">
        <Link href={`${base}/compte/connexion`} className="text-sm font-semibold text-brand hover:underline">← {t(lang, 'account_login_cta')}</Link>
        <button type="submit" disabled={busy} className="btn-primary px-6 py-2.5">{t(lang, 'account_forgot_cta')}</button>
      </div>
    </form>
  );
}

/** Nouveau mot de passe à partir du lien reçu par email. */
export function ResetPasswordForm({ consulateSlug, lang, base }: { consulateSlug: string; lang: Lang; base: string }) {
  const [ready, setReady] = useState(false);
  const [token, setToken] = useState('');
  const [email, setEmail] = useState('');
  const [password, setPassword] = useState('');
  const [confirmation, setConfirmation] = useState('');
  const [message, setMessage] = useState<string | null>(null);
  const [error, setError] = useState<string | null>(null);
  const [busy, setBusy] = useState(false);

  useEffect(() => {
    setToken(fragmentParam('token') ?? '');
    setEmail(fragmentParam('email') ?? '');
    setReady(true);
  }, []);

  async function submit(e: React.FormEvent) {
    e.preventDefault();
    setError(null);
    if (password !== confirmation) {
      setError(t(lang, 'account_password_mismatch'));
      return;
    }
    setBusy(true);
    try {
      const r = await api<{ message: string }>(`/${consulateSlug}/citizen/reset-password`, {
        method: 'POST', lang, noStore: true,
        body: { token, email, password, password_confirmation: confirmation },
      });
      setMessage(r.message);
    } catch (err) {
      setError(err instanceof ApiError ? err.message : t(lang, 'error'));
    } finally {
      setBusy(false);
    }
  }

  if (!ready) return <p className="text-slate-500">{t(lang, 'loading')}</p>;

  if (message) {
    return (
      <div className="card space-y-4 p-6">
        <p role="status" className="rounded-lg bg-green-50 p-3 text-sm text-green-800">{message}</p>
        <Link href={`${base}/compte/connexion`} className="btn-primary inline-flex px-6 py-2.5">{t(lang, 'account_login_cta')}</Link>
      </div>
    );
  }

  if (!token) {
    return <p role="alert" className="rounded-lg bg-red-50 p-3 text-sm text-red-700">{t(lang, 'account_reset_missing')}</p>;
  }

  return (
    <form onSubmit={submit} className="card space-y-4 p-6">
      {error && <p role="alert" className="rounded-lg bg-red-50 p-3 text-sm text-red-700">{error}</p>}
      <div>
        <label className="label" htmlFor="reset-password">{t(lang, 'account_new_password')} *</label>
        <input id="reset-password" type="password" dir="ltr" required minLength={8} autoComplete="new-password" className="input" value={password} onChange={(e) => setPassword(e.target.value)} />
        <p className="mt-1 text-xs text-slate-500">{t(lang, 'account_password_hint')}</p>
      </div>
      <div>
        <label className="label" htmlFor="reset-password2">{t(lang, 'account_password_confirm')} *</label>
        <input id="reset-password2" type="password" dir="ltr" required minLength={8} autoComplete="new-password" className="input" value={confirmation} onChange={(e) => setConfirmation(e.target.value)} />
      </div>
      <div className="flex justify-end border-t border-slate-200 pt-4">
        <button type="submit" disabled={busy} className="btn-primary px-6 py-2.5">{t(lang, 'account_reset_cta')}</button>
      </div>
    </form>
  );
}
