import type { NextConfig } from "next";

const nextConfig: NextConfig = {
  /* config options here */
  cacheComponents: true,
  // Ressources de développement (JS, rechargement) : sans ces hôtes, Next bloque 127.0.0.1 / localhost
  // et la page ne s'hydrate pas (le formulaire de connexion ne réagit plus).
  allowedDevOrigins: ['127.0.0.1', 'localhost'],
  partialPrefetching: true,
  // Ne pas annoncer la technologie du serveur (en-tête X-Powered-By).
  poweredByHeader: false,
  // En-têtes de sécurité du site : pas d'intégration dans un cadre tiers (clickjacking),
  // pas de devinette de type MIME, referrer limité, et fonctions du navigateur désactivées.
  async headers() {
    return [
      {
        source: '/:path*',
        headers: [
          { key: 'X-Frame-Options', value: 'SAMEORIGIN' },
          { key: 'X-Content-Type-Options', value: 'nosniff' },
          { key: 'Referrer-Policy', value: 'strict-origin-when-cross-origin' },
          { key: 'Permissions-Policy', value: 'camera=(), microphone=(), geolocation=(), payment=()' },
        ],
      },
    ];
  },
  turbopack: {
    rules: {
      "*.css": {
        loaders: ["@tailwindcss/turbopack"],
        as: "*.css",
      },
    },
  },
};

export default nextConfig;
