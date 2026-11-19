'use client';

import Link from 'next/link';
import { useRouter } from 'next/navigation';
import { useEffect, useState } from 'react';
import { api, ApiError } from '@/lib/api';
import { bff } from '@/lib/bff';
import type { Lang, Service, Slot } from '@/lib/types';
import { seatsLabel, t } from '@/lib/i18n';

/** Coordonnées du compte citoyen : pré-remplies et verrouillées, la réservation est rattachée au compte. */
interface Account {
  full_name: string;
  email: string;
  phone: string;
  residence_country: string;
}

interface Props {
  consulateSlug: string;
  lang: Lang;
  base: string;
  services: Service[];
  preselected?: string;
  /** Pays de résidence couverts par ce consulat. */
  countries: string[];
  /** Nationalité déclarée : pays représenté par le consulat. */
  nationalityLabel: string;
  /** Compte citoyen actif (obligatoire pour réserver). */
  account?: Account | null;
  /** Rendez-vous pour une personne à charge (depuis l'espace famille). */
  onBehalfInit?: { name: string; relation: string } | null;
}

/** Consulat suggéré par l'API quand le pays de résidence relève d'un autre consulat. */
interface SuggestedConsulate {
  slug: string;
  name: string;
}

type Step = 1 | 2 | 3 | 4;

/**
 * Réservation en 4 étapes : service → date et créneau → coordonnées → récapitulatif.
 * Chaque étape a « Retour » à gauche et l'action principale à droite ; le récapitulatif permet de modifier chaque bloc.
 * Les saisies sont conservées quand on revient en arrière. Les places sont vérifiées par l'API.
 */
