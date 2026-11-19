'use client';

import { useCallback, useEffect, useRef, useState } from 'react';
import { t } from '@/lib/i18n';
import type { Lang } from '@/lib/types';

interface Request {
  title: string;
  /** Élément concerné, affiché en gras : la personne sait exactement ce qu'elle supprime. */
  subject?: string;
  message?: string;
  confirmLabel: string;
  danger?: boolean;
  resolve: (ok: boolean) => void;
}

/**
 * Confirmation accessible (élément <dialog> natif : focus piégé, Échap pour fermer).
 * const [dialog, confirm] = useConfirm(lang); … if (await confirm({...})) …
 */
export function useConfirm(lang: Lang) {
  const [req, setReq] = useState<Request | null>(null);
  const ref = useRef<HTMLDialogElement>(null);

  useEffect(() => {
    const d = ref.current;
    if (req && d && !d.open) d.showModal();
  }, [req]);

  const close = useCallback((ok: boolean) => {
    ref.current?.close();
    setReq((r) => {
      r?.resolve(ok);
      return null;
    });
  }, []);

  const confirm = useCallback(
    (opts: Omit<Request, 'resolve'>) => new Promise<boolean>((resolve) => setReq({ ...opts, resolve })),
    [],
  );

  const dialog = (
    <dialog
      ref={ref}
      aria-labelledby="confirm-title"
      onCancel={(e) => { e.preventDefault(); close(false); }}
      className="w-[min(28rem,calc(100vw-2rem))] rounded-2xl p-0 shadow-2xl backdrop:bg-slate-900/50"
    >
      {req && (
        <div className="space-y-4 p-6">
          <h2 id="confirm-title" className="text-lg font-bold text-slate-900">{req.title}</h2>
          {req.subject && <p className="rounded-lg bg-slate-50 p-3 text-sm font-semibold text-slate-900">{req.subject}</p>}
          {req.message && <p className="text-sm text-slate-600">{req.message}</p>}
          <div className="flex flex-col-reverse gap-3 pt-2 sm:flex-row sm:justify-end">
            <button type="button" autoFocus onClick={() => close(false)} className="btn border border-slate-300 bg-white px-5 py-2.5 text-slate-700 hover:bg-slate-50">
              {t(lang, 'dialog_cancel')}
            </button>
            <button
              type="button"
              onClick={() => close(true)}
              className={`btn px-5 py-2.5 text-white ${req.danger ? 'bg-red-700 hover:bg-red-800' : 'bg-brand hover:opacity-90'}`}
            >
              {req.confirmLabel}
            </button>
          </div>
        </div>
      )}
    </dialog>
  );

  return [dialog, confirm] as const;
}
