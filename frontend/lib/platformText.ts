import { isLang } from '@/lib/i18n';
import type { Lang } from '@/lib/types';

/**
 * Textes des pages de la plateforme (en-tête, pied de page, À propos, Crédits, Connexion) en FR / EN / AR.
 * Adresses : /a-propos (français), /a-propos/en, /a-propos/ar — idem pour /credits et /connexion.
 */

export const PLATFORM_LANGS: Lang[] = ['fr', 'en', 'ar'];

/** Langue depuis le segment optionnel [[...lang]] : vide = français ; « en » ou « ar » ; sinon page introuvable (null). */
export function platformLang(segments?: string[]): Lang | null {
  if (!segments || segments.length === 0) return 'fr';
  if (segments.length === 1 && isLang(segments[0]) && segments[0] !== 'fr') return segments[0];
  return null;
}

export function platformPath(base: string, lang: Lang): string {
  if (base === '/') return lang === 'fr' ? '/' : `/${lang}`;
  return lang === 'fr' ? base : `${base}/${lang}`;
}

export const CHROME = {
  fr: {
    brand: 'Espace consulaire', tagline: 'Vos démarches, en toute simplicité', home: 'Accueil de la plateforme',
    nav: 'Navigation principale', consulates: 'Consulats', howItWorks: 'Fonctionnement', about: 'À propos', credits: 'Crédits',
    agents: 'Espace agents', citizen: 'Espace citoyen', language: 'Langue',
    footerText: 'Plateforme de gestion consulaire commune à plusieurs consulats : rendez-vous, dossiers, pièces justificatives, décisions et suivi, côté consulat comme côté citoyen. Les documents sont remis au guichet du consulat.',
    platform: 'Plateforme', chooseConsulate: 'Choisir un consulat', access: 'Accès', agentsLong: 'Espace agents et consuls',
    design: 'Conception', rights: 'Espace consulaire',
  },
  en: {
    brand: 'Consular Space', tagline: 'Your procedures, made simple', home: 'Platform home',
    nav: 'Main navigation', consulates: 'Consulates', howItWorks: 'How it works', about: 'About', credits: 'Credits',
    agents: 'Staff area', citizen: 'Citizen space', language: 'Language',
    footerText: 'A consular management platform shared by several consulates: appointments, applications, supporting documents, decisions and tracking, for consulates and citizens alike. Documents are handed over at the consulate counter.',
    platform: 'Platform', chooseConsulate: 'Choose a consulate', access: 'Access', agentsLong: 'Staff and consul area',
    design: 'Design', rights: 'Consular Space',
  },
  ar: {
    brand: 'الفضاء القنصلي', tagline: 'إجراءاتك بكل بساطة', home: 'الصفحة الرئيسية للمنصة',
    nav: 'التنقل الرئيسي', consulates: 'القنصليات', howItWorks: 'طريقة العمل', about: 'من نحن', credits: 'الاعتمادات',
    agents: 'فضاء الموظفين', citizen: 'فضاء المواطن', language: 'اللغة',
    footerText: 'منصة لإدارة الشؤون القنصلية مشتركة بين عدة قنصليات: المواعيد والملفات والوثائق الثبوتية والقرارات والمتابعة، للقنصلية وللمواطن على حد سواء. وتُسلَّم الوثائق في شباك القنصلية.',
    platform: 'المنصة', chooseConsulate: 'اختيار قنصلية', access: 'الدخول', agentsLong: 'فضاء الموظفين والقناصل',
    design: 'التصميم', rights: 'الفضاء القنصلي',
  },
} satisfies Record<Lang, Record<string, string>>;

type Card = { icon: string; title: string; text: string };

