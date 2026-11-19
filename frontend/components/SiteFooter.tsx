import Link from 'next/link';
import type { Consulate, Lang, PageContent } from '@/lib/types';
import { t } from '@/lib/i18n';
import Logo from './Logo';
import CurrentYear from './CurrentYear';

export default function SiteFooter({ consulate, lang, base, pages }: { consulate: Consulate; lang: Lang; base: string; pages: PageContent[] }) {
  return (
    <>
      <footer className="mt-12 bg-slate-900 text-slate-300">
        <div className="mx-auto grid max-w-6xl gap-8 px-4 py-10 sm:grid-cols-2 lg:grid-cols-3">
          <div>
            <div className="mb-3 flex items-center gap-3 text-white">
              <Logo consulate={consulate} size={36} />
              <span className="font-bold">{consulate.name}</span>
            </div>
            <p className="whitespace-pre-line text-sm">{consulate.address}</p>
          </div>
          <div className="space-y-1 text-sm">
            <h2 className="mb-2 font-semibold text-white">{t(lang, 'contacts')}</h2>
            {consulate.phone && <p dir="ltr">{consulate.phone}</p>}
            {consulate.email && <p><a className="hover:underline" href={`mailto:${consulate.email}`}>{consulate.email}</a></p>}
            {consulate.whatsapp && (
              <p><a className="font-semibold text-green-400 hover:underline" target="_blank" rel="noopener noreferrer" href={`https://wa.me/${consulate.whatsapp}`}>{t(lang, 'whatsapp')}</a></p>
            )}
            {consulate.latitude != null && consulate.longitude != null && (
              <p>
                <a className="hover:underline" target="_blank" rel="noopener noreferrer"
                  href={`https://www.openstreetmap.org/directions?route=%3B${consulate.latitude}%2C${consulate.longitude}`}>
                  {t(lang, 'footer_directions')} →
                </a>
              </p>
            )}
            <p className="pt-2"><Link className="inline-block rounded-lg bg-brand px-3 py-1.5 text-xs font-semibold text-white hover:opacity-90" href={`${base}/appointment`}>{t(lang, 'book')}</Link></p>
          </div>
          <div className="text-sm">
            <h2 className="mb-2 font-semibold text-white">{t(lang, 'legal')}</h2>
            <ul className="space-y-1">
              {pages.map((p) => (
                <li key={p.slug}><Link className="hover:underline" href={`${base}/pages/${p.slug}`}>{p.title}</Link></li>
              ))}
              <li><Link className="hover:underline" href={`${base}/association`}>{t(lang, 'footer_association')}</Link></li>
              <li><Link className="hover:underline" href={`${base}/compte`}>{t(lang, 'account_nav')}</Link></li>
              <li><Link className="hover:underline" href="/">{t(lang, 'other_consulates')}</Link></li>
              <li><a className="hover:underline" href={process.env.NEXT_PUBLIC_ADMIN_URL ?? 'http://127.0.0.1:8000/admin'} target="_blank" rel="noopener noreferrer">{t(lang, 'admin_space')}</a></li>
            </ul>
          </div>
        </div>
        <div className="border-t border-slate-700 px-4 py-4 text-center text-xs text-slate-400">
          <p>© <CurrentYear /> {consulate.name} · {t(lang, 'footer_rights')}</p>
          <p className="mt-1">
            <Link href="/credits" className="hover:text-white hover:underline">Conception : Aboubakar Assidick Mahamat Adjid Oumar</Link>
          </p>
        </div>
      </footer>
      {consulate.whatsapp && (
        <a
          href={`https://wa.me/${consulate.whatsapp}`}
          target="_blank"
          rel="noopener noreferrer"
          className="fixed bottom-4 end-4 z-40 flex h-12 w-12 items-center justify-center rounded-full bg-green-600 text-sm font-semibold text-white shadow-lg hover:bg-green-700 sm:h-auto sm:w-auto sm:px-4 sm:py-3"
          aria-label={t(lang, 'whatsapp_cta')}
        >
          {/* Téléphone : icône seule pour ne pas recouvrir le contenu. */}
          <svg aria-hidden="true" viewBox="0 0 24 24" fill="none" className="h-6 w-6 sm:hidden"><path d="M4 20l1.3-3.9A8 8 0 1 1 8 19l-4 1Z" stroke="currentColor" strokeWidth="1.7" strokeLinejoin="round" /><path d="M9 9.5c0 3 2.5 5.5 5.5 5.5l1-1.5-2-1-1 .8a4 4 0 0 1-2-2l.8-1-1-2L9 9.5Z" fill="currentColor" /></svg>
          <span className="hidden sm:inline">{t(lang, 'whatsapp')}</span>
        </a>
      )}
    </>
  );
}
