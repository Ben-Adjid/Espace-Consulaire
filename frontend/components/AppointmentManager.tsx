'use client';

import { useEffect, useState } from 'react';
import { api, ApiError, downloadWithToken } from '@/lib/api';
import type { AppointmentView, Lang } from '@/lib/types';
import { t } from '@/lib/i18n';

/**
 * Affiche un rendez-vous et permet de l'annuler ou de télécharger le .ics.
 * Le jeton est lu dans le fragment d'URL (#token=…) puis envoyé en en-tête Authorization.
 */
export default function AppointmentManager({ consulateSlug, lang, reference }: { consulateSlug: string; lang: Lang; reference: string }) {
  const [token, setToken] = useState<string | null>(null);
  const [appt, setAppt] = useState<AppointmentView | null>(null);
  const [error, setError] = useState<string | null>(null);
  const [notice, setNotice] = useState<string | null>(null);
  const [ready, setReady] = useState(false);

  useEffect(() => {
    const match = window.location.hash.match(/token=([^&]+)/);
    const found = match ? decodeURIComponent(match[1]) : null;
    setToken(found);
    setReady(true);
    if (!found) {
      setError(t(lang, 'token_missing'));
      return;
    }
    api<{ data: AppointmentView }>(`/${consulateSlug}/appointments/${reference}`, { lang, token: found, noStore: true })
      .then((r) => setAppt(r.data))
      .catch((err) => setError(err instanceof ApiError ? err.message : t(lang, 'error')));
  }, [consulateSlug, lang, reference]);

  async function cancel() {
    if (!token || !window.confirm(t(lang, 'cancel_confirm'))) return;
    setError(null);
    try {
      const r = await api<{ data: AppointmentView }>(`/${consulateSlug}/appointments/${reference}/cancel`, {
        method: 'POST', lang, token, noStore: true,
      });
      setAppt(r.data);
      setNotice(t(lang, 'cancel_done'));
    } catch (err) {
      setError(err instanceof ApiError ? err.message : t(lang, 'cancel_locked'));
    }
  }

  async function ics() {
    if (!token) return;
    try {
      await downloadWithToken(`/${consulateSlug}/appointments/${reference}/ics`, token, `${reference}.ics`);
    } catch {
      setError(t(lang, 'error'));
    }
  }

  if (!ready) return <p className="text-slate-500">{t(lang, 'loading')}</p>;

  return (
    <div className="space-y-4">
      {error && <p role="alert" className="rounded-lg bg-red-50 p-3 text-sm text-red-700">{error}</p>}
      {notice && <p role="status" className="rounded-lg bg-green-50 p-3 text-sm text-green-800">{notice}</p>}

      {appt && (
        <>
          <dl className="grid gap-3 text-sm sm:grid-cols-2">
            <div><dt className="text-slate-500">{t(lang, 'service')}</dt><dd className="font-semibold">{appt.service}</dd></div>
            <div><dt className="text-slate-500">{t(lang, 'date')}</dt><dd className="font-semibold">{new Date(`${appt.date}T12:00:00`).toLocaleDateString(lang === 'ar' ? 'ar' : lang === 'en' ? 'en-GB' : 'fr-FR', { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' })}</dd></div>
            <div><dt className="text-slate-500">{t(lang, 'time')}</dt><dd className="font-semibold" dir="ltr">{appt.start_time} – {appt.end_time}</dd></div>
            <div><dt className="text-slate-500">{t(lang, 'status')}</dt><dd className="font-semibold">{appt.status_label}</dd></div>
          </dl>

          <div className="flex flex-wrap gap-3">
            <button type="button" onClick={ics} className="btn-outline">{t(lang, 'download_ics')}</button>
            {appt.cancellable && (
              <button type="button" onClick={cancel} className="btn border border-red-600 text-red-700 hover:bg-red-600 hover:text-white">
                {t(lang, 'cancel_appt')}
              </button>
            )}
          </div>
        </>
      )}
    </div>
  );
}
