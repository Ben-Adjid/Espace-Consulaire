'use client';

import { useEffect, useState } from 'react';
import { ApiError } from '@/lib/api';
import { bff, bffRaw } from '@/lib/bff';
import type { Lang } from '@/lib/types';
import { t } from '@/lib/i18n';

interface Board {
  role: string;
  role_label: string;
  members_active: number;
  balance: number;
  expenses_to_approve: number;
  open_elections: number;
  mandates_ending_soon: { role: string; full_name: string; mandate_end: string }[];
}
interface Member { id: number; full_name: string; category: string; category_label: string; status: string; status_label: string; joined_at: string | null }

const errorText = (lang: Lang, err: unknown) => (err instanceof ApiError ? err.message : t(lang, 'error'));
const PUBLISH = ['president', 'vice_president', 'sports'];
const EDIT_INFO = ['president', 'vice_president', 'communication'];

/** Espace du bureau : tableau de bord, registre des adhérents (lecture), activités et informations publiques selon la fonction. */
export default function AssociationBoard({ consulateSlug, lang }: { consulateSlug: string; lang: Lang }) {
  const [board, setBoard] = useState<Board | null>(null);
  const [members, setMembers] = useState<Member[] | null>(null);
  const [error, setError] = useState<string | null>(null);
  const [message, setMessage] = useState<string | null>(null);
  const [activity, setActivity] = useState({ title: '', starts_on: new Date().toISOString().slice(0, 10), location: '', description: '' });
  const [info, setInfo] = useState({ description: '', email: '', phone: '' });

  const [newMember, setNewMember] = useState({ full_name: '', category: 'student' });
  const [journal, setJournal] = useState<{ at: string; by: string; action: string; label: string }[] | null>(null);

  async function loadJournal() {
    try {
      const r = await bff<{ data: { at: string; by: string; action: string; label: string }[] }>(consulateSlug, 'citizen/association/journal', { lang });
      setJournal(r.data);
    } catch {
      setJournal(null);
    }
  }

  // Export du registre : fichier CSV téléchargé (sans contacts), l'export est inscrit au journal côté serveur.
  async function exportRegistry() {
    setError(null);
    try {
      const res = await bffRaw(consulateSlug, 'citizen/association/members/export.csv', { lang });
      if (!res.ok) throw new Error(String(res.status));
      const blob = await res.blob();
      const url = URL.createObjectURL(blob);
      const a = document.createElement('a');
      a.href = url;
      a.download = 'registre-adherents.csv';
      a.click();
      URL.revokeObjectURL(url);
      setMessage(t(lang, 'board_export_done'));
      loadJournal();
    } catch {
      setError(t(lang, 'error'));
    }
  }

  async function reloadMembers() {
    try {
      const r = await bff<{ data: Member[] }>(consulateSlug, 'citizen/association/members', { lang });
      setMembers(r.data);
    } catch {
      setMembers(null);
    }
  }

  useEffect(() => {
    bff<{ data: Board }>(consulateSlug, 'citizen/association/board', { lang })
      .then((r) => setBoard(r.data))
      .catch((err) => setError(errorText(lang, err)));
    reloadMembers();
    loadJournal();
  }, [consulateSlug, lang]);

  async function send(fn: () => Promise<unknown>, ok: string) {
    setError(null);
    setMessage(null);
    try {
      await fn();
      setMessage(ok);
    } catch (err) {
      setError(errorText(lang, err));
    }
  }

  if (error && !board) return <p role="alert" className="rounded-lg bg-red-50 p-3 text-sm text-red-700">{error}</p>;
  if (!board) return <p className="text-slate-600">{t(lang, 'loading')}</p>;

  return (
    <div className="space-y-6">
      {error && <p role="alert" className="rounded-lg bg-red-50 p-3 text-sm text-red-700">{error}</p>}
      {message && <p role="status" className="rounded-lg bg-green-50 p-3 text-sm text-green-800">{message}</p>}

      <section className="grid gap-3 sm:grid-cols-4">
        <div className="card p-4"><p className="text-xs text-slate-500">{t(lang, 'board_your_role')}</p><p className="font-semibold">{board.role_label}</p></div>
        <div className="card p-4"><p className="text-xs text-slate-500">{t(lang, 'board_members')}</p><p className="text-2xl font-bold">{board.members_active}</p></div>
        <div className="card p-4"><p className="text-xs text-slate-500">{t(lang, 'board_expenses_pending')}</p><p className="text-2xl font-bold">{board.expenses_to_approve}</p></div>
        <div className="card p-4"><p className="text-xs text-slate-500">{t(lang, 'board_open_elections')}</p><p className="text-2xl font-bold">{board.open_elections}</p></div>
      </section>

      {board.mandates_ending_soon.length > 0 && (
        <section className="card p-4">
          <p className="mb-2 font-semibold">{t(lang, 'board_mandates_ending')}</p>
          <ul className="space-y-1 text-sm">
            {board.mandates_ending_soon.map((m, i) => <li key={i}>{m.full_name} · {m.role} · {m.mandate_end}</li>)}
          </ul>
        </section>
      )}

      {PUBLISH.includes(board.role) && (
        <form className="card grid gap-3 p-5 sm:grid-cols-2" onSubmit={(e) => { e.preventDefault(); send(async () => { await bff(consulateSlug, 'citizen/association/activities', { method: 'POST', lang, body: activity }); setActivity({ title: '', starts_on: new Date().toISOString().slice(0, 10), location: '', description: '' }); loadJournal(); }, t(lang, 'board_activity_published')); }}>
          <p className="font-semibold sm:col-span-2">{t(lang, 'board_publish_activity')}</p>
          <input className="input" required maxLength={160} placeholder={t(lang, 'board_title')} value={activity.title} onChange={(e) => setActivity({ ...activity, title: e.target.value })} />
          <input className="input" type="date" required value={activity.starts_on} onChange={(e) => setActivity({ ...activity, starts_on: e.target.value })} />
          <input className="input" maxLength={160} placeholder={t(lang, 'board_location')} value={activity.location} onChange={(e) => setActivity({ ...activity, location: e.target.value })} />
          <textarea className="input sm:col-span-2" maxLength={5000} placeholder={t(lang, 'board_description')} value={activity.description} onChange={(e) => setActivity({ ...activity, description: e.target.value })} />
          <div className="sm:col-span-2 flex justify-end"><button type="submit" className="btn-primary px-5 py-2.5">{t(lang, 'board_publish')}</button></div>
        </form>
      )}

      {EDIT_INFO.includes(board.role) && (
        <form className="card grid gap-3 p-5" onSubmit={(e) => { e.preventDefault(); const body = Object.fromEntries(Object.entries(info).filter(([, v]) => v !== '')); send(() => bff(consulateSlug, 'citizen/association/info', { method: 'PATCH', lang, body }), t(lang, 'board_info_saved')); }}>
          <p className="font-semibold">{t(lang, 'board_edit_info')}</p>
          <textarea className="input" maxLength={5000} placeholder={t(lang, 'board_description')} value={info.description} onChange={(e) => setInfo({ ...info, description: e.target.value })} />
          <input className="input" type="email" placeholder={t(lang, 'email')} value={info.email} onChange={(e) => setInfo({ ...info, email: e.target.value })} />
          <input className="input" placeholder={t(lang, 'phone')} value={info.phone} onChange={(e) => setInfo({ ...info, phone: e.target.value })} />
          <div className="flex justify-end"><button type="submit" className="btn-primary px-5 py-2.5">{t(lang, 'ledger_submit')}</button></div>
        </form>
      )}

      {board.role === 'president' && (
        <form className="card grid gap-3 p-5 sm:grid-cols-3" onSubmit={(e) => { e.preventDefault(); send(async () => { await bff(consulateSlug, 'citizen/association/members', { method: 'POST', lang, body: newMember }); setNewMember({ full_name: '', category: 'student' }); await reloadMembers(); }, t(lang, 'board_member_added')); }}>
          <p className="font-semibold sm:col-span-3">{t(lang, 'board_add_member')}</p>
          <input className="input" required maxLength={160} placeholder={t(lang, 'board_name')} value={newMember.full_name} onChange={(e) => setNewMember({ ...newMember, full_name: e.target.value })} />
          <select className="input" value={newMember.category} onChange={(e) => setNewMember({ ...newMember, category: e.target.value })}>
            <option value="student">{t(lang, 'board_cat_student')}</option>
            <option value="intern">{t(lang, 'board_cat_intern')}</option>
            <option value="alumni">{t(lang, 'board_cat_alumni')}</option>
          </select>
          <div className="flex justify-end"><button type="submit" className="btn-primary px-5 py-2.5">{t(lang, 'board_add')}</button></div>
        </form>
      )}

      <section className="space-y-3">
        <div className="flex flex-wrap items-center justify-between gap-2">
          <h2 className="text-lg font-bold text-brand">{t(lang, 'board_registry')}</h2>
          <button type="button" onClick={exportRegistry} className="rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">{t(lang, 'board_export')}</button>
        </div>
        {members && members.length === 0 && <p className="text-sm text-slate-500">{t(lang, 'board_registry_none')}</p>}
        {members && members.length > 0 && (
          <div className="overflow-x-auto">
            <table className="w-full text-sm">
              <thead><tr className="text-start text-slate-500"><th className="p-2 text-start">{t(lang, 'board_name')}</th><th className="p-2 text-start">{t(lang, 'board_category')}</th><th className="p-2 text-start">{t(lang, 'board_status')}</th>{board.role === 'president' && <th className="p-2 text-start" />}</tr></thead>
              <tbody>
                {members.map((m) => (
                  <tr key={m.id} className="border-t border-slate-100">
                    <td className="p-2">{m.full_name}</td><td className="p-2">{m.category_label}</td><td className="p-2">{m.status_label}</td>
                    {board.role === 'president' && (
                      <td className="p-2">
                        <button type="button" className="text-sm font-semibold text-brand hover:underline" onClick={() => send(async () => { await bff(consulateSlug, `citizen/association/members/${m.id}`, { method: 'PATCH', lang, body: { status: m.status === 'active' ? 'inactive' : 'active' } }); await reloadMembers(); }, t(lang, 'board_member_updated'))}>
                          {m.status === 'active' ? t(lang, 'board_deactivate') : t(lang, 'board_reactivate')}
                        </button>
                      </td>
                    )}
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        )}
      </section>

      <section className="space-y-3">
        <h2 className="text-lg font-bold text-brand">{t(lang, 'board_journal')}</h2>
        <p className="text-xs text-slate-500">{t(lang, 'board_journal_hint')}</p>
        {journal && journal.length === 0 && <p className="text-sm text-slate-500">{t(lang, 'board_journal_none')}</p>}
        {journal && journal.length > 0 && (
          <div className="overflow-x-auto">
            <table className="w-full text-sm">
              <thead><tr className="text-start text-slate-500"><th className="p-2 text-start">{t(lang, 'board_journal_when')}</th><th className="p-2 text-start">{t(lang, 'board_journal_by')}</th><th className="p-2 text-start">{t(lang, 'board_journal_what')}</th></tr></thead>
              <tbody>
                {journal.map((j, i) => (
                  <tr key={i} className="border-t border-slate-100">
                    <td className="p-2 whitespace-nowrap">{new Date(j.at).toLocaleString(lang === 'ar' ? 'ar' : lang === 'en' ? 'en-GB' : 'fr-FR')}</td>
                    <td className="p-2">{j.by}</td>
                    <td className="p-2">{j.label}</td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        )}
      </section>
    </div>
  );
}
