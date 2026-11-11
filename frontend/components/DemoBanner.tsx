import { t } from '@/lib/i18n';
import type { Lang } from '@/lib/types';

/** Bandeau visible sur toutes les pages : la démo n'est pas un site officiel. */
export default function DemoBanner({ lang }: { lang: Lang }) {
  return (
    <div role="note" className="sticky top-0 z-50 bg-amber-300 px-3 py-1.5 text-center text-xs font-semibold text-slate-900 sm:text-sm">
      ⚠ {t(lang, 'demo_banner')} — {t(lang, 'demo_note')}
    </div>
  );
}
