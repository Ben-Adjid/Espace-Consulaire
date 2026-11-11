import type { Consulate, Lang } from '@/lib/types';
import { t } from '@/lib/i18n';

/** Carte OpenStreetMap intégrée (iframe officiel, sans clé d'API). */
export default function OsmMap({ consulate, lang }: { consulate: Consulate; lang: Lang }) {
  if (consulate.latitude == null || consulate.longitude == null) return null;

  const lat = consulate.latitude;
  const lon = consulate.longitude;
  const d = 0.008;
  const bbox = `${lon - d},${lat - d * 0.7},${lon + d},${lat + d * 0.7}`;
  const embed = `https://www.openstreetmap.org/export/embed.html?bbox=${bbox}&layer=mapnik&marker=${lat},${lon}`;

  return (
    <div className="card overflow-hidden">
      <iframe title={consulate.name} src={embed} className="h-72 w-full border-0" loading="lazy" referrerPolicy="no-referrer" />
      <p className="p-3 text-xs text-slate-500">
        <a className="underline" target="_blank" rel="noopener noreferrer" href={`https://www.openstreetmap.org/?mlat=${lat}&mlon=${lon}#map=16/${lat}/${lon}`}>
          {t(lang, 'open_map')}
        </a>{' '}
        — {t(lang, 'map_note')}
      </p>
    </div>
  );
}
