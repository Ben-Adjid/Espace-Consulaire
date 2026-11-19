'use client';

import { useEffect, useState } from 'react';
import { ApiError } from '@/lib/api';
import { bff } from '@/lib/bff';
import type { Lang } from '@/lib/types';
import { t } from '@/lib/i18n';

interface Candidate { id: number; name: string | null }
interface Result { role: string; label: string; status: 'elected' | 'second_round' | 'vacant'; winner: string | null; votes: number; turnout: number }
interface Election {
  id: number;
  year: number;
  phase: 'candidacy' | 'voting' | 'closed' | 'proclaimed' | null;
  round: number;
  opens_at: string | null;
  closes_at: string | null;
  candidates: Record<string, Candidate[]>;
  my_candidacy: { role: string; status: 'pending' | 'approved' | 'rejected' } | null;
  has_voted: boolean;
  can_candidate: boolean;
  can_withdraw: boolean;
  can_vote: boolean;
  results: { round: number; roles: Result[] }[] | null;
  chain: { valid: boolean; length: number; head: string | null } | null;
  attestation: { count: number; required: number; attested: boolean } | null;
  is_partial: boolean;
  scope_roles: string[] | null;
}
interface Condition { role: string; label: string; unmet: string[]; requires_document: boolean }
interface Listing { data: Election[]; meta: { eligible: boolean; roles: Record<string, string>; conditions: Condition[]; is_commission: boolean } }

const errorText = (lang: Lang, err: unknown) => (err instanceof ApiError ? err.message : t(lang, 'error'));

function date(lang: Lang, iso: string | null) {
  if (!iso) return '';
  return new Date(iso).toLocaleString(lang === 'ar' ? 'ar' : lang === 'en' ? 'en-GB' : 'fr-FR', { dateStyle: 'medium', timeStyle: 'short' });
}

