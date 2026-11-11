// Textes de l'interface (menus, boutons, libellés). Les contenus du consulat viennent de l'API, déjà traduits.
import type { Lang } from './types';

export const LANGS: Lang[] = ['fr', 'en', 'ar'];

export const LANG_NAMES: Record<Lang, string> = { fr: 'Français', en: 'English', ar: 'العربية' };

export function isLang(value: string): value is Lang {
  return (LANGS as string[]).includes(value);
}

export function dirOf(lang: Lang): 'ltr' | 'rtl' {
  return lang === 'ar' ? 'rtl' : 'ltr';
}

type Dict = Record<string, string>;

const fr: Dict = {
  home: 'Accueil', services: 'Services', news: 'Actualités', faq: 'FAQ', contact: 'Contact',
  book: 'Prendre rendez-vous', book_short: 'Rendez-vous', see_all: 'Voir tout', read_more: 'Lire la suite',
  demo_banner: 'Démo / Maquette non officielle',
  demo_note: 'Données fictives : adresses, téléphones, frais et délais sont fictifs. Aucun logo officiel n’est utilisé.',
  demo_footer: 'Démo / Maquette non officielle — ce site n’est ni édité ni validé par un consulat.',
  quick_access: 'Accès rapide aux services', consul_word: 'Le mot du consul', mission: 'Notre mission',
  find_us: 'Nous trouver', address: 'Adresse', hours: 'Horaires d’ouverture', contacts: 'Contacts',
  latest_news: 'Dernières actualités', documents_required: 'Documents requis',
  documents_hint: 'Cochez au fur et à mesure que vous les rassemblez.', fee: 'Frais', delay: 'Délai de traitement',
  search: 'Rechercher…', search_title: 'Rechercher dans le site', no_result: 'Aucun résultat.',
  faq_search: 'Rechercher une question…', contact_us: 'Contactez-nous', send: 'Envoyer le message',
  sent_ok: 'Merci, votre message a bien été envoyé. Le consulat vous répondra dans les meilleurs délais.',
  name: 'Nom complet', email: 'Email', phone: 'Téléphone', subject: 'Sujet', message: 'Message',
  consent: 'J’accepte que mes données soient utilisées pour traiter ma demande (démo, conservation limitée).',
  required: 'obligatoire', optional: 'facultatif',
  step_service: 'Choisissez le service', step_date: 'Choisissez la date', step_slot: 'Choisissez le créneau',
  step_details: 'Vos coordonnées', no_dates: 'Aucune disponibilité pour ce service pour le moment.',
  no_slots: 'Ce jour est complet. Choisissez une autre date.', seats: 'place(s) restante(s)', seat_one: 'place restante',
  choose: '— Sélectionnez —', confirm: 'Confirmer le rendez-vous', saving: 'Enregistrement…',
  reference: 'Référence', service: 'Service', date: 'Date', time: 'Heure', status: 'Statut',
  cancel_appt: 'Annuler le rendez-vous', cancel_done: 'Rendez-vous annulé.', cancel_confirm: 'Confirmer l’annulation ?',
  cancel_locked: 'Ce rendez-vous ne peut plus être annulé en ligne. Contactez le consulat.',
  download_ics: 'Ajouter au calendrier (.ics)', token_missing: 'Lien incomplet : ouvrez le lien reçu par email.',
  confirmed_title: 'Votre demande est enregistrée', confirmed_hint: 'Conservez cette référence et apportez les documents requis.',
  keep_link: 'Ce lien est personnel : ne le transférez à personne.', back_home: 'Retour à l’accueil',
  not_found: 'Page introuvable', not_found_hint: 'Cette page n’existe pas ou n’est plus publiée.',
  other_consulates: 'Autres consulats de la démo', choose_consulate: 'Choisissez un consulat',
  choose_consulate_hint: 'Plateforme de démonstration : un même logiciel, plusieurs consulats.',
  open_map: 'Ouvrir dans OpenStreetMap', map_note: 'Position fictive à titre de démonstration.',
  legal: 'Informations', error: 'Une erreur est survenue. Réessayez.', loading: 'Chargement…',
  language: 'Langue', menu: 'Menu', skip: 'Aller au contenu', whatsapp: 'WhatsApp', whatsapp_cta: 'Écrire sur WhatsApp',
  tooltip_seats: 'Places disponibles', previous: 'Précédent', next: 'Suivant', page: 'Page',
};

