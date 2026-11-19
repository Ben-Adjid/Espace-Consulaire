import PlatformHome from '@/components/PlatformHome';
import { HOME } from '@/lib/platformText';

export const instant = false;

export const metadata = { title: HOME.fr.meta };

/** Accueil de la plateforme en français ; versions /en et /ar dans app/en et app/ar. */
export default function Page() {
  return <PlatformHome lang="fr" />;
}
