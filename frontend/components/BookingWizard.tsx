'use client';

import { useRouter } from 'next/navigation';
import { useEffect, useState } from 'react';
import { api, ApiError } from '@/lib/api';
import type { Lang, Service, Slot } from '@/lib/types';
import { seatsLabel, t } from '@/lib/i18n';

interface Props {
  consulateSlug: string;
  lang: Lang;
  base: string;
  services: Service[];
  preselected?: string;
}

/** Réservation en 4 étapes : service → date → créneau → coordonnées. Les places sont vérifiées par l'API. */
export default function BookingWizard({ consulateSlug, lang, base, services, preselected }: Props) {
  const router = useRouter();
  const bookable = services.filter((s) => s.allow_appointment);

  const [service, setService] = useState(preselected && bookable.some((s) => s.slug === preselected) ? preselected : '');
  const [dates, setDates] = useState<string[] | null>(null);
  const [date, setDate] = useState('');
  const [slots, setSlots] = useState<Slot[]>([]);
  const [slotId, setSlotId] = useState<number | null>(null);
  const [form, setForm] = useState({ full_name: '', email: '', phone: '', notes: '' });
  const [consent, setConsent] = useState(false);
  const [website, setWebsite] = useState(''); // honeypot
  const [error, setError] = useState<string | null>(null);
  const [submitting, setSubmitting] = useState(false);

  // Dates disponibles dès que le service change.
  useEffect(() => {
    setDates(null);
    setDate('');
    setSlots([]);
    setSlotId(null);
    if (!service) return;
    let cancelled = false;
    api<{ data: string[] }>(`/${consulateSlug}/availability/dates`, { lang, query: { service }, noStore: true })
      .then((r) => !cancelled && setDates(r.data))
      .catch(() => !cancelled && setDates([]));
    return () => {
      cancelled = true;
    };
  }, [service, consulateSlug, lang]);

  // Créneaux du jour choisi.
  useEffect(() => {
    setSlots([]);
    setSlotId(null);
    if (!service || !date) return;
    let cancelled = false;
    api<{ data: Slot[] }>(`/${consulateSlug}/availability/slots`, { lang, query: { service, date }, noStore: true })
      .then((r) => !cancelled && setSlots(r.data))
      .catch(() => !cancelled && setSlots([]));
    return () => {
      cancelled = true;
    };
  }, [date, service, consulateSlug, lang]);

  const locale = lang === 'ar' ? 'ar' : lang === 'en' ? 'en-GB' : 'fr-FR';
  const fmtDate = (iso: string, opts: Intl.DateTimeFormatOptions) =>
    new Date(`${iso}T12:00:00`).toLocaleDateString(locale, opts);

  async function submit(e: React.FormEvent) {
    e.preventDefault();
    setError(null);
    if (!consent) {
      setError(t(lang, 'consent'));
      return;
    }
    setSubmitting(true);
    try {
      const res = await api<{ reference: string; access_token: string }>(`/${consulateSlug}/appointments`, {
        method: 'POST',
        lang,
        body: { service, date, slot_id: slotId, ...form, consent, website },
      });
      // Jeton dans le fragment (#) : il n'est ni envoyé au serveur ni journalisé.
      router.push(`${base}/appointment/confirmation/${res.reference}#token=${res.access_token}`);
    } catch (err) {
      setError(err instanceof ApiError ? err.message : t(lang, 'error'));
      setSubmitting(false);
    }
  }

  const stepClass = 'mb-3 flex items-center gap-2 text-lg font-bold text-brand';
  const badge = 'flex size-7 items-center justify-center rounded-full bg-brand text-sm text-white';

  return (
    <div className="space-y-6">
      <section className="card p-6">
        <h2 className={stepClass}><span className={badge}>1</span>{t(lang, 'step_service')}</h2>
        <select className="input" value={service} onChange={(e) => setService(e.target.value)} aria-label={t(lang, 'step_service')}>
          <option value="">{t(lang, 'choose')}</option>
          {bookable.map((s) => (
            <option key={s.slug} value={s.slug}>{s.name}</option>
          ))}
        </select>
      </section>

      {service && (
        <section className="card p-6">
          <h2 className={stepClass}><span className={badge}>2</span>{t(lang, 'step_date')}</h2>
          {dates === null && <p className="text-slate-500">{t(lang, 'loading')}</p>}
          {dates !== null && dates.length === 0 && <p className="text-slate-600">{t(lang, 'no_dates')}</p>}
          {dates && dates.length > 0 && (
            <div className="grid grid-cols-2 gap-2 sm:grid-cols-3 md:grid-cols-4" role="radiogroup" aria-label={t(lang, 'step_date')}>
              {dates.map((d) => {
                const selected = d === date;
                return (
                  <button
                    key={d}
                    type="button"
                    role="radio"
                    aria-checked={selected}
                    onClick={() => setDate(d)}
                    className={`rounded-lg border px-3 py-2 text-start text-sm transition ${selected ? 'border-transparent bg-brand text-white' : 'border-slate-300 bg-white hover:border-slate-500'}`}
                  >
                    <span className="block text-xs uppercase opacity-80">{fmtDate(d, { weekday: 'long' })}</span>
                    <span className="font-semibold">{fmtDate(d, { day: 'numeric', month: 'short', year: 'numeric' })}</span>
                  </button>
                );
              })}
            </div>
          )}
        </section>
      )}

      {date && (
        <section className="card p-6">
          <h2 className={stepClass}><span className={badge}>3</span>{t(lang, 'step_slot')}</h2>
          {slots.length === 0 && <p className="text-slate-600">{t(lang, 'no_slots')}</p>}
          <div className="grid gap-2 sm:grid-cols-2">
            {slots.map((s) => {
              const selected = s.id === slotId;
              return (
                <label
                  key={s.id}
                  className={`flex cursor-pointer items-center justify-between rounded-lg border p-3 text-sm ${selected ? 'border-[var(--brand)] ring-2 ring-[var(--brand)]/30' : 'border-slate-300 hover:border-slate-500'}`}
                >
                  <span className="flex items-center gap-3">
                    <input type="radio" name="slot" className="size-4" checked={selected} onChange={() => setSlotId(s.id)} />
                    <span className="font-semibold" dir="ltr">{s.start_time} – {s.end_time}</span>
                  </span>
                  <span className="text-xs text-slate-500">{seatsLabel(lang, s.remaining)}</span>
                </label>
              );
            })}
          </div>
        </section>
      )}

      {slotId !== null && (
        <form onSubmit={submit} className="card space-y-4 p-6" noValidate>
          <h2 className={stepClass}><span className={badge}>4</span>{t(lang, 'step_details')}</h2>

          <div className="hp" aria-hidden="true">
            <label>Website <input tabIndex={-1} autoComplete="off" value={website} onChange={(e) => setWebsite(e.target.value)} /></label>
          </div>

          <div className="grid gap-4 sm:grid-cols-2">
            <div>
              <label className="label" htmlFor="bk-name">{t(lang, 'name')} *</label>
              <input id="bk-name" className="input" required maxLength={120} autoComplete="name" value={form.full_name} onChange={(e) => setForm({ ...form, full_name: e.target.value })} />
            </div>
            <div>
              <label className="label" htmlFor="bk-phone">{t(lang, 'phone')} *</label>
              <input id="bk-phone" type="tel" dir="ltr" className="input" required autoComplete="tel" value={form.phone} onChange={(e) => setForm({ ...form, phone: e.target.value })} />
            </div>
            <div className="sm:col-span-2">
              <label className="label" htmlFor="bk-email">{t(lang, 'email')} *</label>
              <input id="bk-email" type="email" dir="ltr" className="input" required autoComplete="email" value={form.email} onChange={(e) => setForm({ ...form, email: e.target.value })} />
            </div>
            <div className="sm:col-span-2">
              <label className="label" htmlFor="bk-notes">{t(lang, 'message')} ({t(lang, 'optional')})</label>
              <textarea id="bk-notes" rows={3} maxLength={500} className="input" value={form.notes} onChange={(e) => setForm({ ...form, notes: e.target.value })} />
            </div>
          </div>

          <label className="flex items-start gap-3 text-sm text-slate-700">
            <input type="checkbox" className="mt-1 size-4" checked={consent} onChange={(e) => setConsent(e.target.checked)} required />
            <span>{t(lang, 'consent')}</span>
          </label>

          {error && <p role="alert" className="rounded-lg bg-red-50 p-3 text-sm text-red-700">{error}</p>}

          <button type="submit" className="btn-primary w-full py-3 text-base sm:w-auto" disabled={submitting}>
            {submitting ? t(lang, 'saving') : t(lang, 'confirm')}
          </button>
        </form>
      )}
    </div>
  );
}
