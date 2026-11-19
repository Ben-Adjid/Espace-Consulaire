import PlatformHome from '@/components/PlatformHome';
import { HOME } from '@/lib/platformText';

export const instant = false;

export const metadata = { title: HOME.en.meta };

/** Accueil de la plateforme (en). */
export default function Page() {
  return <PlatformHome lang="en" />;
}
