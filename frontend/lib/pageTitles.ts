import type { Lang } from '@/lib/types';

/** Titres des pages du consulat, par langue. La clé est le titre français d'origine. */
const TITLES: Record<string, Record<Lang, string>> = {
  'Rendez-vous enregistré': { fr: 'Rendez-vous enregistré', en: 'Appointment confirmed', ar: 'تم تسجيل الموعد' },
  'Prendre rendez-vous': { fr: 'Prendre rendez-vous', en: 'Book an appointment', ar: 'حجز موعد' },
  'Association des étudiants': { fr: 'Association des étudiants', en: 'Students’ association', ar: 'جمعية الطلبة' },
  'Mes démarches': { fr: 'Mes démarches', en: 'My applications', ar: 'ملفاتي' },
  'Ma pré-demande': { fr: 'Ma pré-demande', en: 'My pre-application', ar: 'طلبي المسبق' },
  'Élections de l’association': { fr: 'Élections de l’association', en: 'Association elections', ar: 'انتخابات الجمعية' },
  'Ma famille': { fr: 'Ma famille', en: 'My family', ar: 'عائلتي' },
  Messagerie: { fr: 'Messagerie', en: 'Messages', ar: 'المراسلات' },
  'Mon espace': { fr: 'Mon espace', en: 'My space', ar: 'فضائي' },
  'Mon profil': { fr: 'Mon profil', en: 'My profile', ar: 'ملفي الشخصي' },
  'Mes rendez-vous': { fr: 'Mes rendez-vous', en: 'My appointments', ar: 'مواعيدي' },
  'Mes signalements': { fr: 'Mes signalements', en: 'My reports', ar: 'بلاغاتي' },
  Connexion: { fr: 'Connexion', en: 'Sign in', ar: 'تسجيل الدخول' },
  'Créer mon compte': { fr: 'Créer mon compte', en: 'Create my account', ar: 'إنشاء حسابي' },
  'Mot de passe oublié': { fr: 'Mot de passe oublié', en: 'Forgot password', ar: 'نسيت كلمة المرور' },
  'Nouveau mot de passe': { fr: 'Nouveau mot de passe', en: 'New password', ar: 'كلمة مرور جديدة' },
  'Confirmer mon email': { fr: 'Confirmer mon email', en: 'Confirm my email', ar: 'تأكيد بريدي الإلكتروني' },
  Contact: { fr: 'Contact', en: 'Contact', ar: 'اتصل بنا' },
  'Questions fréquentes': { fr: 'Questions fréquentes', en: 'Frequently asked questions', ar: 'الأسئلة الشائعة' },
  Actualités: { fr: 'Actualités', en: 'News', ar: 'الأخبار' },
  'Services consulaires': { fr: 'Services consulaires', en: 'Consular services', ar: 'الخدمات القنصلية' },
  'Finances de l’association': { fr: 'Finances de l’association', en: 'Association finances', ar: 'مالية الجمعية' },
  'Ma chronologie': { fr: 'Ma chronologie', en: 'My timeline', ar: 'سجلّي الزمني' },
};

/** Titre d'une page dans la langue demandée (repli sur le français). */
export function pageTitle(lang: string, french: string): string {
  const entry = TITLES[french];
  if (!entry) return french;
  return entry[(lang as Lang)] ?? entry.fr;
}
