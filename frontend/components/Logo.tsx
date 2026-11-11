import type { Consulate } from '@/lib/types';

/** Logo : image téléversée, sinon logo neutre généré (bandes aux couleurs du consulat + initiales). */
export default function Logo({ consulate, size = 44 }: { consulate: Consulate; size?: number }) {
  if (consulate.logo_url) {
    // eslint-disable-next-line @next/next/no-img-element
    return <img src={consulate.logo_url} alt="" width={size} height={size} className="shrink-0 rounded-lg bg-white object-contain" />;
  }

  const inner = Math.round(size * 0.62);
  return (
    <span
      aria-hidden="true"
      className="relative inline-flex shrink-0 items-center justify-center overflow-hidden rounded-lg"
      style={{
        width: size,
        height: size,
        background: `linear-gradient(90deg, ${consulate.theme.primary} 33.3%, ${consulate.theme.secondary} 33.3% 66.6%, ${consulate.theme.accent} 66.6%)`,
      }}
    >
      <span
        className="flex items-center justify-center rounded-full bg-white font-bold text-slate-800 shadow"
        style={{ width: inner, height: inner, fontSize: Math.round(size * 0.27) }}
      >
        {consulate.initials}
      </span>
    </span>
  );
}
