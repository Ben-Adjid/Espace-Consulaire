import Link from 'next/link';
import type { Consulate, Lang, PageContent } from '@/lib/types';
import { t } from '@/lib/i18n';
import Logo from './Logo';

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
          </div>
          <div className="text-sm">
            <h2 className="mb-2 font-semibold text-white">{t(lang, 'legal')}</h2>
            <ul className="space-y-1">
              {pages.map((p) => (
                <li key={p.slug}><Link className="hover:underline" href={`${base}/pages/${p.slug}`}>{p.title}</Link></li>
              ))}
              <li><Link className="hover:underline" href="/">{t(lang, 'other_consulates')}</Link></li>
            </ul>
          </div>
        </div>
        <div className="border-t border-slate-700 px-4 py-4 text-center text-xs text-slate-400">{t(lang, 'demo_footer')}</div>
      </footer>
      {consulate.whatsapp && (
        <a
          href={`https://wa.me/${consulate.whatsapp}`}
          target="_blank"
          rel="noopener noreferrer"
          className="fixed bottom-4 end-4 z-40 rounded-full bg-green-600 px-4 py-3 text-sm font-semibold text-white shadow-lg hover:bg-green-700"
          aria-label={t(lang, 'whatsapp_cta')}
        >
          {t(lang, 'whatsapp')}
        </a>
      )}
    </>
  );
}
