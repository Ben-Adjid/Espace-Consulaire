import type { Metadata } from 'next';
import './globals.css';

export const metadata: Metadata = {
  title: { default: 'Plateforme consulaire (démo)', template: '%s — Démo non officielle' },
  description: 'Démo non officielle : plateforme multi-consulats pour digitaliser des services consulaires africains.',
  robots: { index: true, follow: true },
};

export default function RootLayout({ children }: { children: React.ReactNode }) {
  return (
    <html lang="fr">
      <body className="flex min-h-screen flex-col">{children}</body>
    </html>
  );
}
