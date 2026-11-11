'use client';

import { useEffect } from 'react';

/** Met à jour `lang` et `dir` de la balise <html> selon la langue active (FR / EN / AR en RTL). */
export default function HtmlAttributes({ lang, dir }: { lang: string; dir: 'ltr' | 'rtl' }) {
  useEffect(() => {
    document.documentElement.lang = lang;
    document.documentElement.dir = dir;
  }, [lang, dir]);

  return null;
}