export const ABOUT: Record<Lang, {
  meta: string; eyebrow: string; title: string; lead: string; noticeStrong: string; notice: string;
  missionEyebrow: string; missionTitle: string; missionIntro: string; objectives: Card[];
  problemsEyebrow: string; problemsTitle: string; problems: { problem: string; answer: string }[];
  benefitsEyebrow: string; benefitsTitle: string; benefits: Card[];
  stepsEyebrow: string; stepsTitle: string; stepLabel: string; steps: { title: string; text: string }[];
  servicesEyebrow: string; servicesTitle: string; online: string; onlineItems: string[]; atConsulate: string; consulateItems: string[];
  ctaConsulate: string; ctaLogin: string;
}> = {
  fr: {
    meta: 'À propos', eyebrow: 'À propos',
    title: 'Une plateforme de gestion consulaire, pour les consulats et leurs citoyens',
    lead: 'L’Espace consulaire équipe plusieurs consulats d’un même outil : gestion des agents, des rendez-vous, des pré-demandes et de leurs pièces, des décisions et du suivi des dossiers. Les citoyens y disposent d’un espace personnel relié à leur consulat.',
    noticeStrong: 'Un outil de gestion, avec le guichet.',
    notice: 'Les dossiers sont examinés et décidés par les agents sur la plateforme ; la vérification des originaux, le paiement des frais et la remise des documents se font au consulat.',
    missionEyebrow: 'Mission', missionTitle: 'Moderniser la gestion des consulats', missionIntro: 'Trois objectifs guident la plateforme.',
    objectives: [
      { icon: 'calendar', title: 'Gérer', text: 'Organiser chaque consulat : agents et rôles, services, créneaux, rendez-vous du jour, abonnement et rapports.' },
      { icon: 'doc', title: 'Traiter', text: 'Examiner les pré-demandes et leurs pièces, valider, renvoyer pour complément ou refuser, puis suivre le dossier jusqu’au retrait.' },
      { icon: 'compass', title: 'Informer et suivre', text: 'Donner au citoyen les démarches, les créneaux et l’état de son dossier, avec une notification à chaque décision.' },
    ],
    problemsEyebrow: 'Constat', problemsTitle: 'Les difficultés auxquelles elle répond',
    problems: [
      { problem: 'Des déplacements pour rien', answer: 'La liste des pièces est consultable avant de venir, avec les cas particuliers (mineur, renouvellement…).' },
      { problem: 'Des files d’attente imprévisibles', answer: 'Chaque visite correspond à un créneau réservé ; le personnel connaît sa journée à l’avance.' },
      { problem: 'Une information dispersée', answer: 'Un seul site par consulat, tenu à jour par le consulat lui-même, en français, anglais et arabe.' },
      { problem: 'Un suivi difficile à distance', answer: 'L’espace citoyen rassemble rendez-vous, pré-demandes et statut du dossier.' },
    ],
    benefitsEyebrow: 'Pour les citoyens', benefitsTitle: 'Ce que la plateforme change',
    benefits: [
      { icon: 'clock', title: 'Du temps gagné', text: 'Préparation en ligne, passage au consulat sur rendez-vous.' },
      { icon: 'doc', title: 'Des dossiers complets', text: 'Pièces vérifiables à l’avance et pré-demande avec documents.' },
      { icon: 'phone', title: 'Accessible partout', text: 'Conçu pour le téléphone autant que pour l’ordinateur.' },
      { icon: 'shield', title: 'Des données protégées', text: 'Visibles uniquement par le consulat dont dépend le citoyen.' },
    ],
    stepsEyebrow: 'Fonctionnement', stepsTitle: 'Du site au guichet', stepLabel: 'Étape',
    steps: [
      { title: 'Choisir son consulat', text: 'Selon son pays de résidence.' },
      { title: 'Consulter la démarche', text: 'Conditions, pièces, frais, délai.' },
      { title: 'Créer son compte et réserver', text: 'Compte validé par le consulat, puis rendez-vous.' },
      { title: 'Se présenter au consulat', text: 'Avec sa référence et les originaux.' },
    ],
    servicesEyebrow: 'Services', servicesTitle: 'Ce que vous trouverez, et ce qui reste au consulat',
    online: 'Sur la plateforme',
    onlineItems: [
      'Fiches des démarches, pièces à fournir, frais et délais indicatifs',
      'Prise, déplacement et annulation de rendez-vous',
      'Pré-demande en ligne avec dépôt des pièces',
      'Espace famille, signalements et suivi du dossier',
      'Actualités, questions fréquentes, contact et itinéraire',
    ],
    atConsulate: 'Au consulat',
    consulateItems: [
      'Vérification des originaux et de l’identité',
      'Prise des données biométriques lorsque la démarche l’exige',
      'Paiement des frais consulaires',
      'Décision et délivrance des documents',
    ],
    ctaConsulate: 'Choisir mon consulat', ctaLogin: 'Se connecter',
  },
  en: {
    meta: 'About', eyebrow: 'About',
    title: 'A consular management platform, for consulates and their citizens',
    lead: 'The Consular Space gives several consulates one shared tool: managing staff, appointments, pre-applications and their documents, decisions and application tracking. Citizens get a personal space linked to their consulate.',
    noticeStrong: 'A management tool, working with the counter.',
    notice: 'Applications are examined and decided by staff on the platform; checking original documents, paying fees and handing over documents take place at the consulate.',
    missionEyebrow: 'Mission', missionTitle: 'Modernising consulate management', missionIntro: 'Three objectives guide the platform.',
    objectives: [
      { icon: 'calendar', title: 'Manage', text: 'Organise each consulate: staff and roles, services, time slots, daily appointments, subscription and reports.' },
      { icon: 'doc', title: 'Process', text: 'Examine pre-applications and their documents, approve, return for completion or refuse, then track the application until collection.' },
      { icon: 'compass', title: 'Inform and track', text: 'Give citizens procedures, available slots and the status of their application, with a notification at each decision.' },
    ],
    problemsEyebrow: 'The situation', problemsTitle: 'The difficulties it addresses',
    problems: [
      { problem: 'Wasted trips', answer: 'The list of documents can be checked before coming, including special cases (minors, renewals…).' },
      { problem: 'Unpredictable queues', answer: 'Each visit matches a booked slot; staff know their day in advance.' },
      { problem: 'Scattered information', answer: 'One website per consulate, kept up to date by the consulate itself, in French, English and Arabic.' },
      { problem: 'Hard to follow up from afar', answer: 'The citizen space gathers appointments, pre-applications and application status.' },
    ],
    benefitsEyebrow: 'For citizens', benefitsTitle: 'What the platform changes',
    benefits: [
      { icon: 'clock', title: 'Time saved', text: 'Prepare online, visit the consulate by appointment.' },
      { icon: 'doc', title: 'Complete applications', text: 'Documents checked in advance and pre-application with files.' },
      { icon: 'phone', title: 'Accessible anywhere', text: 'Designed for phones as much as for computers.' },
      { icon: 'shield', title: 'Protected data', text: 'Visible only to the consulate the citizen depends on.' },
    ],
    stepsEyebrow: 'How it works', stepsTitle: 'From the website to the counter', stepLabel: 'Step',
    steps: [
      { title: 'Choose your consulate', text: 'Based on your country of residence.' },
      { title: 'Check the procedure', text: 'Conditions, documents, fees, processing time.' },
      { title: 'Create an account and book', text: 'Account approved by the consulate, then an appointment.' },
      { title: 'Go to the consulate', text: 'With your reference and the original documents.' },
    ],
    servicesEyebrow: 'Services', servicesTitle: 'What you will find here, and what remains with the consulate',
    online: 'On the platform',
    onlineItems: [
      'Procedure sheets, documents to provide, fees and indicative processing times',
      'Booking, moving and cancelling appointments',
      'Online pre-application with document upload',
      'Family space, reports and application tracking',
      'News, frequently asked questions, contact and directions',
    ],
    atConsulate: 'At the consulate',
    consulateItems: [
      'Checking original documents and identity',
      'Biometric data capture when the procedure requires it',
      'Payment of consular fees',
      'Decision and issuance of documents',
    ],
    ctaConsulate: 'Choose my consulate', ctaLogin: 'Log in',
  },
  ar: {
    meta: 'من نحن', eyebrow: 'من نحن',
    title: 'منصة لإدارة الشؤون القنصلية، للقنصليات ومواطنيها',
    lead: 'يزوّد الفضاء القنصلي عدة قنصليات بأداة واحدة: إدارة الموظفين والمواعيد والطلبات المسبقة ووثائقها والقرارات ومتابعة الملفات. ويحصل المواطنون على فضاء شخصي مرتبط بقنصليتهم.',
    noticeStrong: 'أداة إدارة تعمل مع الشباك.',
    notice: 'يدرس الموظفون الملفات ويبتّون فيها على المنصة، أما التحقق من الوثائق الأصلية ودفع الرسوم وتسليم الوثائق فتتم في القنصلية.',
    missionEyebrow: 'المهمة', missionTitle: 'تحديث إدارة القنصليات', missionIntro: 'ثلاثة أهداف توجّه عمل المنصة.',
    objectives: [
      { icon: 'calendar', title: 'الإدارة', text: 'تنظيم كل قنصلية: الموظفون والأدوار والخدمات والفترات الزمنية ومواعيد اليوم والاشتراك والتقارير.' },
      { icon: 'doc', title: 'المعالجة', text: 'دراسة الطلبات المسبقة ووثائقها، والقبول أو الإرجاع للاستكمال أو الرفض، ثم متابعة الملف حتى الاستلام.' },
      { icon: 'compass', title: 'الإعلام والمتابعة', text: 'تزويد المواطن بالإجراءات والمواعيد المتاحة وحالة ملفه، مع إشعار عند كل قرار.' },
    ],
    problemsEyebrow: 'الواقع', problemsTitle: 'الصعوبات التي تعالجها',
    problems: [
      { problem: 'تنقلات دون جدوى', answer: 'يمكن الاطلاع على قائمة الوثائق قبل الحضور، مع الحالات الخاصة (القاصر، التجديد…).' },
      { problem: 'طوابير انتظار غير متوقعة', answer: 'كل زيارة تقابل موعداً محجوزاً، ويعرف الموظفون برنامج يومهم مسبقاً.' },
      { problem: 'معلومات متفرقة', answer: 'موقع واحد لكل قنصلية تحدّثه القنصلية نفسها، بالفرنسية والإنجليزية والعربية.' },
      { problem: 'صعوبة المتابعة عن بُعد', answer: 'يجمع فضاء المواطن المواعيد والطلبات المسبقة وحالة الملف.' },
    ],
    benefitsEyebrow: 'للمواطنين', benefitsTitle: 'ما الذي تغيّره المنصة',
    benefits: [
      { icon: 'clock', title: 'توفير الوقت', text: 'التحضير عبر الإنترنت والحضور إلى القنصلية بموعد.' },
      { icon: 'doc', title: 'ملفات مكتملة', text: 'وثائق يمكن التحقق منها مسبقاً وطلب مسبق مرفق بالمستندات.' },
      { icon: 'phone', title: 'متاحة في كل مكان', text: 'مصممة للهاتف بقدر ما هي مصممة للحاسوب.' },
      { icon: 'shield', title: 'بيانات محمية', text: 'لا يطّلع عليها إلا القنصلية التي يتبعها المواطن.' },
    ],
    stepsEyebrow: 'طريقة العمل', stepsTitle: 'من الموقع إلى الشباك', stepLabel: 'الخطوة',
    steps: [
      { title: 'اختيار القنصلية', text: 'حسب بلد الإقامة.' },
      { title: 'الاطلاع على الإجراء', text: 'الشروط والوثائق والرسوم والمدة.' },
      { title: 'إنشاء حساب والحجز', text: 'حساب تعتمده القنصلية، ثم موعد.' },
      { title: 'الحضور إلى القنصلية', text: 'مع المرجع والوثائق الأصلية.' },
    ],
    servicesEyebrow: 'الخدمات', servicesTitle: 'ما ستجده هنا، وما يبقى من اختصاص القنصلية',
    online: 'على المنصة',
    onlineItems: [
      'بطاقات الإجراءات والوثائق المطلوبة والرسوم والآجال التقريبية',
      'حجز المواعيد وتغييرها وإلغاؤها',
      'طلب مسبق عبر الإنترنت مع إيداع الوثائق',
      'فضاء العائلة والبلاغات ومتابعة الملف',
      'الأخبار والأسئلة الشائعة والاتصال والاتجاهات',
    ],
    atConsulate: 'في القنصلية',
    consulateItems: [
      'التحقق من الوثائق الأصلية والهوية',
      'أخذ البيانات البيومترية عندما يتطلب الإجراء ذلك',
      'دفع الرسوم القنصلية',
      'اتخاذ القرار وتسليم الوثائق',
    ],
    ctaConsulate: 'اختيار قنصليتي', ctaLogin: 'تسجيل الدخول',
  },
};