const en: Dict = {
  home: 'Home', services: 'Services', news: 'News', faq: 'FAQ', contact: 'Contact',
  book: 'Book an appointment', book_short: 'Appointment', see_all: 'See all', read_more: 'Read more',
  demo_banner: 'Demo / Unofficial mockup',
  demo_note: 'Fictitious data: addresses, phone numbers, fees and delays are fictitious. No official logo is used.',
  demo_footer: 'Demo / Unofficial mockup — this site is not published or validated by any consulate.',
  quick_access: 'Quick access to services', consul_word: 'A word from the consul', mission: 'Our mission',
  find_us: 'Find us', address: 'Address', hours: 'Opening hours', contacts: 'Contacts',
  latest_news: 'Latest news', documents_required: 'Required documents',
  documents_hint: 'Tick them off as you gather them.', fee: 'Fee', delay: 'Processing time',
  search: 'Search…', search_title: 'Search the site', no_result: 'No results.',
  faq_search: 'Search a question…', contact_us: 'Contact us', send: 'Send message',
  sent_ok: 'Thank you, your message has been sent. The consulate will reply as soon as possible.',
  name: 'Full name', email: 'Email', phone: 'Phone', subject: 'Subject', message: 'Message',
  consent: 'I agree that my data is used to process my request (demo, limited retention).',
  required: 'required', optional: 'optional',
  step_service: 'Choose the service', step_date: 'Choose the date', step_slot: 'Choose the time slot',
  step_details: 'Your details', no_dates: 'No availability for this service at the moment.',
  no_slots: 'This day is fully booked. Choose another date.', seats: 'seats left', seat_one: 'seat left',
  choose: '— Select —', confirm: 'Confirm the appointment', saving: 'Saving…',
  reference: 'Reference', service: 'Service', date: 'Date', time: 'Time', status: 'Status',
  cancel_appt: 'Cancel the appointment', cancel_done: 'Appointment cancelled.', cancel_confirm: 'Confirm cancellation?',
  cancel_locked: 'This appointment can no longer be cancelled online. Please contact the consulate.',
  download_ics: 'Add to calendar (.ics)', token_missing: 'Incomplete link: open the link you received by email.',
  confirmed_title: 'Your request has been recorded', confirmed_hint: 'Keep this reference and bring the required documents.',
  keep_link: 'This link is personal: do not share it.', back_home: 'Back to home',
  not_found: 'Page not found', not_found_hint: 'This page does not exist or is no longer published.',
  other_consulates: 'Other consulates in the demo', choose_consulate: 'Choose a consulate',
  choose_consulate_hint: 'Demo platform: one software, several consulates.',
  open_map: 'Open in OpenStreetMap', map_note: 'Fictitious location for demonstration.',
  legal: 'Information', error: 'Something went wrong. Please try again.', loading: 'Loading…',
  language: 'Language', menu: 'Menu', skip: 'Skip to content', whatsapp: 'WhatsApp', whatsapp_cta: 'Write on WhatsApp',
  tooltip_seats: 'Available seats', previous: 'Previous', next: 'Next', page: 'Page',
};

