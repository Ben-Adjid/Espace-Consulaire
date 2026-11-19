'use client';

import Link from 'next/link';
import { useRouter } from 'next/navigation';
import { useEffect, useState } from 'react';
import { ApiError } from '@/lib/api';
import { bff } from '@/lib/bff';
import type { Lang } from '@/lib/types';
import { t } from '@/lib/i18n';

interface ConversationSummary {
  id: number;
  subject: string;
  status: 'open' | 'closed';
  status_label: string;
  pre_request_id: number | null;
  unread: boolean;
  last_message_at: string | null;
  last_excerpt: string | null;
  last_by: 'citizen' | 'consulate' | null;
}

interface Thread {
  id: number;
  subject: string;
  status: 'open' | 'closed';
  status_label: string;
  can_reply: boolean;
  pre_request: { id: number; service_name: string | null } | null;
  messages: { id: number; body: string; by: 'citizen' | 'consulate'; at: string | null }[];
}

const MAX_BODY = 2000;

function formatDate(lang: Lang, iso: string | null) {
  if (!iso) return '';
  return new Date(iso).toLocaleString(lang === 'ar' ? 'ar' : lang === 'en' ? 'en-GB' : 'fr-FR', { dateStyle: 'medium', timeStyle: 'short' });
}

const errorText = (lang: Lang, err: unknown) => (err instanceof ApiError ? err.message : t(lang, 'error'));

