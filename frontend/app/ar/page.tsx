import PlatformHome from '@/components/PlatformHome';
import { HOME } from '@/lib/platformText';

export const instant = false;

export const metadata = { title: HOME.ar.meta };

/** Accueil de la plateforme (ar). */
export default function Page() {
  return <PlatformHome lang="ar" />;
}