export const CREDITS: Record<Lang, {
  meta: string; eyebrow: string; lastNameLabel: string; firstNameLabel: string; profileLabel: string; profile: string;
  presEyebrow: string; presTitle: string; pres: string[];
  skillsEyebrow: string; skillsTitle: string; skills: Card[];
  contribEyebrow: string; contribTitle: string; contrib: string[];
  wordEyebrow: string; word: string; wordStrong: string;
}> = {
  fr: {
    meta: 'Crédits', eyebrow: 'Crédits · Conception et développement',
    lastNameLabel: 'Nom', firstNameLabel: 'Prénom', profileLabel: 'Profil', profile: 'Concepteur et développeur de solutions numériques',
    presEyebrow: 'Présentation', presTitle: 'Mettre le numérique au service des citoyens',
    pres: [
      'Concepteur et développeur de solutions numériques, je m’intéresse à la façon dont le numérique peut rendre les services publics plus simples, plus rapides et plus accessibles, en particulier pour les citoyens qui vivent loin de leur pays.',
      'Cette plateforme est née de cette conviction : rapprocher les consulats de leurs ressortissants, en partant de leurs besoins concrets et de l’usage réel du téléphone.',
    ],
    skillsEyebrow: 'Compétences', skillsTitle: 'Domaines d’intervention et d’intérêt',
    skills: [
      { icon: 'code', title: 'Développement web et mobile', text: 'Conception d’applications web et mobiles, de l’interface au serveur, pensées pour un usage sur téléphone.' },
      { icon: 'layers', title: 'Conception de solutions numériques', text: 'Analyse d’un besoin métier, modélisation, architecture et mise en œuvre d’outils numériques adaptés.' },
      { icon: 'chart', title: 'Analyse de données', text: 'Collecte, structuration et lecture des données pour éclairer les décisions et suivre l’activité.' },
      { icon: 'network', title: 'Administration réseau', text: 'Organisation, configuration et sécurisation des infrastructures réseau et des postes de travail.' },
      { icon: 'compass', title: 'Coaching et accompagnement des étudiants', text: 'Domaine d’intérêt : accompagner les étudiants dans leurs projets, leur orientation et leurs méthodes de travail.' },
      { icon: 'book', title: 'Formation informatique', text: 'Domaine d’intérêt : transmettre les connaissances numériques de façon claire, progressive et pratique.' },
    ],
    contribEyebrow: 'Contribution', contribTitle: 'Ce qui a été conçu et développé',
    contrib: [
      'Conception de la plateforme multi-consulats et de son organisation par rôles',
      'Développement des sites publics des consulats, en français, anglais et arabe',
      'Développement de l’espace citoyen : rendez-vous, pré-demandes, famille, signalements',
      'Développement de l’administration : accueil du jour, dossiers, abonnements, associations',
      'Mise en place des contrôles d’accès et de la protection des données',
    ],
    wordEyebrow: 'Le mot du concepteur',
    word: 'Je suis Tchadien, et je crois que chaque compatriote, où qu’il vive, mérite un service public simple, rapide et digne. J’ai conçu cette plateforme pour rapprocher nos consulats de leurs citoyens : moins d’attente, plus de transparence, et des démarches accessibles depuis un simple téléphone.',
    wordStrong: 'Servir mon pays par le numérique est mon engagement.',
  },
  en: {
    meta: 'Credits', eyebrow: 'Credits · Design and development',
    lastNameLabel: 'Last name', firstNameLabel: 'First name', profileLabel: 'Profile', profile: 'Designer and developer of digital solutions',
    presEyebrow: 'Introduction', presTitle: 'Putting digital technology at the service of citizens',
    pres: [
      'As a designer and developer of digital solutions, I am interested in how digital technology can make public services simpler, faster and more accessible, especially for citizens living far from their home country.',
      'This platform grew out of that conviction: bringing consulates closer to their nationals, starting from their concrete needs and from how people actually use their phones.',
    ],
    skillsEyebrow: 'Skills', skillsTitle: 'Areas of work and interest',
    skills: [
      { icon: 'code', title: 'Web and mobile development', text: 'Building web and mobile applications, from interface to server, designed for use on a phone.' },
      { icon: 'layers', title: 'Digital solution design', text: 'Analysing a business need, modelling, architecture and delivery of suitable digital tools.' },
      { icon: 'chart', title: 'Data analysis', text: 'Collecting, structuring and reading data to inform decisions and monitor activity.' },
      { icon: 'network', title: 'Network administration', text: 'Organising, configuring and securing network infrastructure and workstations.' },
      { icon: 'compass', title: 'Student coaching and mentoring', text: 'Area of interest: supporting students with their projects, orientation and working methods.' },
      { icon: 'book', title: 'IT training', text: 'Area of interest: passing on digital knowledge in a clear, gradual and practical way.' },
    ],
    contribEyebrow: 'Contribution', contribTitle: 'What was designed and developed',
    contrib: [
      'Design of the multi-consulate platform and its role-based organisation',
      'Development of the consulates’ public websites, in French, English and Arabic',
      'Development of the citizen space: appointments, pre-applications, family, reports',
      'Development of the administration: daily reception, applications, subscriptions, associations',
      'Implementation of access controls and data protection',
    ],
    wordEyebrow: 'A word from the designer',
    word: 'I am Chadian, and I believe that every fellow citizen, wherever they live, deserves a public service that is simple, fast and dignified. I designed this platform to bring our consulates closer to their citizens: less waiting, more transparency, and procedures accessible from a simple phone.',
    wordStrong: 'Serving my country through digital technology is my commitment.',
  },
  ar: {
    meta: 'الاعتمادات', eyebrow: 'الاعتمادات · التصميم والتطوير',
    lastNameLabel: 'اللقب', firstNameLabel: 'الاسم', profileLabel: 'الصفة', profile: 'مصمم ومطوّر حلول رقمية',
    presEyebrow: 'تقديم', presTitle: 'تسخير الرقمنة لخدمة المواطنين',
    pres: [
      'بصفتي مصمماً ومطوّراً للحلول الرقمية، أهتم بالطريقة التي يمكن بها للرقمنة أن تجعل الخدمات العامة أبسط وأسرع وأيسر وصولاً، ولا سيما للمواطنين المقيمين بعيداً عن بلدهم.',
      'وُلدت هذه المنصة من هذه القناعة: تقريب القنصليات من رعاياها، انطلاقاً من احتياجاتهم الفعلية ومن استخدامهم الحقيقي للهاتف.',
    ],
    skillsEyebrow: 'الكفاءات', skillsTitle: 'مجالات التدخل والاهتمام',
    skills: [
      { icon: 'code', title: 'تطوير الويب والهاتف المحمول', text: 'تصميم تطبيقات الويب والهاتف، من الواجهة إلى الخادم، بما يناسب الاستخدام على الهاتف.' },
      { icon: 'layers', title: 'تصميم الحلول الرقمية', text: 'تحليل الاحتياج، والنمذجة، والهندسة المعمارية، وتنفيذ أدوات رقمية ملائمة.' },
      { icon: 'chart', title: 'تحليل البيانات', text: 'جمع البيانات وهيكلتها وقراءتها لدعم القرارات ومتابعة النشاط.' },
      { icon: 'network', title: 'إدارة الشبكات', text: 'تنظيم البنى التحتية للشبكات ومحطات العمل وإعدادها وتأمينها.' },
      { icon: 'compass', title: 'تدريب الطلاب ومرافقتهم', text: 'مجال اهتمام: مرافقة الطلاب في مشاريعهم وتوجيههم وأساليب عملهم.' },
      { icon: 'book', title: 'التكوين في الإعلام الآلي', text: 'مجال اهتمام: نقل المعارف الرقمية بطريقة واضحة وتدريجية وعملية.' },
    ],
    contribEyebrow: 'المساهمة', contribTitle: 'ما تم تصميمه وتطويره',
    contrib: [
      'تصميم المنصة متعددة القنصليات وتنظيمها حسب الأدوار',
      'تطوير المواقع العامة للقنصليات بالفرنسية والإنجليزية والعربية',
      'تطوير فضاء المواطن: المواعيد، والطلبات المسبقة، والعائلة، والبلاغات',
      'تطوير فضاء الإدارة: استقبال اليوم، والملفات، والاشتراكات، والجمعيات',
      'وضع ضوابط الوصول وحماية البيانات',
    ],
    wordEyebrow: 'كلمة المصمم',
    word: 'أنا تشادي، وأؤمن بأن كل مواطن، أينما كان يعيش، يستحق خدمة عامة بسيطة وسريعة وكريمة. صمّمت هذه المنصة لتقريب قنصلياتنا من مواطنيها: انتظار أقل، وشفافية أكبر، وإجراءات في متناول الجميع من خلال هاتف بسيط.',
    wordStrong: 'خدمة بلدي عبر الرقمنة هي التزامي.',
  },
};

