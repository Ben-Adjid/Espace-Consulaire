'use client';

import { useEffect, useState } from 'react';

/** Année courante, lue après l'affichage (les pages pré-rendues ne peuvent pas lire l'heure). */
export default function CurrentYear() {
  const [year, setYear] = useState<number | null>(null);
  useEffect(() => setYear(new Date().getFullYear()), []);
  return <span>{year ?? ''}</span>;
}
