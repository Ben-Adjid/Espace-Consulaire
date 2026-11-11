import Link from 'next/link';

export default function NotFound() {
  return (
    <div className="mx-auto max-w-md px-4 py-20 text-center">
      <p className="text-6xl font-extrabold text-slate-300">404</p>
      <h1 className="mt-4 text-2xl font-bold text-slate-900">Page introuvable · الصفحة غير موجودة</h1>
      <p className="mt-2 text-slate-600">Cette page n’existe pas ou n’est plus publiée. · هذه الصفحة غير موجودة أو لم تعد منشورة.</p>
      <Link href="/" className="mt-6 inline-flex rounded-lg bg-slate-900 px-5 py-2.5 text-sm font-semibold text-white">Accueil · الرئيسية</Link>
    </div>
  );
}
