'use client';

import Link from 'next/link';
import { useEffect, useRef, useState } from 'react';
import { api } from '@/lib/api';
import type { Consulate, Lang, SearchResult, Service } from '@/lib/types';
import { t } from '@/lib/i18n';

interface Props {
  consulate: Consulate;
  consulateSlug: string;
  lang: Lang;
  base: string;
}

/** Action proposée sous une réponse : bouton qui relance une intention. */
type Action =
  | { kind: 'docs'; slug?: string }
  | { kind: 'fees'; slug?: string }
  | { kind: 'hours' }
  | { kind: 'contact' }
  | { kind: 'book' }
  | { kind: 'manage' };

interface Message {
  id: number;
  from: 'bot' | 'user';
  text: string;
  actions?: { label: string; action: Action }[];
  links?: { label: string; href: string }[];
}

/**
 * Assistant à règles (sans IA générative) : il répond à partir des données publiques du consulat.
 * Il ne traite aucun dossier personnel et renvoie vers le consulat quand il ne sait pas répondre.
 */
export default function ChatBot({ consulate, consulateSlug, lang, base }: Props) {
  const [open, setOpen] = useState(false);
  const [services, setServices] = useState<Service[] | null>(null);
  const [messages, setMessages] = useState<Message[]>([]);
  const [input, setInput] = useState('');
  const [busy, setBusy] = useState(false);
  const idRef = useRef(0);
  const listRef = useRef<HTMLDivElement>(null);

  // Services chargés une fois, à l'ouverture.
  useEffect(() => {
    if (!open || services !== null) return;
    api<{ data: Service[] }>(`/${consulateSlug}/services`, { lang, revalidate: 300 })
      .then((r) => setServices(r.data))
      .catch(() => setServices([]));
  }, [open, services, consulateSlug, lang]);

  // Message d'accueil à la première ouverture.
  useEffect(() => {
    if (open && messages.length === 0) {
      push('bot', t(lang, 'chat_intro'), {
        actions: [
          { label: t(lang, 'chat_quick_docs'), action: { kind: 'docs' } },
          { label: t(lang, 'chat_quick_fees'), action: { kind: 'fees' } },
          { label: t(lang, 'chat_quick_book'), action: { kind: 'book' } },
          { label: t(lang, 'chat_quick_manage'), action: { kind: 'manage' } },
          { label: t(lang, 'chat_quick_hours'), action: { kind: 'hours' } },
          { label: t(lang, 'chat_quick_contact'), action: { kind: 'contact' } },
        ],
      });
    }
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [open]);

  useEffect(() => {
    listRef.current?.scrollTo({ top: listRef.current.scrollHeight, behavior: 'smooth' });
  }, [messages, busy]);

  function push(from: 'bot' | 'user', text: string, extra: Partial<Message> = {}) {
    idRef.current += 1;
    setMessages((m) => [...m, { id: idRef.current, from, text, ...extra }]);
  }

  /** Choix d'un service pour les pièces ou les tarifs. */
  function askService(kind: 'docs' | 'fees') {
    const list = services ?? [];
    push('bot', t(lang, 'chat_pick_service'), {
      actions: list.map((s) => ({ label: s.name, action: { kind, slug: s.slug } })),
    });
  }

  function answer(action: Action) {
    switch (action.kind) {
      case 'docs':
        if (!action.slug) return askService('docs');
        return showDocs(action.slug);
      case 'fees':
        if (!action.slug) return askService('fees');
        return showFees(action.slug);
      case 'hours':
        return push('bot', [consulate.opening_hours, consulate.address].filter(Boolean).join('\n\n') || t(lang, 'chat_no_info'), {
          links: [{ label: t(lang, 'chat_quick_contact'), href: `${base}/contact` }],
        });
      case 'contact':
        return push('bot', [consulate.phone, consulate.email].filter(Boolean).join('\n') || t(lang, 'chat_no_info'), {
          links: [{ label: t(lang, 'contact'), href: `${base}/contact` }],
        });
      case 'book':
        return push('bot', t(lang, 'chat_book'), { links: [{ label: t(lang, 'book'), href: `${base}/appointment` }] });
      case 'manage':
        return push('bot', t(lang, 'chat_manage'));
    }
  }

  function showDocs(slug: string) {
    const s = services?.find((x) => x.slug === slug);
    if (!s) return push('bot', t(lang, 'chat_fallback'));
    const body = s.documents.length
      ? s.documents.map((d) => `• ${d}`).join('\n')
      : t(lang, 'chat_no_docs');
    push('bot', `${t(lang, 'chat_docs_title').replace('{service}', s.name)}\n\n${body}`, {
      links: [{ label: t(lang, 'book'), href: `${base}/appointment?service=${s.slug}` }],
    });
  }

  function showFees(slug: string) {
    const s = services?.find((x) => x.slug === slug);
    if (!s) return push('bot', t(lang, 'chat_fallback'));
    const fee = s.fee ? t(lang, 'chat_fee').replace('{fee}', s.fee) : t(lang, 'chat_no_fee');
    const time = s.processing_time ? t(lang, 'chat_processing').replace('{time}', s.processing_time) : '';
    push('bot', [fee, time].filter(Boolean).join('\n'), {
      links: [{ label: t(lang, 'book'), href: `${base}/appointment?service=${s.slug}` }],
    });
  }

  /** Reconnaît une intention simple dans une phrase libre (français et anglais). */
  function detect(text: string): Action | null {
    const q = text.normalize('NFD').replace(/[̀-ͯ]/g, '').toLowerCase();
    if (/piece|document|justificatif|apporter|documents?|required|papers|bring/.test(q)) return { kind: 'docs' };
    if (/tarif|prix|cout|combien|frais|fee|price|cost|how much/.test(q)) return { kind: 'fees' };
    if (/horaire|ouvert|adresse|ou se trouve|where|address|hours|open/.test(q)) return { kind: 'hours' };
    if (/annul|modifi|deplac|changer|cancel|reschedule|change my/.test(q)) return { kind: 'manage' };
    if (/rendez|rdv|reserv|book|appointment/.test(q)) return { kind: 'book' };
    if (/contact|telephone|appeler|mail|whatsapp|call|phone/.test(q)) return { kind: 'contact' };
    return null;
  }

  /** Service dont le nom (ou l'identifiant) apparaît dans la phrase. */
  function serviceIn(text: string): Service | undefined {
    const norm = (x: string) => x.normalize('NFD').replace(/[̀-ͯ]/g, '').toLowerCase();
    const q = norm(text);
    return (services ?? []).find((s) => q.includes(norm(s.name)) || q.includes(norm(s.slug.replace(/-/g, ' '))));
  }

  async function send(text: string) {
    const clean = text.trim();
    if (!clean) return;
    setInput('');
    push('user', clean);

    const intent = detect(clean);
    // Un service nommé dans la question permet de répondre directement (pièces ou tarif de ce service).
    const named = serviceIn(clean);
    if (named && intent?.kind === 'docs') return showDocs(named.slug);
    if (named && intent?.kind === 'fees') return showFees(named.slug);
    if (intent) return answer(intent);
    if (named) {
      return push('bot', t(lang, 'chat_about_service').replace('{service}', named.name), {
        actions: [
          { label: t(lang, 'chat_quick_docs'), action: { kind: 'docs', slug: named.slug } },
          { label: t(lang, 'chat_quick_fees'), action: { kind: 'fees', slug: named.slug } },
        ],
        links: [{ label: t(lang, 'book'), href: `${base}/appointment?service=${named.slug}` }],
      });
    }

    // Recherche dans le contenu public du consulat.
    setBusy(true);
    try {
      const r = await api<{ data: SearchResult[] }>(`/${consulateSlug}/search`, { lang, query: { q: clean }, noStore: true });
      if (r.data.length === 0) return push('bot', t(lang, 'chat_fallback'));
      push('bot', t(lang, 'chat_found'), {
        links: r.data.slice(0, 5).map((x) => ({ label: x.title, href: hrefOf(x) })),
      });
    } catch {
      push('bot', t(lang, 'chat_fallback'));
    } finally {
      setBusy(false);
    }
  }

  function hrefOf(x: SearchResult): string {
    if (x.type === 'service' && x.slug) return `${base}/services/${x.slug}`;
    if (x.type === 'news' && x.slug) return `${base}/news/${x.slug}`;
    return `${base}/faq`;
  }

  return (
    <>
      <button
        type="button"
        onClick={() => setOpen((o) => !o)}
        aria-expanded={open}
        aria-controls="chatbot-panel"
        aria-label={open ? t(lang, 'chat_close') : t(lang, 'chat_open')}
        // Sur téléphone : bouton rond compact (icône seule) pour ne pas recouvrir les formulaires.
        className="fixed bottom-20 end-4 z-50 flex h-12 w-12 items-center justify-center gap-2 rounded-full bg-brand text-sm font-semibold text-white shadow-lg transition hover:scale-[1.02] sm:bottom-24 sm:end-6 sm:h-auto sm:w-auto sm:px-4 sm:py-3"
      >
        <span aria-hidden="true">💬</span>
        <span className="hidden sm:inline">{open ? t(lang, 'chat_close') : t(lang, 'chat_open')}</span>
      </button>

      {open && (
        <section
          id="chatbot-panel"
          aria-label={t(lang, 'chat_title')}
          className="fixed bottom-40 end-4 z-50 flex max-h-[70vh] w-[min(360px,calc(100vw-2rem))] flex-col overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-2xl sm:end-6"
        >
          <header className="flex items-center justify-between bg-brand px-4 py-3 text-white">
            <div>
              <p className="text-sm font-bold">{t(lang, 'chat_title')}</p>
              <p className="text-xs text-white/80">{consulate.name}</p>
            </div>
            <button type="button" onClick={() => setOpen(false)} aria-label={t(lang, 'chat_close')} className="rounded-md px-2 py-1 text-lg hover:bg-white/10">
              ×
            </button>
          </header>

          <div ref={listRef} className="flex-1 space-y-3 overflow-y-auto bg-slate-50 p-4" role="log" aria-live="polite">
            {messages.map((m) => (
              <div key={m.id} className={`flex flex-col ${m.from === 'user' ? 'items-end' : 'items-start'}`}>
                <p className={`max-w-[85%] whitespace-pre-line rounded-2xl px-3 py-2 text-sm ${m.from === 'user' ? 'bg-brand text-white' : 'bg-white text-slate-800 shadow-sm'}`}>
                  {m.text}
                </p>
                {m.actions && m.actions.length > 0 && (
                  <div className="mt-2 flex flex-wrap gap-2">
                    {m.actions.map((a) => (
                      <button
                        key={a.label}
                        type="button"
                        onClick={() => {
                          push('user', a.label);
                          answer(a.action);
                        }}
                        className="rounded-full border border-brand/40 bg-white px-3 py-1 text-xs font-semibold text-brand hover:bg-brand hover:text-white"
                      >
                        {a.label}
                      </button>
                    ))}
                  </div>
                )}
                {m.links && m.links.length > 0 && (
                  <div className="mt-2 flex flex-wrap gap-2">
                    {m.links.map((l) => (
                      <Link key={l.href + l.label} href={l.href} className="rounded-full bg-slate-900 px-3 py-1 text-xs font-semibold text-white hover:bg-slate-700">
                        {l.label} →
                      </Link>
                    ))}
                  </div>
                )}
              </div>
            ))}
            {busy && <p className="text-xs text-slate-500">{t(lang, 'loading')}</p>}
          </div>

          <form
            className="flex gap-2 border-t border-slate-200 p-3"
            onSubmit={(e) => {
              e.preventDefault();
              send(input);
            }}
          >
            <label htmlFor="chat-input" className="sr-only">{t(lang, 'chat_placeholder')}</label>
            <input
              id="chat-input"
              value={input}
              onChange={(e) => setInput(e.target.value)}
              placeholder={t(lang, 'chat_placeholder')}
              maxLength={200}
              className="input flex-1 py-2 text-sm"
            />
            <button type="submit" disabled={!input.trim() || busy} className="btn-primary px-4 py-2 text-sm">
              {t(lang, 'chat_send')}
            </button>
          </form>
        </section>
      )}
    </>
  );
}
