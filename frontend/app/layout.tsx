import type { Metadata } from 'next';
import './globals.css';

export const metadata: Metadata = {
  title: { default: 'Plateforme consulaire', template: '%s — Plateforme consulaire' },
  description: 'Plateforme multi-consulats pour digitaliser les services consulaires africains.',
  robots: { index: true, follow: true },
};

export default function RootLayout({ children }: { children: React.ReactNode }) {
  return (
    <html lang="fr">
      <body className="flex min-h-screen flex-col">{children}</body>
    </html>
  );
}