const ar: Dict = {
  home: 'الرئيسية', services: 'الخدمات', news: 'الأخبار', faq: 'الأسئلة الشائعة', contact: 'اتصل بنا',
  book: 'حجز موعد', book_short: 'موعد', see_all: 'عرض الكل', read_more: 'اقرأ المزيد',
  demo_banner: 'عرض تجريبي / نموذج غير رسمي',
  demo_note: 'بيانات وهمية: العناوين وأرقام الهاتف والرسوم والآجال وهمية. لا يُستخدم أي شعار رسمي.',
  demo_footer: 'عرض تجريبي / نموذج غير رسمي — هذا الموقع غير صادر عن أي قنصلية ولم تعتمده.',
  quick_access: 'وصول سريع إلى الخدمات', consul_word: 'كلمة القنصل', mission: 'مهمتنا',
  find_us: 'موقعنا', address: 'العنوان', hours: 'ساعات العمل', contacts: 'وسائل الاتصال',
  latest_news: 'آخر الأخبار', documents_required: 'الوثائق المطلوبة',
  documents_hint: 'ضع علامة عليها كلما جمعتها.', fee: 'الرسوم', delay: 'مدة المعالجة',
  search: 'ابحث…', search_title: 'ابحث في الموقع', no_result: 'لا توجد نتائج.',
  faq_search: 'ابحث عن سؤال…', contact_us: 'تواصل معنا', send: 'إرسال الرسالة',
  sent_ok: 'شكرًا، تم إرسال رسالتك. ستردّ القنصلية في أقرب وقت ممكن.',
  name: 'الاسم الكامل', email: 'البريد الإلكتروني', phone: 'الهاتف', subject: 'الموضوع', message: 'الرسالة',
  consent: 'أوافق على استخدام بياناتي لمعالجة طلبي (عرض تجريبي، مدة حفظ محدودة).',
  required: 'مطلوب', optional: 'اختياري',
  step_service: 'اختر الخدمة', step_date: 'اختر التاريخ', step_slot: 'اختر الموعد',
  step_details: 'بياناتك', no_dates: 'لا توجد مواعيد متاحة لهذه الخدمة حاليًا.',
  no_slots: 'هذا اليوم مكتمل. اختر تاريخًا آخر.', seats: 'أماكن متبقية', seat_one: 'مكان متبقٍ',
  choose: '— اختر —', confirm: 'تأكيد الموعد', saving: 'جارٍ الحفظ…',
  reference: 'المرجع', service: 'الخدمة', date: 'التاريخ', time: 'الساعة', status: 'الحالة',
  cancel_appt: 'إلغاء الموعد', cancel_done: 'تم إلغاء الموعد.', cancel_confirm: 'تأكيد الإلغاء؟',
  cancel_locked: 'لم يعد بالإمكان إلغاء هذا الموعد عبر الإنترنت. يُرجى الاتصال بالقنصلية.',
  download_ics: 'إضافة إلى التقويم (.ics)', token_missing: 'الرابط غير مكتمل: افتح الرابط الذي وصلك بالبريد الإلكتروني.',
  confirmed_title: 'تم تسجيل طلبك', confirmed_hint: 'احتفظ بهذا المرجع وأحضر الوثائق المطلوبة.',
  keep_link: 'هذا الرابط شخصي: لا تشاركه مع أحد.', back_home: 'العودة إلى الرئيسية',
  not_found: 'الصفحة غير موجودة', not_found_hint: 'هذه الصفحة غير موجودة أو لم تعد منشورة.',
  other_consulates: 'قنصليات أخرى في العرض التجريبي', choose_consulate: 'اختر قنصلية',
  choose_consulate_hint: 'منصة تجريبية: برنامج واحد وعدة قنصليات.',
  open_map: 'فتح في OpenStreetMap', map_note: 'موقع وهمي لأغراض العرض.',
  legal: 'معلومات', error: 'حدث خطأ. أعد المحاولة.', loading: 'جارٍ التحميل…',
  language: 'اللغة', menu: 'القائمة', skip: 'انتقل إلى المحتوى', whatsapp: 'واتساب', whatsapp_cta: 'راسلنا على واتساب',
  tooltip_seats: 'الأماكن المتاحة', previous: 'السابق', next: 'التالي', page: 'صفحة',
};

const DICTS: Record<Lang, Dict> = { fr, en, ar };

/** Renvoie le texte de l'interface pour la langue donnée (repli sur le français). */
export function t(lang: Lang, key: string): string {
  return DICTS[lang][key] ?? fr[key] ?? key;
}

/** Libellé de places restantes, avec le bon accord. */
export function seatsLabel(lang: Lang, n: number): string {
  return n === 1 ? t(lang, 'seat_one') : `${n} ${t(lang, 'seats')}`;
}