export const CONNEXION: Record<Lang, {
  meta: string; eyebrow: string; title: string; intro: string;
  citizenTitle: string; citizenText: string; unavailable: string; none: string;
  consulateLabel: string; consulatePlaceholder: string; continueCta: string;
  agentTitle: string; agentText: string; agentCta: string;
  noAccount: string; homeLink: string; noAccountEnd: string;
}> = {
  fr: {
    meta: 'Connexion', eyebrow: 'Connexion', title: 'Quel espace souhaitez-vous ouvrir ?',
    intro: 'Les citoyens et le personnel consulaire ont chacun leur espace et leurs identifiants.',
    citizenTitle: 'Espace citoyen',
    citizenText: 'Vos rendez-vous, vos pré-demandes, votre famille et votre profil. Votre compte dépend du consulat de votre pays de résidence.',
    unavailable: 'La liste des consulats est momentanément indisponible. Réessayez dans quelques instants.',
    none: 'Aucun consulat n’est encore ouvert sur la plateforme.',
    consulateLabel: 'Votre consulat', consulatePlaceholder: 'Choisissez selon votre pays de résidence', continueCta: 'Continuer vers la connexion citoyen',
    agentTitle: 'Espace agents et consuls',
    agentText: 'Accueil du jour, rendez-vous, dossiers et contenus du consulat. Accès réservé aux comptes créés par la plateforme ou par le consul.',
    agentCta: 'Se connecter à l’espace agents',
    noAccount: 'Pas encore de compte citoyen ? Choisissez votre consulat sur', homeLink: 'la page d’accueil', noAccountEnd: ', puis « Créer un compte ».',
  },
  en: {
    meta: 'Log in', eyebrow: 'Log in', title: 'Which space would you like to open?',
    intro: 'Citizens and consular staff each have their own space and their own credentials.',
    citizenTitle: 'Citizen space',
    citizenText: 'Your appointments, pre-applications, family and profile. Your account depends on the consulate of your country of residence.',
    unavailable: 'The list of consulates is temporarily unavailable. Please try again in a moment.',
    none: 'No consulate is open on the platform yet.',
    consulateLabel: 'Your consulate', consulatePlaceholder: 'Choose based on your country of residence', continueCta: 'Continue to citizen login',
    agentTitle: 'Staff and consul area',
    agentText: 'Daily reception, appointments, applications and consulate content. Access reserved for accounts created by the platform or by the consul.',
    agentCta: 'Log in to the staff area',
    noAccount: 'No citizen account yet? Choose your consulate on', homeLink: 'the home page', noAccountEnd: ', then “Create an account”.',
  },
  ar: {
    meta: 'تسجيل الدخول', eyebrow: 'تسجيل الدخول', title: 'أي فضاء تريد فتحه؟',
    intro: 'لكل من المواطنين والموظفين القنصليين فضاؤه وبيانات دخوله الخاصة.',
    citizenTitle: 'فضاء المواطن',
    citizenText: 'مواعيدك وطلباتك المسبقة وعائلتك وملفك الشخصي. يتبع حسابك قنصلية بلد إقامتك.',
    unavailable: 'قائمة القنصليات غير متاحة مؤقتاً. يرجى المحاولة بعد قليل.',
    none: 'لا توجد أي قنصلية مفتوحة على المنصة حتى الآن.',
    consulateLabel: 'قنصليتك', consulatePlaceholder: 'اختر حسب بلد إقامتك', continueCta: 'المتابعة إلى دخول المواطن',
    agentTitle: 'فضاء الموظفين والقناصل',
    agentText: 'استقبال اليوم والمواعيد والملفات ومحتوى القنصلية. الدخول مخصص للحسابات التي تنشئها المنصة أو القنصل.',
    agentCta: 'الدخول إلى فضاء الموظفين',
    noAccount: 'ليس لديك حساب مواطن بعد؟ اختر قنصليتك من', homeLink: 'الصفحة الرئيسية', noAccountEnd: '، ثم «إنشاء حساب».',
  },
};

