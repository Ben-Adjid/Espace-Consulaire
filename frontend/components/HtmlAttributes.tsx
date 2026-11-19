'use client';

import { useEffect } from 'react';

/** Met à jour `lang` et `dir` de la balise <html> selon la langue active (FR / EN / AR en RTL). */
export default function HtmlAttributes({ lang, dir }: { lang: string; dir: 'ltr' | 'rtl' }) {
  useEffect(() => {
    document.documentElement.lang = lang;
    document.documentElement.dir = dir;
    // En quittant la page (navigation côté client), on revient au français de gauche à droite :
    // une page sans HtmlAttributes (accueil de la plateforme) ne doit pas hériter du sens arabe.
    return () => {
      document.documentElement.lang = 'fr';
      document.documentElement.dir = 'ltr';
    };
  }, [lang, dir]);

  return null;
}