/** Élections en ligne : conditions, candidature, vote anonyme à chaque tour, reçu, résultats publiés. */
export default function AssociationElections({ consulateSlug, lang }: { consulateSlug: string; lang: Lang }) {
  const [listing, setListing] = useState<Listing | null>(null);
  const [error, setError] = useState<string | null>(null);
  const [message, setMessage] = useState<string | null>(null);
  const [receipt, setReceipt] = useState<string | null>(null);
  const [verifyCode, setVerifyCode] = useState('');
  const [verified, setVerified] = useState<string | null>(null);
  const [busy, setBusy] = useState(false);
  const [familyOk, setFamilyOk] = useState<Record<number, boolean>>({});
  const [role, setRole] = useState<Record<number, string>>({});
  const [choice, setChoice] = useState<Record<number, Record<string, number>>>({});

  function load() {
    bff<Listing>(consulateSlug, 'citizen/association-elections', { lang })
      .then(setListing)
      .catch((err) => setError(errorText(lang, err)));
  }
  useEffect(load, [consulateSlug, lang]);

  async function run(fn: () => Promise<unknown>, ok: string) {
    setError(null);
    setMessage(null);
    setBusy(true);
    try {
      await fn();
      setMessage(ok);
      load();
    } catch (err) {
      setError(errorText(lang, err));
    } finally {
      setBusy(false);
    }
  }

  if (!listing) {
    return error ? <p role="alert" className="rounded-lg bg-red-50 p-3 text-sm text-red-700">{error}</p> : <p className="text-slate-600">{t(lang, 'loading')}</p>;
  }

  const roles = listing.meta.roles;
  const unmetFor = (r: string) => listing.meta.conditions.find((c) => c.role === r)?.unmet ?? [];

  return (
    <div className="space-y-6">
      {error && <p role="alert" className="rounded-lg bg-red-50 p-3 text-sm text-red-700">{error}</p>}
      {message && <p role="status" className="rounded-lg bg-green-50 p-3 text-sm text-green-800">{message}</p>}

      {receipt && (
        <div className="rounded-xl border-2 border-green-300 bg-green-50 p-4 text-sm text-green-900">
          <p className="font-bold">{t(lang, 'elec_receipt_title')}</p>
          <p className="mt-1 font-mono text-lg tracking-widest" dir="ltr">{receipt}</p>
          <p className="mt-2">{t(lang, 'elec_receipt_help')}</p>
        </div>
      )}

      {listing.data.length === 0 && <p className="card p-5 text-slate-600">{t(lang, 'elec_none')}</p>}

      {listing.data.map((e) => {
        const phaseLabel = e.phase === 'candidacy' ? t(lang, 'elec_phase_candidacy') : e.phase === 'voting' ? t(lang, 'elec_phase_voting') : e.phase === 'closed' ? t(lang, 'elec_phase_closed') : t(lang, 'elec_phase_proclaimed');
        const picks = choice[e.id] ?? {};
        return (
          <section key={e.id} className="card space-y-4 p-5">
            <div className="flex flex-wrap items-center justify-between gap-2">
              <div>
                <h2 className="font-bold text-brand">{e.is_partial ? t(lang, 'elec_partial_title') : t(lang, 'elec_title')} {e.year}</h2>
                {e.is_partial && e.scope_roles && (
                  <p className="text-sm text-slate-600">{t(lang, 'elec_partial_scope')} : {e.scope_roles.map((r) => roles[r] ?? r).join(', ')}</p>
                )}
              </div>
              <span className="rounded-full bg-slate-100 px-3 py-1 text-xs font-bold text-slate-700">
                {phaseLabel}{e.phase === 'voting' && e.round > 1 ? ` · ${t(lang, 'elec_round2')}` : ''}
              </span>
            </div>
            <p className="text-sm text-slate-600">
              {e.phase === 'candidacy'
                ? <>{t(lang, 'elec_voting_from')} : {date(lang, e.opens_at)} · {t(lang, 'elec_closes')} : {date(lang, e.closes_at)}</>
                : <>{t(lang, 'elec_voting_since')} : {date(lang, e.opens_at)} · {t(lang, 'elec_closes')} : {date(lang, e.closes_at)}</>}
            </p>

            {e.my_candidacy && (
              <p className="rounded-lg bg-slate-50 p-3 text-sm">
                {t(lang, 'elec_my_candidacy')} : <strong>{roles[e.my_candidacy.role] ?? e.my_candidacy.role}</strong> —{' '}
                {t(lang, e.my_candidacy.status === 'approved' ? 'elec_status_approved' : e.my_candidacy.status === 'rejected' ? 'elec_status_rejected' : 'elec_status_pending')}
              </p>
            )}

            {e.can_candidate && (
              <div className="space-y-3">
                <p className="text-sm font-semibold text-slate-900">{t(lang, 'elec_conditions')}</p>
                <ul className="space-y-1 text-sm">
                  {listing.meta.conditions.map((c) => (
                    <li key={c.role} className={c.unmet.length ? 'text-red-700' : 'text-green-800'}>
                      {c.label} : {c.unmet.length ? c.unmet.join(' ; ') : t(lang, 'elec_conditions_ok')}
                      {c.requires_document ? ` (${t(lang, 'elec_needs_document')})` : ''}
                    </li>
                  ))}
                </ul>
                <label className="flex items-start gap-2 text-sm text-slate-700">
                  <input type="checkbox" className="mt-1" checked={!!familyOk[e.id]} onChange={(ev) => setFamilyOk({ ...familyOk, [e.id]: ev.target.checked })} />
                  <span>{t(lang, 'elec_family_declaration')}</span>
                </label>
                <div className="flex flex-col gap-3 sm:flex-row">
                  <select className="input sm:max-w-xs" value={role[e.id] ?? ''} onChange={(ev) => setRole({ ...role, [e.id]: ev.target.value })} aria-label={t(lang, 'elec_choose_role')}>
                    <option value="">{t(lang, 'choose')}</option>
                    {Object.entries(roles).map(([k, label]) => <option key={k} value={k}>{label}{unmetFor(k).length ? ` (${t(lang, 'elec_unavailable')})` : ''}</option>)}
                  </select>
                  <button type="button" disabled={busy || !role[e.id] || !familyOk[e.id] || unmetFor(role[e.id]).length > 0}
                    onClick={() => run(() => bff(consulateSlug, `citizen/association-elections/${e.id}/candidacy`, { method: 'POST', lang, body: { role: role[e.id], declare_no_family_link: true } }), t(lang, 'elec_applied'))}
                    className="btn-primary px-5 py-2.5">{t(lang, 'elec_apply')}</button>
                </div>
              </div>
            )}
            {e.can_withdraw && (
              <button type="button" disabled={busy}
                onClick={() => run(() => bff(consulateSlug, `citizen/association-elections/${e.id}/candidacy`, { method: 'DELETE', lang }), t(lang, 'elec_withdrawn'))}
                className="text-sm font-semibold text-red-700 hover:underline">{t(lang, 'elec_withdraw')}</button>
            )}

            {e.phase === 'voting' && e.has_voted && <p className="rounded-lg bg-green-50 p-3 text-sm text-green-800">{t(lang, 'elec_voted')}</p>}

            {e.can_vote && (
              <form className="space-y-4" onSubmit={async (ev) => {
                ev.preventDefault();
                setError(null);
                setBusy(true);
                try {
                  const r = await bff<{ receipt: string }>(consulateSlug, `citizen/association-elections/${e.id}/vote`, { method: 'POST', lang, body: { candidate_ids: Object.values(picks) } });
                  setReceipt(r.receipt);
                  setMessage(t(lang, 'elec_voted'));
                  load();
                } catch (err) {
                  setError(errorText(lang, err));
                } finally {
                  setBusy(false);
                }
              }}>
                <p className="text-sm text-slate-600">{t(lang, 'elec_vote_help')}</p>
                {Object.entries(e.candidates).map(([r, list]) => (
                  <fieldset key={r} className="space-y-2">
                    <legend className="text-sm font-semibold text-slate-900">{roles[r] ?? r}</legend>
                    {list.map((c) => (
                      <label key={c.id} className="flex items-center gap-2 text-sm">
                        <input type="radio" name={`elec-${e.id}-${r}`} checked={picks[r] === c.id}
                          onChange={() => setChoice({ ...choice, [e.id]: { ...picks, [r]: c.id } })} />
                        {c.name}
                      </label>
                    ))}
                  </fieldset>
                ))}
                <button type="submit" disabled={busy || Object.keys(picks).length === 0} className="btn-primary px-5 py-2.5">{t(lang, 'elec_vote_submit')}</button>
              </form>
            )}

            {e.results && (
              <div className="space-y-3">
                <p className="text-sm font-semibold text-slate-900">{t(lang, 'elec_results')}</p>
                {e.results.map((round) => (
                  <div key={round.round} className="rounded-lg bg-slate-50 p-3 text-sm">
                    <p className="font-semibold">{round.round === 1 ? t(lang, 'elec_round1') : t(lang, 'elec_round2')}</p>
                    <ul className="mt-1 space-y-1">
                      {round.roles.map((r) => (
                        <li key={r.role}>
                          {r.label} : {r.status === 'elected' ? `${t(lang, 'elec_elected')} ${r.winner}` : r.status === 'second_round' ? t(lang, 'elec_go_round2') : t(lang, 'elec_vacant')}
                          {r.turnout > 0 && <span className="text-slate-500"> ({r.votes} / {r.turnout})</span>}
                        </li>
                      ))}
                    </ul>
                  </div>
                ))}
                {e.attestation && (
                  <p className={`text-sm ${e.attestation.attested ? 'text-green-800' : 'text-slate-600'}`}>
                    {t(lang, 'elec_attested')} : {e.attestation.count} / {e.attestation.required}
                  </p>
                )}
                {listing.meta.is_commission && e.attestation && (
                  <button type="button" disabled={busy}
                    onClick={() => run(() => bff(consulateSlug, `citizen/association-elections/${e.id}/attest`, { method: 'POST', lang }), t(lang, 'elec_attest_done'))}
                    className="rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">{t(lang, 'elec_attest')}</button>
                )}
                {e.chain && (
                  <p className={`text-xs ${e.chain.valid ? 'text-green-800' : 'text-red-700'}`}>
                    {e.chain.valid ? t(lang, 'elec_chain_ok') : t(lang, 'elec_chain_ko')} · {e.chain.length}
                  </p>
                )}
              </div>
            )}

            {(e.phase === 'voting' || e.phase === 'closed' || e.phase === 'proclaimed') && (
              <div className="space-y-2 border-t border-slate-200 pt-3">
                <p className="text-sm font-semibold text-slate-900">{t(lang, 'elec_verify_receipt')}</p>
                <div className="flex flex-col gap-2 sm:flex-row">
                  <input className="input sm:max-w-xs" value={verifyCode} onChange={(ev) => setVerifyCode(ev.target.value)} aria-label={t(lang, 'elec_receipt_code')} dir="ltr" />
                  <button type="button" disabled={busy || !verifyCode.trim()}
                    onClick={async () => {
                      try {
                        const r = await bff<{ valid: boolean }>(consulateSlug, `citizen/association-elections/${e.id}/receipt`, { method: 'POST', lang, body: { code: verifyCode.trim() } });
                        setVerified(r.valid ? t(lang, 'elec_receipt_ok') : t(lang, 'elec_receipt_ko'));
                      } catch (err) {
                        setVerified(errorText(lang, err));
                      }
                    }}
                    className="rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">{t(lang, 'elec_verify')}</button>
                </div>
                {verified && <p className="text-sm text-slate-700">{verified}</p>}
              </div>
            )}
          </section>
        );
      })}

      {!listing.meta.eligible && listing.data.length > 0 && <p className="text-sm text-slate-500">{t(lang, 'elec_not_eligible')}</p>}
    </div>
  );
}