export default function BookingWizard({ consulateSlug, lang, base, services, preselected, countries, nationalityLabel, account, onBehalfInit }: Props) {
  const router = useRouter();
  const bookable = services.filter((s) => s.allow_appointment);

  const [step, setStep] = useState<Step>(1);
  const [service, setService] = useState(preselected && bookable.some((s) => s.slug === preselected) ? preselected : '');
  const [dates, setDates] = useState<string[] | null>(null);
  const [date, setDate] = useState('');
  const [slots, setSlots] = useState<Slot[]>([]);
  const [slotId, setSlotId] = useState<number | null>(null);
  const [form, setForm] = useState({
    full_name: account?.full_name ?? '',
    email: account?.email ?? '',
    phone: account?.phone ?? '',
    notes: '',
  });
  const [residence, setResidence] = useState(account?.residence_country ?? countries[0] ?? '');
  const [nationality, setNationality] = useState(false);
  // Procuration : rendez-vous pris pour une autre personne (enfant mineur, mandant…).
  // Pré-rempli quand on vient de l'espace famille (?for=…&rel=…).
  const [forOther, setForOther] = useState(!!onBehalfInit);
  const [onBehalf, setOnBehalf] = useState(onBehalfInit ?? { name: '', relation: 'child' });
  const [consent, setConsent] = useState(false);
  const [website, setWebsite] = useState(''); // honeypot
  const [error, setError] = useState<string | null>(null);
  const [suggested, setSuggested] = useState<SuggestedConsulate | null>(null);
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
  const selectedService = bookable.find((s) => s.slug === service);
  const selectedSlot = slots.find((s) => s.id === slotId);

  function goTo(next: Step) {
    setError(null);
    setStep(next);
    window.scrollTo({ top: 0, behavior: 'smooth' });
  }

  async function submit(e: React.FormEvent) {
    e.preventDefault();
    setError(null);
    setSuggested(null);
    if (!consent) {
      setError(t(lang, 'consent'));
      // Le message est en haut de l'assistant : on le ramène à l'écran pour que le citoyen le voie.
      setTimeout(() => document.querySelector('[role="alert"]')?.scrollIntoView({ behavior: 'smooth', block: 'center' }), 0);
      return;
    }
    setSubmitting(true);
    try {
      // Le compte est identifié par le cookie de session (proxy) : aucun jeton n'est lu par la page.
      const res = await bff<{ reference: string; access_token: string }>(consulateSlug, 'appointments', {
        method: 'POST',
        lang,
        body: {
          service, date, slot_id: slotId, ...form, consent, website, nationality, residence_country: residence,
          ...(forOther ? { on_behalf_name: onBehalf.name, on_behalf_relation: onBehalf.relation } : {}),
        },
      });
      // Jeton dans le fragment (#) : il n'est ni envoyé au serveur ni journalisé.
      router.push(`${base}/appointment/confirmation/${res.reference}#token=${res.access_token}`);
    } catch (err) {
      setError(err instanceof ApiError ? err.message : t(lang, 'error'));
      const body = err instanceof ApiError ? (err.body as { suggested_consulate?: SuggestedConsulate | null }) : null;
      setSuggested(body?.suggested_consulate ?? null);
      setSubmitting(false);
    }
  }

  const steps = [t(lang, 'step_service'), t(lang, 'step_date'), t(lang, 'step_details'), t(lang, 'step_recap')];
  const stepClass = 'mb-3 flex items-center gap-2 text-lg font-bold text-brand';
  const badge = 'flex size-7 items-center justify-center rounded-full bg-brand text-sm text-white';
  const footer = 'flex flex-col-reverse gap-3 border-t border-slate-200 pt-5 sm:flex-row sm:items-center sm:justify-between';
  const backBtn = 'btn border border-slate-300 bg-white text-slate-700 hover:bg-slate-50 sm:w-auto';
  const nextBtn = 'btn-primary w-full px-6 py-3 sm:w-auto';

  return (
    <div className="space-y-6">
      {/* Progression */}
      <ol className="grid grid-cols-4 gap-2" aria-label={t(lang, 'progress')}>
        {steps.map((label, i) => {
          const n = (i + 1) as Step;
          const done = n < step;
          const current = n === step;
          return (
            <li key={label} className="min-w-0">
              <div className={`h-1.5 rounded-full ${done || current ? 'bg-brand' : 'bg-slate-200'}`} />
              <p className={`mt-2 truncate text-xs ${current ? 'font-bold text-slate-900' : 'text-slate-500'}`} aria-current={current ? 'step' : undefined}>
                {n}. {label}
              </p>
            </li>
          );
        })}
      </ol>

      {error && (
        <div role="alert" className="rounded-lg bg-red-50 p-3 text-sm text-red-700">
          <p>{error}</p>
          {suggested && (
            <Link href={`/${suggested.slug}/${lang}/appointment`} className="mt-2 inline-block font-semibold underline">
              {suggested.name} →
            </Link>
          )}
        </div>
      )}

      {/* Étape 1 : service */}
      {step === 1 && (
        <section className="card space-y-5 p-6">
          <h2 className={stepClass}><span className={badge}>1</span>{t(lang, 'step_service')}</h2>
          <select className="input" value={service} onChange={(e) => setService(e.target.value)} aria-label={t(lang, 'step_service')}>
            <option value="">{t(lang, 'choose')}</option>
            {bookable.map((s) => (
              <option key={s.slug} value={s.slug}>{s.name}</option>
            ))}
          </select>
          <div className={footer}>
            <span />
            <button type="button" className={nextBtn} disabled={!service} onClick={() => goTo(2)}>
              {t(lang, 'btn_next')} →
            </button>
          </div>
        </section>
      )}

      {/* Étape 2 : date et créneau */}
      {step === 2 && (
        <section className="card space-y-6 p-6">
          <div>
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
          </div>

          {date && (
            <div>
              <h2 className={stepClass}><span className={badge}>2</span>{t(lang, 'step_slot')}</h2>
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
            </div>
          )}

          <div className={footer}>
            <button type="button" className={backBtn} onClick={() => goTo(1)}>← {t(lang, 'btn_back')}</button>
            <button type="button" className={nextBtn} disabled={slotId === null} onClick={() => goTo(3)}>
              {t(lang, 'btn_next')} →
            </button>
          </div>
        </section>
      )}

      {/* Étape 3 : coordonnées et résidence */}
      {step === 3 && (
        <section className="card space-y-5 p-6">
          <h2 className={stepClass}><span className={badge}>3</span>{t(lang, 'step_details')}</h2>

          <div className="hp" aria-hidden="true">
            <label>Website <input tabIndex={-1} autoComplete="off" value={website} onChange={(e) => setWebsite(e.target.value)} /></label>
          </div>

          <div className="grid gap-4 sm:grid-cols-2">
            <div>
              <label className="label" htmlFor="bk-name">{t(lang, 'name')} *</label>
              <input id="bk-name" className="input" required readOnly={!!account} maxLength={120} autoComplete="name" value={form.full_name} onChange={(e) => setForm({ ...form, full_name: e.target.value })} />
            </div>
            <div>
              <label className="label" htmlFor="bk-phone">{t(lang, 'phone')} *</label>
              <input id="bk-phone" type="tel" dir="ltr" className="input" required readOnly={!!account} autoComplete="tel" value={form.phone} onChange={(e) => setForm({ ...form, phone: e.target.value })} />
            </div>
            <div className="sm:col-span-2">
              <label className="label" htmlFor="bk-email">{t(lang, 'email')} *</label>
              <input id="bk-email" type="email" dir="ltr" className="input" required readOnly={!!account} autoComplete="email" value={form.email} onChange={(e) => setForm({ ...form, email: e.target.value })} />
            </div>
            <div className="sm:col-span-2">
              <label className="label" htmlFor="bk-notes">{t(lang, 'message')} ({t(lang, 'optional')})</label>
              <textarea id="bk-notes" rows={3} maxLength={500} className="input" value={form.notes} onChange={(e) => setForm({ ...form, notes: e.target.value })} />
            </div>
          </div>

          <div className="space-y-3 rounded-lg border border-slate-200 bg-slate-50 p-4">
            <div>
              <label className="label" htmlFor="bk-residence">{t(lang, 'residence_country')} *</label>
              <select id="bk-residence" className="input" required disabled={!!account} value={residence} onChange={(e) => setResidence(e.target.value)}>
                {countries.map((c) => (
                  <option key={c} value={c}>{c}</option>
                ))}
              </select>
              <p className="mt-1 text-xs text-slate-500">{t(lang, 'residence_help')}</p>
            </div>
            <label className="flex items-start gap-3 text-sm text-slate-700">
              <input type="checkbox" className="mt-1 size-4" checked={nationality} onChange={(e) => setNationality(e.target.checked)} required />
              <span>{t(lang, 'nationality_confirm').replace('{nationality}', nationalityLabel)}</span>
            </label>
          </div>

          <fieldset className="space-y-3 rounded-lg border border-slate-200 p-4">
            <legend className="px-1 text-sm font-semibold text-slate-700">{t(lang, 'on_behalf_toggle')}</legend>
            <div className="flex flex-wrap gap-4 text-sm">
              <label className="flex items-center gap-2">
                <input type="radio" name="for-whom" className="size-4" checked={!forOther} onChange={() => setForOther(false)} />
                {t(lang, 'on_behalf_self')}
              </label>
              <label className="flex items-center gap-2">
                <input type="radio" name="for-whom" className="size-4" checked={forOther} onChange={() => setForOther(true)} />
                {t(lang, 'on_behalf_other')}
              </label>
            </div>
            {forOther && (
              <div className="grid gap-3 sm:grid-cols-2">
                <div>
                  <label className="label" htmlFor="bk-behalf-name">{t(lang, 'on_behalf_name')} *</label>
                  <input id="bk-behalf-name" className="input" required maxLength={120} value={onBehalf.name} onChange={(e) => setOnBehalf({ ...onBehalf, name: e.target.value })} />
                </div>
                <div>
                  <label className="label" htmlFor="bk-behalf-rel">{t(lang, 'on_behalf_relation')} *</label>
                  <select id="bk-behalf-rel" className="input" value={onBehalf.relation} onChange={(e) => setOnBehalf({ ...onBehalf, relation: e.target.value })}>
                    {['child', 'spouse', 'parent', 'mandate', 'other'].map((r) => (
                      <option key={r} value={r}>{t(lang, `relation_${r}`)}</option>
                    ))}
                  </select>
                </div>
                <p className="text-xs text-slate-500 sm:col-span-2">{t(lang, 'on_behalf_hint')}</p>
              </div>
            )}
          </fieldset>

          <div className={footer}>
            <button type="button" className={backBtn} onClick={() => goTo(2)}>← {t(lang, 'btn_back')}</button>
            <button
              type="button"
              className={nextBtn}
              disabled={!form.full_name || !form.phone || !form.email || !nationality || !residence || (forOther && onBehalf.name.trim().length < 2)}
              onClick={() => goTo(4)}
            >
              {t(lang, 'btn_next')} →
            </button>
          </div>
        </section>
      )}

      {/* Étape 4 : récapitulatif et envoi */}
      {step === 4 && (
        <form onSubmit={submit} className="card space-y-5 p-6" noValidate>
          <h2 className={stepClass}><span className={badge}>4</span>{t(lang, 'step_recap')}</h2>

          <dl className="divide-y divide-slate-200 rounded-lg border border-slate-200">
            <RecapRow label={t(lang, 'service')} value={selectedService?.name ?? '—'} onEdit={() => goTo(1)} editLabel={t(lang, 'btn_edit')} />
            <RecapRow
              label={t(lang, 'date')}
              value={date ? `${fmtDate(date, { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' })} · ${selectedSlot ? `${selectedSlot.start_time} – ${selectedSlot.end_time}` : '—'}` : '—'}
              onEdit={() => goTo(2)}
              editLabel={t(lang, 'btn_edit')}
            />
            <RecapRow label={t(lang, 'name')} value={form.full_name} onEdit={() => goTo(3)} editLabel={t(lang, 'btn_edit')} />
            <RecapRow label={t(lang, 'email')} value={form.email} onEdit={() => goTo(3)} editLabel={t(lang, 'btn_edit')} />
            <RecapRow label={t(lang, 'phone')} value={form.phone} onEdit={() => goTo(3)} editLabel={t(lang, 'btn_edit')} />
            <RecapRow label={t(lang, 'residence_country')} value={residence} onEdit={() => goTo(3)} editLabel={t(lang, 'btn_edit')} />
            {forOther && (
              <RecapRow
                label={t(lang, 'on_behalf_for')}
                value={`${onBehalf.name} (${t(lang, `relation_${onBehalf.relation}`)})`}
                onEdit={() => goTo(3)}
                editLabel={t(lang, 'btn_edit')}
              />
            )}
          </dl>

          <label className="flex items-start gap-3 text-sm text-slate-700">
            <input type="checkbox" className="mt-1 size-4" checked={consent} onChange={(e) => setConsent(e.target.checked)} />
            <span>{t(lang, 'consent')}</span>
          </label>

          <div className={footer}>
            <button type="button" className={backBtn} onClick={() => goTo(3)}>← {t(lang, 'btn_back')}</button>
            <button type="submit" className={nextBtn} disabled={submitting}>
              {submitting ? t(lang, 'saving') : t(lang, 'confirm')}
            </button>
          </div>
        </form>
      )}
    </div>
  );
}

/** Une ligne du récapitulatif avec son bouton « Modifier ». */
function RecapRow({ label, value, onEdit, editLabel }: { label: string; value: string; onEdit: () => void; editLabel: string }) {
  return (
    <div className="flex items-start justify-between gap-4 p-3">
      <div className="min-w-0">
        <dt className="text-xs font-semibold uppercase tracking-wide text-slate-500">{label}</dt>
        <dd className="break-words text-sm font-medium text-slate-900">{value}</dd>
      </div>
      <button type="button" onClick={onEdit} className="shrink-0 text-sm font-semibold text-brand underline-offset-2 hover:underline">
        {editLabel}
      </button>
    </div>
  );
}
