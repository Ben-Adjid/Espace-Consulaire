'use client';

import Link from 'next/link';
import { useEffect, useState } from 'react';
import { bff } from '@/lib/bff';
import type { Lang, Service } from '@/lib/types';
import { t } from '@/lib/i18n';
import BookingWizard from './BookingWizard';

interface Account {
  full_name: string;
  email: string;
  phone: string;
  residence_country: string;
  status: 'pending' | 'active' | 'rejected';
}

interface Props {
  consulateSlug: string;
  lang: Lang;
  base: string;
  services: Service[];
  preselected?: string;
  countries: string[];
  nationalityLabel: string;
  onBehalfInit?: { name: string; relation: string } | null;
}

/** La réservation n'est accessible qu'avec un compte citoyen actif. */
export default function BookingGate(props: Props) {
  const { consulateSlug, lang, base } = props;
  const [state, setState] = useState<'loading' | 'guest' | 'pending' | 'rejected' | 'ready'>('loading');
  const [account, setAccount] = useState<Account | null>(null);

  useEffect(() => {
    // Le cookie de session (httpOnly) est envoyé automatiquement par le proxy : aucun jeton ici.
    bff<{ data: Account }>(consulateSlug, 'citizen/me', { lang })
      .then((r) => {
        setAccount(r.data);
        setState(r.data.status === 'active' ? 'ready' : r.data.status);
      })
      .catch(() => setState('guest'));
  }, [consulateSlug, lang]);

  if (state === 'loading') return <p className="text-slate-500">{t(lang, 'loading')}</p>;

  if (state === 'guest') {
    return (
      <div className="card space-y-4 p-6 text-center">
        <p className="text-lg font-bold text-slate-900">{t(lang, 'account_required_title')}</p>
        <p className="text-slate-600">{t(lang, 'account_need_account')}</p>
        <div className="flex flex-col justify-center gap-3 sm:flex-row">
          <Link href={`${base}/compte/connexion`} className="btn border border-slate-300 bg-white px-6 py-2.5 text-slate-700 hover:bg-slate-50">{t(lang, 'account_login_cta')}</Link>
          <Link href={`${base}/compte/inscription`} className="btn-primary px-6 py-2.5">{t(lang, 'account_register_cta')}</Link>
        </div>
      </div>
    );
  }

  if (state === 'pending' || state === 'rejected') {
    return (
      <div className="card space-y-3 p-6">
        <p className="font-bold text-slate-900">{state === 'pending' ? t(lang, 'account_pending_title') : t(lang, 'account_rejected_title')}</p>
        <p className="text-slate-600">{state === 'pending' ? t(lang, 'account_pending_text') : t(lang, 'account_rejected_text')}</p>
        <Link href={`${base}/compte`} className="font-semibold text-brand hover:underline">{t(lang, 'account_title')} →</Link>
      </div>
    );
  }

  return (
    <BookingWizard
      consulateSlug={consulateSlug}
      lang={lang}
      base={base}
      services={props.services}
      preselected={props.preselected}
      countries={props.countries}
      nationalityLabel={props.nationalityLabel}
      account={account}
      onBehalfInit={props.onBehalfInit}
    />
  );
}