type Step = { n: string; title: string; text: string };

/** Accueil de la plateforme (/, /en, /ar). */
export const HOME: Record<Lang, {
  meta: string; badge: string; heroTitle: string; heroAccent: string; heroText: string; findCta: string; howCta: string;
  journey: string; journeyNote: string; startEyebrow: string; chooseTitle: string; chooseText: string; loadError: string; noConsulate: string;
  howEyebrow: string; howTitle: string; steps: Step[]; servicesEyebrow: string; servicesTitle: string; features: { title: string; text: string }[];
  goodToKnow: string; tips: string[]; aboutCta: string;
  picker: { label: string; placeholder: string; one: string; many: string; empty: string; covered: string };
}> = {
  fr: {
    meta: 'Accueil', badge: 'Plateforme de gestion consulaire', heroTitle: 'Vos démarches consulaires,', heroAccent: 'suivies de bout en bout.',
    heroText: 'Rendez-vous, pré-demande avec pièces, décision du consulat et suivi du dossier : votre consulat et vous, sur la même plateforme.',
    findCta: 'Trouver mon consulat', howCta: 'Comment ça marche', journey: 'Votre parcours',
    journeyNote: 'Les démarches, frais et disponibilités sont publiés par chaque consulat.',
    startEyebrow: 'Commencer ici', chooseTitle: 'Choisissez votre consulat',
    chooseText: 'Votre compte et vos rendez-vous dépendent du consulat qui couvre votre pays de résidence.',
    loadError: 'La liste des consulats n’a pas pu être chargée. Réessayez dans quelques instants.',
    noConsulate: 'Aucun consulat n’est encore ouvert sur la plateforme.',
    howEyebrow: 'Fonctionnement', howTitle: 'Quatre étapes, du site au guichet',
    steps: [
      { n: '01', title: 'Choisissez votre consulat', text: 'Celui qui couvre votre pays de résidence.' },
      { n: '02', title: 'Consultez la démarche', text: 'Pièces, frais et délai, avant de vous déplacer.' },
      { n: '03', title: 'Réservez votre rendez-vous', text: 'Avec votre compte citoyen, validé par le consulat.' },
      { n: '04', title: 'Présentez-vous au consulat', text: 'Avec votre référence et les originaux de vos pièces.' },
    ],
    servicesEyebrow: 'Services en ligne', servicesTitle: 'Ce que vous pouvez faire ici',
    features: [
      { title: 'Démarches et pièces à fournir', text: 'Pour chaque service : conditions, documents à apporter, frais et délai indicatif publiés par le consulat.' },
      { title: 'Rendez-vous en ligne', text: 'Choisissez un créneau disponible, recevez votre référence, ajoutez-le à votre agenda, déplacez-le ou annulez-le.' },
      { title: 'Espace citoyen', text: 'Suivez vos rendez-vous, préparez une pré-demande avec vos pièces, gérez ceux de votre famille et signalez un problème.' },
      { title: 'Trois langues', text: 'Français, anglais et arabe, avec une mise en page adaptée à la lecture de droite à gauche.' },
    ],
    goodToKnow: 'Bon à savoir',
    tips: [
      'Vos pré-demandes sont examinées en ligne par les agents du consulat ; vous êtes prévenu à chaque décision.',
      'Apportez toujours les originaux des pièces demandées le jour du rendez-vous.',
      'Les frais et délais affichés sont indicatifs ; le consulat confirme le montant exact.',
    ],
    aboutCta: 'À propos de la plateforme',
    picker: { label: 'Dans quel pays résidez-vous ?', placeholder: 'Ex. Sénégal, Ghana, Cameroun…', one: 'consulat correspond', many: 'consulats correspondent', empty: 'Aucun consulat ne couvre encore ce pays sur la plateforme. Contactez l’ambassade ou le consulat le plus proche.', covered: 'Pays couverts' },
  },
  en: {
    meta: 'Home', badge: 'Consular management platform', heroTitle: 'Your consular procedures,', heroAccent: 'tracked from start to finish.',
    heroText: 'Appointments, pre-applications with documents, the consulate’s decision and application tracking: you and your consulate, on the same platform.',
    findCta: 'Find my consulate', howCta: 'How it works', journey: 'Your journey',
    journeyNote: 'Procedures, fees and availability are published by each consulate.',
    startEyebrow: 'Start here', chooseTitle: 'Choose your consulate',
    chooseText: 'Your account and appointments depend on the consulate covering your country of residence.',
    loadError: 'The list of consulates could not be loaded. Please try again in a moment.',
    noConsulate: 'No consulate is open on the platform yet.',
    howEyebrow: 'How it works', howTitle: 'Four steps, from the website to the counter',
    steps: [
      { n: '01', title: 'Choose your consulate', text: 'The one covering your country of residence.' },
      { n: '02', title: 'Check the procedure', text: 'Documents, fees and processing time, before you travel.' },
      { n: '03', title: 'Book your appointment', text: 'With your citizen account, approved by the consulate.' },
      { n: '04', title: 'Go to the consulate', text: 'With your reference and the original documents.' },
    ],
    servicesEyebrow: 'Online services', servicesTitle: 'What you can do here',
    features: [
      { title: 'Procedures and documents', text: 'For each service: conditions, documents to bring, fees and indicative processing time published by the consulate.' },
      { title: 'Online appointments', text: 'Pick an available slot, get your reference, add it to your calendar, move it or cancel it.' },
      { title: 'Citizen space', text: 'Follow your appointments, prepare a pre-application with your documents, manage your family’s and report a problem.' },
      { title: 'Three languages', text: 'French, English and Arabic, with a layout adapted to right-to-left reading.' },
    ],
    goodToKnow: 'Good to know',
    tips: [
      'Your pre-applications are reviewed online by consulate staff; you are notified of each decision.',
      'Always bring the original documents requested on the day of your appointment.',
      'Fees and processing times shown are indicative; the consulate confirms the exact amount.',
    ],
    aboutCta: 'About the platform',
    picker: { label: 'Which country do you live in?', placeholder: 'E.g. Senegal, Ghana, Cameroon…', one: 'consulate matches', many: 'consulates match', empty: 'No consulate covers this country on the platform yet. Contact the nearest embassy or consulate.', covered: 'Countries covered' },
  },
  ar: {
    meta: 'الرئيسية', badge: 'منصة لإدارة الشؤون القنصلية', heroTitle: 'إجراءاتك القنصلية،', heroAccent: 'متابَعة من البداية إلى النهاية.',
    heroText: 'المواعيد، والطلب المسبق مع الوثائق، وقرار القنصلية، ومتابعة الملف: أنت وقنصليتك على المنصة نفسها.',
    findCta: 'العثور على قنصليتي', howCta: 'طريقة العمل', journey: 'مسارك',
    journeyNote: 'تنشر كل قنصلية إجراءاتها ورسومها ومواعيدها المتاحة.',
    startEyebrow: 'ابدأ من هنا', chooseTitle: 'اختر قنصليتك',
    chooseText: 'يتبع حسابك ومواعيدك القنصلية التي تغطي بلد إقامتك.',
    loadError: 'تعذّر تحميل قائمة القنصليات. يرجى المحاولة بعد قليل.',
    noConsulate: 'لا توجد أي قنصلية مفتوحة على المنصة حتى الآن.',
    howEyebrow: 'طريقة العمل', howTitle: 'أربع خطوات، من الموقع إلى الشباك',
    steps: [
      { n: '01', title: 'اختر قنصليتك', text: 'التي تغطي بلد إقامتك.' },
      { n: '02', title: 'اطّلع على الإجراء', text: 'الوثائق والرسوم والمدة قبل التنقل.' },
      { n: '03', title: 'احجز موعدك', text: 'بحساب المواطن الذي تعتمده القنصلية.' },
      { n: '04', title: 'احضر إلى القنصلية', text: 'مع المرجع والوثائق الأصلية.' },
    ],
    servicesEyebrow: 'الخدمات عبر الإنترنت', servicesTitle: 'ما يمكنك القيام به هنا',
    features: [
      { title: 'الإجراءات والوثائق المطلوبة', text: 'لكل خدمة: الشروط والوثائق الواجب إحضارها والرسوم والمدة التقريبية التي تنشرها القنصلية.' },
      { title: 'المواعيد عبر الإنترنت', text: 'اختر فترة متاحة، واحصل على المرجع، وأضفه إلى تقويمك، أو غيّره أو ألغه.' },
      { title: 'فضاء المواطن', text: 'تابع مواعيدك، وحضّر طلباً مسبقاً مع وثائقك، وأدر مواعيد عائلتك، وأبلغ عن مشكلة.' },
      { title: 'ثلاث لغات', text: 'الفرنسية والإنجليزية والعربية، مع تنسيق ملائم للقراءة من اليمين إلى اليسار.' },
    ],
    goodToKnow: 'معلومات مفيدة',
    tips: [
      'يدرس موظفو القنصلية طلباتك المسبقة عبر الإنترنت، وتُبلَّغ بكل قرار.',
      'أحضر دائماً أصول الوثائق المطلوبة يوم الموعد.',
      'الرسوم والآجال المعروضة تقريبية، وتؤكد القنصلية المبلغ الدقيق.',
    ],
    aboutCta: 'من نحن',
    picker: { label: 'في أي بلد تقيم؟', placeholder: 'مثال: السنغال، غانا، الكاميرون…', one: 'قنصلية مطابقة', many: 'قنصليات مطابقة', empty: 'لا توجد بعد قنصلية تغطي هذا البلد على المنصة. تواصل مع أقرب سفارة أو قنصلية.', covered: 'البلدان المشمولة' },
  },
};
