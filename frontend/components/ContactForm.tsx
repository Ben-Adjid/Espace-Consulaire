'use client';

import { useEffect, useState } from 'react';
import { api, ApiError } from '@/lib/api';
import type { Lang } from '@/lib/types';
import { t } from '@/lib/i18n';

/** Formulaire de contact : consentement, honeypot, jeton horodaté (anti-robot), rate limiting côté API. */
export default function ContactForm({ consulateSlug, lang }: { consulateSlug: string; lang: Lang }) {
  const [token, setToken] = useState<string | null>(null);
  const [form, setForm] = useState({ name: '', email: '', phone: '', subject: '', message: '' });
  const [consent, setConsent] = useState(false);
  const [website, setWebsite] = useState('');
  const [error, setError] = useState<string | null>(null);
  const [done, setDone] = useState(false);
  const [busy, setBusy] = useState(false);

  useEffect(() => {
    api<{ token: string }>(`/${consulateSlug}/contact/token`, { lang, noStore: true })
      .then((r) => setToken(r.token))
      .catch(() => setToken(null));
  }, [consulateSlug, lang]);

  async function submit(e: React.FormEvent) {
    e.preventDefault();
    setError(null);
    setBusy(true);
    try {
      await api(`/${consulateSlug}/contact`, {
        method: 'POST',
        lang,
        body: { ...form, consent, website, _t: token ?? '' },
      });
      setDone(true);
    } catch (err) {
      setError(err instanceof ApiError ? err.message : t(lang, 'error'));
    } finally {
      setBusy(false);
    }
  }

  if (done) {
    return <p role="status" className="rounded-lg border border-green-300 bg-green-50 p-4 text-green-800">{t(lang, 'sent_ok')}</p>;
  }

  return (
    <form onSubmit={submit} className="card space-y-4 p-6" noValidate>
      <div className="hp" aria-hidden="true">
        <label>Website <input tabIndex={-1} autoComplete="off" value={website} onChange={(e) => setWebsite(e.target.value)} /></label>
      </div>
      <div className="grid gap-4 sm:grid-cols-2">
        <div>
          <label className="label" htmlFor="c-name">{t(lang, 'name')} *</label>
          <input id="c-name" className="input" required maxLength={120} autoComplete="name" value={form.name} onChange={(e) => setForm({ ...form, name: e.target.value })} />
        </div>
        <div>
          <label className="label" htmlFor="c-email">{t(lang, 'email')} *</label>
          <input id="c-email" type="email" dir="ltr" className="input" required maxLength={160} autoComplete="email" value={form.email} onChange={(e) => setForm({ ...form, email: e.target.value })} />
        </div>
        <div>
          <label className="label" htmlFor="c-phone">{t(lang, 'phone')}</label>
          <input id="c-phone" type="tel" dir="ltr" className="input" maxLength={32} autoComplete="tel" value={form.phone} onChange={(e) => setForm({ ...form, phone: e.target.value })} />
        </div>
        <div>
          <label className="label" htmlFor="c-subject">{t(lang, 'subject')} *</label>
          <input id="c-subject" className="input" required maxLength={160} value={form.subject} onChange={(e) => setForm({ ...form, subject: e.target.value })} />
        </div>
      </div>
      <div>
        <label className="label" htmlFor="c-message">{t(lang, 'message')} *</label>
        <textarea id="c-message" rows={6} className="input" required maxLength={4000} value={form.message} onChange={(e) => setForm({ ...form, message: e.target.value })} />
      </div>
      <label className="flex items-start gap-3 text-sm text-slate-700">
        <input type="checkbox" className="mt-1 size-4" checked={consent} onChange={(e) => setConsent(e.target.checked)} required />
        <span>{t(lang, 'consent')}</span>
      </label>
      {error && <p role="alert" className="rounded-lg bg-red-50 p-3 text-sm text-red-700">{error}</p>}
      <button type="submit" className="btn-primary px-6 py-3" disabled={busy}>{t(lang, 'send')}</button>
    </form>
  );
}
