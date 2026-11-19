'use client';

import Link from 'next/link';
import { useRouter } from 'next/navigation';
import { useEffect, useState } from 'react';
import { ApiError } from '@/lib/api';
import { bff } from '@/lib/bff';
import type { Lang } from '@/lib/types';
import { t } from '@/lib/i18n';
import { useConfirm } from './ConfirmDialog';

export interface PreDoc { id: number; label: string; original_name: string; size: number; status: string; status_label?: string; review_note: string | null }

/** Badge de l'examen pièce par pièce : à vérifier (gris), acceptée (vert), refusée (rouge). */
function DocStatus({ status, label }: { status: string; label?: string }) {
  const style = status === 'accepted' ? 'bg-green-100 text-green-800' : status === 'rejected' ? 'bg-red-100 text-red-800' : 'bg-slate-100 text-slate-600';
  return <span className={`shrink-0 rounded-full px-2.5 py-0.5 text-xs font-semibold ${style}`}>{label ?? status}</span>;
}
export interface PreReq {
  id: number;
  service: string;
  service_name: string;
  /** Frais en vigueur, à régler au guichet (ex. « 15 000 FCFA »). */
  fee?: string | null;
  is_urgent?: boolean;
  special_case?: string | null;
  special_case_label?: string | null;
  valid_until?: string | null;
  status: string;
  status_label: string;
  data: Record<string, string>;
  staff_note: string | null;
  editable: boolean;
  submitted_at: string | null;
  documents: PreDoc[];
  /** Pièces du service (suggestions de libellé), pièces encore manquantes, pièces selon la situation. */
  required_documents?: string[];
  missing_documents?: string[];
  conditional_documents?: string[];
  /** Historique visible du citoyen (sans le nom des agents). */
  history?: { label: string; note: string | null; by: 'citizen' | 'consulate'; at: string | null }[];
}

const FIELDS = ['nom_pere', 'nom_mere', 'lieu_naissance', 'date_naissance', 'adresse_dakar'] as const;

/** Liste des pré-demandes et création d'un brouillon pour un service. */
export function PreRequestList({ consulateSlug, lang, base, services }: { consulateSlug: string; lang: Lang; base: string; services: { slug: string; name: string }[] }) {
  const router = useRouter();
  const [items, setItems] = useState<PreReq[] | null>(null);
  const [service, setService] = useState('');
  const [error, setError] = useState<string | null>(null);

  function load() {
    bff<{ data: PreReq[] }>(consulateSlug, 'citizen/requests', { lang })
      .then((r) => setItems(r.data))
      .catch((err) => { setItems([]); setError(err instanceof ApiError && err.status === 401 ? t(lang, 'account_need_account') : null); });
  }
  useEffect(load, [consulateSlug, lang]);

  async function create() {
    setError(null);
    try {
      const r = await bff<{ data: PreReq }>(consulateSlug, 'citizen/requests', { method: 'POST', lang, body: { service } });
      router.push(`${base}/compte/demandes/${r.data.id}`);
    } catch (err) {
      setError(err instanceof ApiError ? err.message : t(lang, 'error'));
    }
  }

  return (
    <div className="space-y-6">
      {error && <p role="alert" className="rounded-lg bg-red-50 p-3 text-sm text-red-700">{error}</p>}
      <section className="card space-y-3 p-5">
        <h2 className="font-bold text-brand">{t(lang, 'pre_new_title')}</h2>
        <p className="text-sm text-slate-600">{t(lang, 'pre_new_intro')}</p>
        <div className="flex flex-col gap-3 sm:flex-row">
          <select className="input sm:max-w-sm" value={service} onChange={(e) => setService(e.target.value)} aria-label={t(lang, 'pre_choose_service')}>
            <option value="">{t(lang, 'choose')}</option>
            {services.map((s) => <option key={s.slug} value={s.slug}>{s.name}</option>)}
          </select>
          <button type="button" disabled={!service} onClick={create} className="btn-primary px-5 py-2.5">{t(lang, 'pre_start')}</button>
        </div>
      </section>

      <section className="space-y-3">
        <h2 className="text-lg font-bold text-brand">{t(lang, 'pre_mine')}</h2>
        {items && items.length === 0 && <p className="text-slate-600">{t(lang, 'pre_none')}</p>}
        {items?.map((p) => (
          <Link key={p.id} href={`${base}/compte/demandes/${p.id}`} className="card flex items-center justify-between gap-3 p-4 hover:border-slate-400">
            <div>
              <p className="font-semibold text-slate-900">{p.service_name}</p>
              <p className="text-xs text-slate-500">{p.documents.length} {t(lang, 'pre_docs_count')}</p>
            </div>
            <span className="rounded-full bg-slate-100 px-3 py-1 text-xs font-bold text-slate-700">{p.status_label}</span>
          </Link>
        ))}
      </section>
    </div>
  );
}

