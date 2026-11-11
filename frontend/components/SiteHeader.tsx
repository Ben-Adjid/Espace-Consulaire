'use client';

import Link from 'next/link';
import { usePathname } from 'next/navigation';
import type { Consulate, Lang } from '@/lib/types';
import { LANG_NAMES, t } from '@/lib/i18n';
import Logo from './Logo';
import SearchBox from './SearchBox';

interface Props {
  consulate: Consulate;
  lang: Lang;
  base: string; // ex. /tchad-senegal/fr
}

export default function SiteHeader({ consulate, lang, base }: Omit<Props, 'currentPath'>) {
  const pathname = usePathname();
  const currentPath = pathname.replace(`/${consulate.slug}/${lang}`, '') || '/';
  const nav = [
    { href: '', key: 'home' },
    { href: '/services', key: 'services' },
    { href: '/news', key: 'news' },
    { href: '/faq', key: 'faq' },
    { href: '/contact', key: 'contact' },
  ];

  return (
    <header className="sticky top-0 z-40 border-b border-slate-200 bg-white/95 backdrop-blur">
      <div className="h-1.5" style={{ background: 'linear-gradient(90deg, var(--brand) 33.3%, var(--brand-2) 33.3% 66.6%, var(--brand-3) 66.6%)' }} />
      <div className="mx-auto flex max-w-6xl flex-wrap items-center justify-between gap-3 px-4 py-3">
        <Link href={base || '/'} className="flex items-center gap-3">
          <Logo consulate={consulate} />
          <span className="leading-tight">
            <span className="block text-sm font-bold sm:text-base text-brand">{consulate.name}</span>
            <span className="block text-xs text-slate-500">{consulate.host_country}</span>
          </span>
        </Link>

        <nav aria-label={t(lang, 'menu')} className="order-3 flex w-full items-center gap-1 overflow-x-auto md:order-none md:w-auto">
          {nav.map((item) => {
            const active = item.href === '' ? currentPath === '/' : currentPath.startsWith(item.href);
            return (
              <Link
                key={item.key}
                href={`${base}${item.href}`}
                aria-current={active ? 'page' : undefined}
                className={`whitespace-nowrap rounded-md px-3 py-2 text-sm font-medium ${active ? 'bg-slate-100 text-slate-900' : 'text-slate-600 hover:bg-slate-100'}`}
              >
                {t(lang, item.key)}
              </Link>
            );
          })}
        </nav>

        <div className="flex items-center gap-2">
          <SearchBox consulateSlug={consulate.slug} lang={lang} base={base} />
          <div className="flex overflow-hidden rounded-md border border-slate-300 text-xs font-semibold" role="group" aria-label={t(lang, 'language')}>
            {consulate.languages.map((code) => {
              const nextPath = currentPath === '/' ? '' : currentPath;
              return (
                <Link
                  key={code}
                  href={`/${consulate.slug}/${code}${nextPath}`}
                  hrefLang={code}
                  lang={code}
                  aria-label={LANG_NAMES[code]}
                  className={`px-2.5 py-1.5 ${code === lang ? 'text-white bg-brand' : 'bg-white text-slate-700 hover:bg-slate-100'}`}
                >
                  {code.toUpperCase()}
                </Link>
              );
            })}
          </div>
          <Link href="/" className="hidden whitespace-nowrap text-xs font-semibold text-slate-600 hover:underline lg:inline" title={t(lang, 'other_consulates')}>
            {t(lang, 'choose_consulate')}
          </Link>
          <a href={process.env.NEXT_PUBLIC_ADMIN_URL ?? 'http://127.0.0.1:8000/admin'} target="_blank" rel="noopener noreferrer" className="hidden whitespace-nowrap text-xs font-semibold text-slate-600 hover:underline xl:inline">
            {t(lang, 'admin_space')}
          </a>
          <Link href={`${base}/appointment`} className="btn-accent hidden px-4 py-2 text-sm sm:inline-flex">
            {t(lang, 'book')}
          </Link>
        </div>
      </div>
    </header>
  );
}