/** Liste des conversations et formulaire de nouveau message. */
export function ConversationList({ consulateSlug, lang, base, preRequestId }: { consulateSlug: string; lang: Lang; base: string; preRequestId?: number }) {
  const router = useRouter();
  const [items, setItems] = useState<ConversationSummary[] | null>(null);
  const [subject, setSubject] = useState('');
  const [body, setBody] = useState('');
  const [error, setError] = useState<string | null>(null);
  const [busy, setBusy] = useState(false);
  const [open, setOpen] = useState(Boolean(preRequestId));

  useEffect(() => {
    bff<{ data: ConversationSummary[] }>(consulateSlug, 'citizen/messages', { lang })
      .then((r) => setItems(r.data))
      .catch((err) => { setItems([]); setError(err instanceof ApiError && err.status === 401 ? t(lang, 'account_need_account') : errorText(lang, err)); });
  }, [consulateSlug, lang]);

  async function send(e: React.FormEvent) {
    e.preventDefault();
    setError(null);
    setBusy(true);
    try {
      const r = await bff<{ data: Thread }>(consulateSlug, 'citizen/messages', {
        method: 'POST', lang, body: { subject, body, pre_request_id: preRequestId ?? null },
      });
      router.push(`${base}/compte/messages/${r.data.id}`);
    } catch (err) {
      setError(errorText(lang, err));
      setBusy(false);
    }
  }

  return (
    <div className="space-y-6">
      {error && <p role="alert" className="rounded-lg bg-red-50 p-3 text-sm text-red-700">{error}</p>}

      <section className="card space-y-3 p-5">
        <div className="flex flex-wrap items-center justify-between gap-3">
          <div>
            <h2 className="font-bold text-brand">{t(lang, 'msg_new_title')}</h2>
            <p className="text-sm text-slate-600">{t(lang, 'msg_new_intro')}</p>
          </div>
          {!open && <button type="button" onClick={() => setOpen(true)} className="btn-primary px-5 py-2.5">{t(lang, 'msg_new_button')}</button>}
        </div>
        {open && (
          <form onSubmit={send} className="space-y-3">
            {preRequestId && <p className="rounded-lg bg-slate-50 p-2 text-sm text-slate-600">{t(lang, 'msg_about_request')} #{preRequestId}</p>}
            <div>
              <label htmlFor="msg-subject" className="label">{t(lang, 'msg_subject')}</label>
              <input id="msg-subject" className="input" required maxLength={150} value={subject} onChange={(e) => setSubject(e.target.value)} />
            </div>
            <div>
              <label htmlFor="msg-body" className="label">{t(lang, 'msg_body')}</label>
              <textarea id="msg-body" className="input min-h-32" required maxLength={MAX_BODY} value={body} onChange={(e) => setBody(e.target.value)} />
              <p className="mt-1 text-xs text-slate-500">{body.length} / {MAX_BODY} · {t(lang, 'msg_no_sensitive')}</p>
            </div>
            <div className="flex justify-end gap-3">
              <button type="button" onClick={() => setOpen(false)} className="rounded-lg px-4 py-2 text-sm font-semibold text-slate-600 hover:bg-slate-100">{t(lang, 'dialog_cancel')}</button>
              <button type="submit" disabled={busy || !subject.trim() || !body.trim()} className="btn-primary px-5 py-2.5">{t(lang, 'msg_send')}</button>
            </div>
          </form>
        )}
      </section>

      <section className="space-y-3">
        <h2 className="text-lg font-bold text-brand">{t(lang, 'msg_mine')}</h2>
        {items && items.length === 0 && <p className="text-slate-600">{t(lang, 'msg_none')}</p>}
        {items?.map((c) => (
          <Link key={c.id} href={`${base}/compte/messages/${c.id}`} className={`card flex items-start justify-between gap-3 p-4 hover:border-slate-400 ${c.unread ? 'border-amber-300 bg-amber-50' : ''}`}>
            <div className="min-w-0">
              <p className={`truncate text-slate-900 ${c.unread ? 'font-bold' : 'font-semibold'}`}>
                {c.unread && <span className="me-2 inline-block rounded-full bg-amber-500 px-2 py-0.5 text-xs font-bold text-white">{t(lang, 'msg_new_badge')}</span>}
                {c.subject}
              </p>
              {c.last_excerpt && (
                <p className="mt-1 truncate text-sm text-slate-600">
                  {t(lang, c.last_by === 'consulate' ? 'pre_history_consulate' : 'pre_history_citizen')} : {c.last_excerpt}
                </p>
              )}
              <p className="mt-1 text-xs text-slate-500">{formatDate(lang, c.last_message_at)}</p>
            </div>
            <span className={`shrink-0 rounded-full px-3 py-1 text-xs font-bold ${c.status === 'open' ? 'bg-green-100 text-green-800' : 'bg-slate-100 text-slate-600'}`}>{c.status_label}</span>
          </Link>
        ))}
      </section>
    </div>
  );
}

/** Fil d'une conversation et réponse. */
export function ConversationThread({ consulateSlug, lang, base, id }: { consulateSlug: string; lang: Lang; base: string; id: number }) {
  const [thread, setThread] = useState<Thread | null>(null);
  const [body, setBody] = useState('');
  const [error, setError] = useState<string | null>(null);
  const [busy, setBusy] = useState(false);

  useEffect(() => {
    bff<{ data: Thread }>(consulateSlug, `citizen/messages/${id}`, { lang })
      .then((r) => setThread(r.data))
      .catch((err) => setError(errorText(lang, err)));
  }, [consulateSlug, lang, id]);

  async function reply(e: React.FormEvent) {
    e.preventDefault();
    setError(null);
    setBusy(true);
    try {
      const r = await bff<{ data: Thread }>(consulateSlug, `citizen/messages/${id}/reply`, { method: 'POST', lang, body: { body } });
      setThread(r.data);
      setBody('');
    } catch (err) {
      setError(errorText(lang, err));
    } finally {
      setBusy(false);
    }
  }

  if (!thread) {
    return error ? <p role="alert" className="rounded-lg bg-red-50 p-3 text-sm text-red-700">{error}</p> : <p className="text-slate-600">{t(lang, 'loading')}</p>;
  }

  return (
    <div className="space-y-6">
      <div className="flex flex-wrap items-start justify-between gap-3">
        <div>
          <h1 className="text-2xl font-bold text-brand">{thread.subject}</h1>
          {thread.pre_request && (
            <Link href={`${base}/compte/demandes/${thread.pre_request.id}`} className="text-sm font-semibold text-brand underline">
              {t(lang, 'msg_about_request')} #{thread.pre_request.id}{thread.pre_request.service_name ? ` — ${thread.pre_request.service_name}` : ''}
            </Link>
          )}
        </div>
        <span className={`rounded-full px-3 py-1 text-xs font-bold ${thread.status === 'open' ? 'bg-green-100 text-green-800' : 'bg-slate-100 text-slate-600'}`}>{thread.status_label}</span>
      </div>

      <ol className="space-y-3">
        {thread.messages.map((m) => {
          const mine = m.by === 'citizen';
          return (
            <li key={m.id} className={`flex ${mine ? 'justify-end' : 'justify-start'}`}>
              <div className={`max-w-[85%] rounded-2xl p-4 text-sm shadow-sm ${mine ? 'bg-brand text-white' : 'border border-slate-200 bg-white text-slate-900'}`}>
                <p className={`text-xs font-semibold ${mine ? 'text-white/80' : 'text-slate-500'}`}>
                  {t(lang, mine ? 'pre_history_citizen' : 'pre_history_consulate')} · {formatDate(lang, m.at)}
                </p>
                <p className="mt-1 whitespace-pre-line">{m.body}</p>
              </div>
            </li>
          );
        })}
      </ol>

      {error && <p role="alert" className="rounded-lg bg-red-50 p-3 text-sm text-red-700">{error}</p>}

      {thread.can_reply ? (
        <form onSubmit={reply} className="card space-y-3 p-5">
          <label htmlFor="msg-reply" className="label">{t(lang, 'msg_reply')}</label>
          <textarea id="msg-reply" className="input min-h-28" required maxLength={MAX_BODY} value={body} onChange={(e) => setBody(e.target.value)} />
          <div className="flex items-center justify-between gap-3">
            <p className="text-xs text-slate-500">{body.length} / {MAX_BODY}</p>
            <button type="submit" disabled={busy || !body.trim()} className="btn-primary px-5 py-2.5">{t(lang, 'msg_send')}</button>
          </div>
        </form>
      ) : (
        <p className="rounded-xl bg-slate-50 p-4 text-sm text-slate-600">{t(lang, 'msg_closed')}</p>
      )}

      <Link href={`${base}/compte/messages`} className="inline-block text-sm font-semibold text-slate-600 hover:underline">← {t(lang, 'msg_back')}</Link>
    </div>
  );
}
