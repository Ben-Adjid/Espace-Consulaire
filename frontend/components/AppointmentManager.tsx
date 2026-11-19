'use client';

import Link from 'next/link';
import { useEffect, useState } from 'react';
import { api, ApiError, downloadWithToken } from '@/lib/api';
import { bff, bffRaw } from '@/lib/bff';
import type { AppointmentView, Lang, Slot } from '@/lib/types';
import { seatsLabel, t } from '@/lib/i18n';
import { useConfirm } from './ConfirmDialog';

/**
 * Gestion d'un rendez-vous par le visiteur : consulter, ajouter au calendrier, déplacer, annuler.
 * Le jeton est lu dans le fragment d'URL (#token=…) puis envoyé en en-tête Authorization.
 * Chaque panneau a son bouton « Retour ». L'annulation reste séparée, en rouge, avec confirmation.
 */
export default function AppointmentManager({ consulateSlug, lang, reference, consulateName = '' }: { consulateSlug: string; lang: Lang; reference: string; consulateName?: string }) {
  const [token, setToken] = useState<string | null>(null);
  const [appt, setAppt] = useState<AppointmentView | null>(null);
  const [error, setError] = useState<string | null>(null);
  const [notice, setNotice] = useState<string | null>(null);
  const [ready, setReady] = useState(false);

  // Déplacement
  const [moving, setMoving] = useState(false);
  const [dates, setDates] = useState<string[] | null>(null);
  const [newDate, setNewDate] = useState('');
  const [slots, setSlots] = useState<Slot[]>([]);
  const [newSlotId, setNewSlotId] = useState<number | null>(null);
  const [busy, setBusy] = useState(false);
  const [dialog, confirm] = useConfirm(lang);

  const base = `/${consulateSlug}/${lang}`;
  const locale = lang === 'ar' ? 'ar' : lang === 'en' ? 'en-GB' : 'fr-FR';
  const fmtDate = (iso: string, opts: Intl.DateTimeFormatOptions) =>
    new Date(`${iso}T12:00:00`).toLocaleDateString(locale, opts);

  useEffect(() => {
    // Lien personnel (#token=…) d'abord ; sinon le compte citoyen qui possède ce rendez-vous (cookie de session).
    const match = window.location.hash.match(/token=([^&]+)/);
    const found = match ? decodeURIComponent(match[1]) : null;
    setToken(found);
    setReady(true);
    const load = found
      ? api<{ data: AppointmentView }>(`/${consulateSlug}/appointments/${reference}`, { lang, token: found, noStore: true })
      : bff<{ data: AppointmentView }>(consulateSlug, `appointments/${reference}`, { lang });
    load
      .then((r) => setAppt(r.data))
      .catch((err) => {
        if (!found && err instanceof ApiError && err.status === 401) setError(t(lang, 'token_missing'));
        else setError(err instanceof ApiError ? err.message : t(lang, 'error'));
      });
  }, [consulateSlug, lang, reference]);

  // Dates proposées pour le déplacement (même service que le rendez-vous).
  useEffect(() => {
    if (!moving || !appt?.service_slug) return;
    let cancelled = false;
    setDates(null);
    api<{ data: string[] }>(`/${consulateSlug}/availability/dates`, { lang, query: { service: appt.service_slug }, noStore: true })
      .then((r) => !cancelled && setDates(r.data))
      .catch(() => !cancelled && setDates([]));
    return () => {
      cancelled = true;
    };
  }, [moving, appt?.service_slug, consulateSlug, lang]);

  // Créneaux du jour choisi.
  useEffect(() => {
    setSlots([]);
    setNewSlotId(null);
    if (!moving || !newDate || !appt?.service_slug) return;
    let cancelled = false;
    api<{ data: Slot[] }>(`/${consulateSlug}/availability/slots`, { lang, query: { service: appt.service_slug, date: newDate }, noStore: true })
      .then((r) => !cancelled && setSlots(r.data))
      .catch(() => !cancelled && setSlots([]));
    return () => {
      cancelled = true;
    };
  }, [newDate, moving, appt?.service_slug, consulateSlug, lang]);

  /** Appel sur ce rendez-vous : lien personnel si présent, sinon compte citoyen (cookie via le proxy). */
  function act<T>(path: string, method: 'GET' | 'POST', body?: unknown): Promise<T> {
    return token
      ? api<T>(`/${consulateSlug}/appointments/${reference}${path}`, { method, lang, token, body, noStore: true })
      : bff<T>(consulateSlug, `appointments/${reference}${path}`, { method, lang, body });
  }

  async function cancel() {
    if (!appt) return;
    const ok = await confirm({
      title: t(lang, 'cancel_confirm'),
      subject: `${appt.service ?? ''} · ${fmtDate(appt.date, { weekday: 'long', day: 'numeric', month: 'long' })} · ${appt.start_time} · ${appt.reference}`,
      confirmLabel: t(lang, 'confirm_yes'),
      danger: true,
    });
    if (!ok) return;
    setError(null);
    try {
      const r = await act<{ data: AppointmentView }>('/cancel', 'POST');
      setAppt(r.data);
      setMoving(false);
      setNotice(t(lang, 'cancel_done'));
    } catch (err) {
      setError(err instanceof ApiError ? err.message : t(lang, 'cancel_locked'));
    }
  }

  async function reschedule() {
    if (newSlotId === null || !newDate) return;
    setError(null);
    setBusy(true);
    try {
      const r = await act<{ data: AppointmentView }>('/reschedule', 'POST', { date: newDate, slot_id: newSlotId });
      setAppt(r.data);
      setMoving(false);
      setNotice(t(lang, 'reschedule_done'));
    } catch (err) {
      setError(err instanceof ApiError ? err.message : t(lang, 'error'));
    } finally {
      setBusy(false);
    }
  }

  async function ics() {
    try {
      if (token) {
        await downloadWithToken(`/${consulateSlug}/appointments/${reference}/ics`, token, `${reference}.ics`);
        return;
      }
      // Compte citoyen : le cookie de session est envoyé par le proxy, le fichier est téléchargé depuis le navigateur.
      const res = await bffRaw(consulateSlug, `appointments/${reference}/ics`, { lang });
      if (!res.ok) throw new Error('ics');
      const url = URL.createObjectURL(await res.blob());
      const a = document.createElement('a');
      a.href = url;
      a.download = `${reference}.ics`;
      a.click();
      URL.revokeObjectURL(url);
    } catch {
      setError(t(lang, 'error'));
    }
  }

  if (!ready) return <p className="text-slate-500">{t(lang, 'loading')}</p>;

  const btnBack = 'btn border border-slate-300 bg-white text-slate-700 hover:bg-slate-50';
  const btnPrimary = 'btn-primary px-5 py-2.5';

  return (
    <div className="space-y-6">
      {dialog}
      {error && <p role="alert" className="rounded-lg bg-red-50 p-3 text-sm text-red-700">{error}</p>}
      {notice && <p role="status" className="rounded-lg bg-green-50 p-3 text-sm text-green-800">{notice}</p>}

      {appt && !moving && (
        <>
          <dl className="grid gap-3 rounded-xl border border-slate-200 bg-white p-5 text-sm sm:grid-cols-2">
            <div><dt className="text-slate-500">{t(lang, 'service')}</dt><dd className="font-semibold">{appt.service}</dd></div>
            <div><dt className="text-slate-500">{t(lang, 'date')}</dt><dd className="font-semibold">{fmtDate(appt.date, { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' })}</dd></div>
            <div><dt className="text-slate-500">{t(lang, 'time')}</dt><dd className="font-semibold" dir="ltr">{appt.start_time} – {appt.end_time}</dd></div>
            <div><dt className="text-slate-500">{t(lang, 'status')}</dt><dd className="font-semibold">{appt.status_label}</dd></div>
            {appt.ticket && (
              <div><dt className="text-slate-500">{t(lang, 'ticket')}</dt><dd className="text-lg font-bold text-sky-700" dir="ltr">{appt.ticket}</dd></div>
            )}
            {appt.case_status_label && (
              <div className="sm:col-span-2">
                <dt className="text-slate-500">{t(lang, 'case_status')}</dt>
                <dd className="font-semibold">{appt.case_status_label}</dd>
                {appt.case_note && <dd className="mt-1 text-sm text-slate-600">{appt.case_note}</dd>}
              </div>
            )}
            {appt.pre_request && (
              <div className="sm:col-span-2">
                <dt className="text-slate-500">{t(lang, 'appt_dossier')}</dt>
                <dd className={`font-semibold ${appt.pre_request.status === 'validated' ? 'text-green-800' : 'text-amber-800'}`}>{appt.pre_request.status_label}</dd>
              </div>
            )}
          </dl>

          {/* Actions principales à gauche, retour à l'accueil à droite */}
          <div className="flex flex-col gap-3 sm:flex-row sm:flex-wrap sm:items-center sm:justify-between">
            <div className="flex flex-col gap-3 sm:flex-row sm:flex-wrap">
              <button type="button" onClick={ics} className={btnBack}>{t(lang, 'download_ics')}</button>
              <button type="button" onClick={() => printTicket(appt, consulateName, lang)} className={btnBack}>{t(lang, 'ticket_print')}</button>
              {appt.cancellable && appt.status !== 'cancelled' && (
                <button type="button" onClick={() => { setError(null); setNotice(null); setMoving(true); }} className={btnPrimary}>
                  {t(lang, 'btn_reschedule')}
                </button>
              )}
            </div>
            <Link href={base} className="text-sm font-semibold text-slate-600 hover:text-slate-900 hover:underline">
              ← {t(lang, 'btn_home')}
            </Link>
          </div>

          {appt.cancellable && (
            <div className="rounded-xl border border-red-200 bg-red-50/50 p-4">
              <p className="mb-3 text-sm text-slate-700">{t(lang, 'cancel_hint')}</p>
              <button type="button" onClick={cancel} className="btn border border-red-600 bg-white text-red-700 hover:bg-red-600 hover:text-white">
                {t(lang, 'cancel_appt')}
              </button>
            </div>
          )}
        </>
      )}

      {appt && moving && (
        <section className="space-y-6 rounded-xl border border-slate-200 bg-white p-5">
          <h2 className="text-lg font-bold text-brand">{t(lang, 'reschedule_title')}</h2>

          <div>
            <p className="mb-2 text-sm font-semibold text-slate-700">{t(lang, 'step_date')}</p>
            {dates === null && <p className="text-slate-500">{t(lang, 'loading')}</p>}
            {dates !== null && dates.length === 0 && <p className="text-slate-600">{t(lang, 'no_dates')}</p>}
            {dates && dates.length > 0 && (
              <div className="grid grid-cols-2 gap-2 sm:grid-cols-3 md:grid-cols-4" role="radiogroup" aria-label={t(lang, 'step_date')}>
                {dates.map((d) => {
                  const selected = d === newDate;
                  return (
                    <button
                      key={d}
                      type="button"
                      role="radio"
                      aria-checked={selected}
                      onClick={() => setNewDate(d)}
                      className={`rounded-lg border px-3 py-2 text-start text-sm ${selected ? 'border-transparent bg-brand text-white' : 'border-slate-300 hover:border-slate-500'}`}
                    >
                      <span className="block text-xs uppercase opacity-80">{fmtDate(d, { weekday: 'long' })}</span>
                      <span className="font-semibold">{fmtDate(d, { day: 'numeric', month: 'short' })}</span>
                    </button>
                  );
                })}
              </div>
            )}
          </div>

          {newDate && (
            <div>
              <p className="mb-2 text-sm font-semibold text-slate-700">{t(lang, 'step_slot')}</p>
              {slots.length === 0 && <p className="text-slate-600">{t(lang, 'no_slots')}</p>}
              <div className="grid gap-2 sm:grid-cols-2">
                {slots.map((s) => {
                  const selected = s.id === newSlotId;
                  return (
                    <label key={s.id} className={`flex cursor-pointer items-center justify-between rounded-lg border p-3 text-sm ${selected ? 'border-[var(--brand)] ring-2 ring-[var(--brand)]/30' : 'border-slate-300 hover:border-slate-500'}`}>
                      <span className="flex items-center gap-3">
                        <input type="radio" name="new-slot" className="size-4" checked={selected} onChange={() => setNewSlotId(s.id)} />
                        <span className="font-semibold" dir="ltr">{s.start_time} – {s.end_time}</span>
                      </span>
                      <span className="text-xs text-slate-500">{seatsLabel(lang, s.remaining)}</span>
                    </label>
                  );
                })}
              </div>
            </div>
          )}

          <div className="flex flex-col-reverse gap-3 border-t border-slate-200 pt-5 sm:flex-row sm:justify-between">
            <button type="button" className={btnBack} onClick={() => { setMoving(false); setError(null); }}>← {t(lang, 'btn_back')}</button>
            <button type="button" className={btnPrimary} disabled={newSlotId === null || busy} onClick={reschedule}>
              {busy ? t(lang, 'saving') : t(lang, 'btn_confirm_move')}
            </button>
          </div>
        </section>
      )}
    </div>
  );
}

/** Ticket imprimable : fenêtre dédiée (indépendante des styles du site), échappement HTML des valeurs. */
function printTicket(appt: AppointmentView, consulateName: string, lang: Lang) {
  const esc = (v: string | null | undefined) => (v ?? '').replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' })[c] ?? c);
  const dateLabel = new Date(`${appt.date}T12:00:00`).toLocaleDateString(lang === 'ar' ? 'ar' : lang === 'en' ? 'en-GB' : 'fr-FR', { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' });
  const w = window.open('', '_blank', 'width=420,height=640');
  if (!w) return;
  w.document.write(`<!doctype html><html lang="${lang}" dir="${lang === 'ar' ? 'rtl' : 'ltr'}"><head><meta charset="utf-8"><title>${esc(appt.reference)}</title>
<style>body{font-family:system-ui,Arial,sans-serif;padding:24px;color:#0f172a}.t{border:2px dashed #0f172a;border-radius:12px;padding:20px;text-align:center}
.n{font-size:40px;font-weight:800;margin:8px 0}.r{font-family:monospace;font-size:18px;letter-spacing:2px}small{color:#475569}</style></head><body>
<div class="t"><p><strong>${esc(consulateName)}</strong></p>
<p>${esc(appt.service)}</p>
<p class="r">${esc(appt.reference)}</p>
${appt.ticket ? `<p><small>Ticket</small></p><p class="n">${esc(appt.ticket)}</p>` : ''}
<p>${esc(dateLabel)}<br>${esc(appt.start_time)} – ${esc(appt.end_time)}</p>
<p><small>Présentez ce ticket et une pièce d'identité au guichet.</small></p></div>
<script>window.onload=function(){window.print();}</script></body></html>`);
  w.document.close();
}
