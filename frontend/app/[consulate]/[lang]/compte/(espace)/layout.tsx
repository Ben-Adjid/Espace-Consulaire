import CitizenSpaceNav from '@/components/CitizenSpaceNav';
import { baseOf, loadConsulate } from '@/lib/server';
export const instant = false;

/** Espace citoyen : menu latéral + zone de contenu. Les pages de connexion restent hors de cet espace. */
export default async function CitizenSpaceLayout({ children, params }: { children: React.ReactNode; params: Promise<{ consulate: string; lang: string }> }) {
  const { consulate: slug, lang: rawLang } = await params;
  const { lang } = await loadConsulate(slug, rawLang);
  const base = baseOf(slug, lang);

  return (
    <div className="mx-auto grid max-w-6xl gap-6 px-4 py-8 md:grid-cols-[240px_minmax(0,1fr)] md:gap-8 md:py-10">
      <CitizenSpaceNav consulateSlug={slug} lang={lang} base={base} />
      <div className="min-w-0 space-y-6">{children}</div>
    </div>
  );
}