/** Édition d'une pré-demande : données, pièces (PDF, JPEG, PNG, 5 Mo max), soumission. */
export function PreRequestEditor({ consulateSlug, lang, base, id }: { consulateSlug: string; lang: Lang; base: string; id: number }) {
  const [req, setReq] = useState<PreReq | null>(null);
  const [data, setData] = useState<Record<string, string>>({});
  const [label, setLabel] = useState('');
  const [message, setMessage] = useState<string | null>(null);
  const [error, setError] = useState<string | null>(null);
  const [busy, setBusy] = useState(false);
  const [dialog, confirm] = useConfirm(lang);

  function apply(r: PreReq) {
    setReq(r);
    setData(r.data ?? {});
  }
  useEffect(() => {
    bff<{ data: PreReq }>(consulateSlug, `citizen/requests/${id}`, { lang })
      .then((r) => apply(r.data))
      .catch((err) => setError(err instanceof ApiError ? err.message : t(lang, 'error')));
  }, [consulateSlug, lang, id]);

  async function run(fn: () => Promise<void>) {
    setError(null); setMessage(null); setBusy(true);
    try { await fn(); } catch (err) { setError(err instanceof ApiError ? err.message : t(lang, 'error')); } finally { setBusy(false); }
  }

  const saveData = () => run(async () => {
    const r = await bff<{ data: PreReq }>(consulateSlug, `citizen/requests/${id}`, { method: 'PUT', lang, body: { data } });
    apply(r.data);
    setMessage(t(lang, 'pre_saved'));
  });

  const upload = (file: File) => run(async () => {
    const fd = new FormData();
    fd.append('label', label || file.name);
    fd.append('file', file);
    // Envoi multipart : le proxy transmet le corps tel quel.
    const r = await fetch(`/api/bff/${consulateSlug}/citizen/requests/${id}/documents?lang=${lang}`, { method: 'POST', body: fd, credentials: 'same-origin', cache: 'no-store' });
    const json = await r.json().catch(() => ({}));
    if (!r.ok) throw new ApiError(r.status, (json as { message?: string }).message ?? t(lang, 'error'), json);
    setLabel('');
    const fresh = await bff<{ data: PreReq }>(consulateSlug, `citizen/requests/${id}`, { lang });
    apply(fresh.data);
    setMessage(t(lang, 'pre_uploaded'));
  });

  // Suppression d'un document : confirmation explicite, avec le nom du fichier concerné.
  const removeDoc = async (doc: PreDoc) => {
    const ok = await confirm({
      title: t(lang, 'doc_remove_title'),
      subject: `${doc.label} — ${doc.original_name}`,
      message: t(lang, 'doc_remove_msg'),
      confirmLabel: t(lang, 'pre_remove'),
      danger: true,
    });
    if (ok) await deleteDoc(doc.id);
  };

  const deleteDoc = (docId: number) => run(async () => {
    await bff(consulateSlug, `citizen/requests/${id}/documents/${docId}`, { method: 'DELETE', lang });
    const fresh = await bff<{ data: PreReq }>(consulateSlug, `citizen/requests/${id}`, { lang });
    apply(fresh.data);
  });

  const submit = () => run(async () => {
    const r = await bff<{ message: string; data: PreReq }>(consulateSlug, `citizen/requests/${id}/submit`, { method: 'POST', lang });
    apply(r.data);
    setMessage(r.message);
  });

  if (!req) return error ? <p role="alert" className="rounded-lg bg-red-50 p-3 text-sm text-red-700">{error}</p> : <p className="text-slate-500">{t(lang, 'loading')}</p>;

  return (
    <div className="space-y-6">
      {dialog}
      <div className="flex flex-wrap items-center justify-between gap-3">
        <h1 className="text-2xl font-bold text-brand">{req.service_name}</h1>
        {req.fee && <p className="text-sm font-semibold text-slate-700">{t(lang, 'pre_fee')} : {req.fee}</p>}
        {req.special_case_label && (
          <p className="rounded-lg bg-amber-50 p-3 text-sm text-amber-900">{t(lang, 'pre_special_case')} : <strong>{req.special_case_label}</strong></p>
        )}
        {req.valid_until && <p className="text-sm text-slate-700">{t(lang, 'pre_valid_until')} : <span dir="ltr">{req.valid_until}</span></p>}
        <span className="rounded-full bg-slate-100 px-3 py-1 text-sm font-bold text-slate-700">{req.status_label}</span>
      </div>
      {req.staff_note && <p className="rounded-lg bg-amber-50 p-3 text-sm text-amber-900"><strong>{t(lang, 'pre_staff_note')} :</strong> {req.staff_note}</p>}
      {message && <p role="status" className="rounded-lg bg-green-50 p-3 text-sm text-green-800">{message}</p>}
      {error && <p role="alert" className="rounded-lg bg-red-50 p-3 text-sm text-red-700">{error}</p>}

      <section className="card space-y-4 p-5">
        <h2 className="font-bold">{t(lang, 'pre_data_title')}</h2>
        <div className="grid gap-4 sm:grid-cols-2">
          {FIELDS.map((f) => (
            <div key={f}>
              <label className="label" htmlFor={`f-${f}`}>{t(lang, `pre_field_${f}`)}</label>
              <input id={`f-${f}`} className="input" disabled={!req.editable} value={data[f] ?? ''} onChange={(e) => setData({ ...data, [f]: e.target.value })} />
            </div>
          ))}
        </div>
        {req.editable && (
          <button type="button" disabled={busy} onClick={saveData} className="btn border border-slate-300 bg-white px-4 py-2 text-slate-700 hover:bg-slate-50">{t(lang, 'pre_save')}</button>
        )}
      </section>

      <section className="card space-y-4 p-5">
        <h2 className="font-bold">{t(lang, 'pre_docs_title')}</h2>
        {/* Pièces exigées par le service et pas encore déposées */}
        {req.editable && (req.missing_documents?.length ?? 0) > 0 && (
          <div className="rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-900">
            <p className="font-semibold">{t(lang, 'pre_missing_title')}</p>
            <ul className="mt-1 list-inside list-disc">
              {req.missing_documents!.map((m) => <li key={m}>{m}</li>)}
            </ul>
          </div>
        )}
        {req.editable && (req.required_documents?.length ?? 0) > 0 && (req.missing_documents?.length ?? 0) === 0 && (
          <p className="rounded-xl bg-green-50 p-3 text-sm text-green-800">{t(lang, 'pre_missing_none')}</p>
        )}
        {req.editable && (req.conditional_documents?.length ?? 0) > 0 && (
          <p className="text-xs text-slate-500">{t(lang, 'pre_conditional')} {req.conditional_documents!.join(' · ')}</p>
        )}
        <ul className="divide-y divide-slate-100">
          {req.documents.map((d) => (
            <li key={d.id} className="flex items-center justify-between gap-3 py-3">
              <div className="min-w-0">
                <p className="flex flex-wrap items-center gap-2 font-semibold">{d.label} <DocStatus status={d.status} label={d.status_label} /></p>
                <p className="truncate text-xs text-slate-500">{d.original_name} · {Math.max(1, Math.round(d.size / 1024))} Ko</p>
                {d.review_note && <p className="mt-1 rounded-md bg-red-50 px-2 py-1 text-xs text-red-800">{d.review_note}</p>}
              </div>
              {req.editable && (
                <button type="button" onClick={() => removeDoc(d)} className="text-sm font-semibold text-red-700 hover:underline">{t(lang, 'pre_remove')}</button>
              )}
            </li>
          ))}
          {req.documents.length === 0 && <li className="py-3 text-sm text-slate-600">{t(lang, 'pre_no_docs')}</li>}
        </ul>

        {req.editable && (
          <div className="grid gap-3 rounded-lg border border-dashed border-slate-300 p-4 sm:grid-cols-2">
            <div>
              <label className="label" htmlFor="doc-label">{t(lang, 'pre_doc_label')}</label>
              {/* Suggestions : les pièces du service (le libellé exact permet de calculer les pièces manquantes) */}
              <input id="doc-label" className="input" list="doc-suggestions" value={label} onChange={(e) => setLabel(e.target.value)} placeholder={t(lang, 'pre_doc_placeholder')} />
              <datalist id="doc-suggestions">
                {(req.missing_documents?.length ? req.missing_documents : req.required_documents ?? []).map((d) => <option key={d} value={d} />)}
              </datalist>
            </div>
            <div>
              <label className="label" htmlFor="doc-file">{t(lang, 'pre_doc_file')}</label>
              {/* Le nom de la pièce est obligatoire : sans lui, le fichier n'est pas téléversé. */}
              <input id="doc-file" type="file" accept="application/pdf,image/jpeg,image/png" className="input" disabled={busy || !label.trim()}
                onChange={(e) => { const f = e.target.files?.[0]; if (f) upload(f); e.target.value = ''; }} />
              <p className="mt-1 text-xs text-slate-500">{label.trim() ? t(lang, 'pre_doc_hint') : t(lang, 'pre_doc_need_label')}</p>
            </div>
          </div>
        )}
      </section>

      {/* Historique du dossier */}
      {(req.history?.length ?? 0) > 0 && (
        <section className="card space-y-3 p-5">
          <h2 className="font-bold">{t(lang, 'pre_history_title')}</h2>
          <ol className="space-y-3 border-s-2 border-slate-200 ps-4">
            {req.history!.map((h, i) => (
              <li key={i}>
                <p className="text-sm font-semibold text-slate-900">{h.label}</p>
                <p className="text-xs text-slate-500">
                  {h.at ? new Date(h.at).toLocaleString(lang === 'ar' ? 'ar' : lang === 'en' ? 'en-GB' : 'fr-FR', { dateStyle: 'medium', timeStyle: 'short' }) : ''}
                  {' · '}{t(lang, h.by === 'citizen' ? 'pre_history_citizen' : 'pre_history_consulate')}
                </p>
                {h.note && <p className="mt-1 text-sm text-slate-700">{h.note}</p>}
              </li>
            ))}
          </ol>
        </section>
      )}

      {req.editable && (
        <div className="flex flex-col-reverse gap-3 border-t border-slate-200 pt-5 sm:flex-row sm:justify-between">
          <Link href={`${base}/compte/demandes`} className="btn border border-slate-300 bg-white text-slate-700 hover:bg-slate-50">← {t(lang, 'btn_back')}</Link>
          <Link href={`${base}/compte/messages?demande=${id}`} className="text-sm font-semibold text-brand underline">{t(lang, 'msg_ask_about_request')}</Link>
          {req.documents.length === 0 && <p className="w-full text-sm text-slate-600">{t(lang, 'pre_submit_hint')}</p>}
          <button type="button" disabled={busy || req.documents.length === 0} onClick={submit} className="btn-primary px-6 py-2.5">{t(lang, 'pre_submit')}</button>
        </div>
      )}
    </div>
  );
}
