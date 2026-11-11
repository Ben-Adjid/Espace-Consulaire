<?php

/*
 * Contenu de démonstration COMMUN aux consulats (fictif).
 * Placeholders remplacés par langue : {c} pays représenté, {a} adjectif de nationalité,
 * {n} nom du consulat, {h} pays d'accueil.
 * Les frais sont fournis par chaque consulat (clé `fees`).
 */

return [

    'services' => [
        [
            'slug' => 'passeport', 'icon' => 'passport',
            'name' => ['fr' => 'Passeport', 'en' => 'Passport', 'ar' => 'جواز السفر'],
            'summary' => [
                'fr' => 'Première demande ou renouvellement de votre passeport {a}.',
                'en' => 'First application or renewal of your {a} passport.',
                'ar' => 'طلب أول أو تجديد جواز سفرك {a}.',
            ],
            'description' => [
                'fr' => "Le consulat reçoit les demandes de passeport ordinaire pour les ressortissants résidant au {h}.\n\nLa présence du demandeur est obligatoire pour la prise des données biométriques. Les mineurs doivent être accompagnés de l'un de leurs parents.",
                'en' => "The consulate receives ordinary passport applications from nationals residing in {h}.\n\nThe applicant must be present for biometric data capture. Minors must be accompanied by one of their parents.",
                'ar' => "تستقبل القنصلية طلبات جواز السفر العادي من المواطنين المقيمين في {h}.\n\nيجب حضور صاحب الطلب لأخذ البيانات البيومترية. ويجب أن يرافق القاصرَ أحدُ والديه.",
            ],
            'documents' => [
                'fr' => "Formulaire de demande dûment rempli\nActe de naissance (copie intégrale)\nAncien passeport (en cas de renouvellement)\nCarte de résident ou titre de séjour au {h}\n2 photos d'identité récentes (fond blanc)\nJustificatif de domicile de moins de 3 mois\nQuittance de paiement des frais",
                'en' => "Completed application form\nBirth certificate (full copy)\nOld passport (for renewals)\nResidence card or residence permit in {h}\n2 recent passport photos (white background)\nProof of address less than 3 months old\nPayment receipt",
                'ar' => "استمارة الطلب مملوءة\nشهادة الميلاد (نسخة كاملة)\nجواز السفر القديم (عند التجديد)\nبطاقة الإقامة أو رخصة الإقامة في {h}\nصورتان شخصيتان حديثتان (خلفية بيضاء)\nإثبات السكن لا يتجاوز 3 أشهر\nوصل دفع الرسوم",
            ],
            'delay' => ['fr' => '4 à 6 semaines', 'en' => '4 to 6 weeks', 'ar' => 'من 4 إلى 6 أسابيع'],
        ],
        [
            'slug' => 'carte-consulaire', 'icon' => 'id-card',
            'name' => ['fr' => 'Carte consulaire', 'en' => 'Consular card', 'ar' => 'البطاقة القنصلية'],
            'summary' => [
                'fr' => 'Carte attestant de votre inscription au consulat.',
                'en' => 'Card certifying your registration with the consulate.',
                'ar' => 'بطاقة تثبت تسجيلك لدى القنصلية.',
            ],
            'description' => [
                'fr' => "La carte consulaire atteste de votre immatriculation auprès du consulat et facilite vos démarches administratives au {h}.\n\nElle est délivrée sur présentation d'une pièce d'identité en cours de validité.",
                'en' => "The consular card certifies your registration with the consulate and simplifies your administrative procedures in {h}.\n\nIt is issued upon presentation of a valid identity document.",
                'ar' => "تثبت البطاقة القنصلية تسجيلك لدى القنصلية وتسهّل إجراءاتك الإدارية في {h}.\n\nتُسلَّم عند تقديم وثيقة هوية سارية الصلاحية.",
            ],
            'documents' => [
                'fr' => "Passeport ou carte nationale d'identité en cours de validité\n2 photos d'identité récentes\nJustificatif de domicile\nCarte de résident ou titre de séjour\nQuittance de paiement des frais",
                'en' => "Valid passport or national ID card\n2 recent passport photos\nProof of address\nResidence card or residence permit\nPayment receipt",
                'ar' => "جواز سفر أو بطاقة هوية وطنية سارية\nصورتان شخصيتان حديثتان\nإثبات السكن\nبطاقة الإقامة أو رخصة الإقامة\nوصل دفع الرسوم",
            ],
            'delay' => ['fr' => '2 semaines', 'en' => '2 weeks', 'ar' => 'أسبوعان'],
        ],
        [
            'slug' => 'visa', 'icon' => 'visa',
            'name' => ['fr' => 'Visa', 'en' => 'Visa', 'ar' => 'التأشيرة'],
            'summary' => [
                'fr' => 'Demande de visa pour les ressortissants étrangers se rendant en {c}.',
                'en' => 'Visa application for foreign nationals travelling to {c}.',
                'ar' => 'طلب تأشيرة للأجانب المسافرين إلى {c}.',
            ],
            'description' => [
                'fr' => "Le consulat instruit les demandes de visa (court séjour, affaires, tourisme) pour les voyageurs se rendant en {c}.\n\nLe dépôt du dossier ne garantit pas l'octroi du visa. Les frais ne sont pas remboursables.",
                'en' => "The consulate processes visa applications (short stay, business, tourism) for travellers going to {c}.\n\nSubmitting an application does not guarantee that a visa will be granted. Fees are non-refundable.",
                'ar' => "تدرس القنصلية طلبات التأشيرة (إقامة قصيرة، أعمال، سياحة) للمسافرين إلى {c}.\n\nلا يضمن إيداع الملف منح التأشيرة، والرسوم غير قابلة للاسترداد.",
            ],
            'documents' => [
                'fr' => "Passeport valide encore 6 mois minimum\nFormulaire de demande de visa\n2 photos d'identité récentes\nBillet d'avion aller-retour (réservation)\nJustificatif d'hébergement\nJustificatifs de ressources financières\nCarte de séjour au {h} (pour les résidents)",
                'en' => "Passport valid for at least 6 more months\nVisa application form\n2 recent passport photos\nRound-trip air ticket (reservation)\nProof of accommodation\nProof of financial resources\nResidence permit in {h} (for residents)",
                'ar' => "جواز سفر صالح لستة أشهر على الأقل\nاستمارة طلب التأشيرة\nصورتان شخصيتان حديثتان\nتذكرة طيران ذهاب وإياب (حجز)\nإثبات السكن\nإثبات الموارد المالية\nبطاقة إقامة في {h} (للمقيمين)",
            ],
            'delay' => ['fr' => '5 à 10 jours ouvrés', 'en' => '5 to 10 working days', 'ar' => 'من 5 إلى 10 أيام عمل'],
        ],
        [
            'slug' => 'legalisation-documents', 'icon' => 'stamp',
            'name' => ['fr' => 'Légalisation de documents', 'en' => 'Document legalisation', 'ar' => 'تصديق الوثائق'],
            'summary' => [
                'fr' => 'Légalisation de la signature et du cachet apposés sur vos documents.',
                'en' => 'Legalisation of the signature and stamp on your documents.',
                'ar' => 'تصديق التوقيع والختم الموضوعين على وثائقك.',
            ],
            'description' => [
                'fr' => "La légalisation authentifie la signature et la qualité du signataire d'un document destiné à être utilisé en {c}.\n\nLe document doit d'abord être authentifié par les autorités compétentes du {h}.",
                'en' => "Legalisation authenticates the signature and capacity of the signatory of a document to be used in {c}.\n\nThe document must first be authenticated by the competent authorities of {h}.",
                'ar' => "يوثّق التصديق توقيع وصفة الموقّع على وثيقة معدّة للاستعمال في {c}.\n\nيجب أولًا توثيق الوثيقة لدى السلطات المختصة في {h}.",
            ],
            'documents' => [
                'fr' => "Document original à légaliser\nCopie du document\nPièce d'identité du demandeur\nAuthentification préalable par les autorités du {h}\nQuittance de paiement des frais (par document)",
                'en' => "Original document to be legalised\nCopy of the document\nApplicant's ID\nPrior authentication by the authorities of {h}\nPayment receipt (per document)",
                'ar' => "الوثيقة الأصلية المراد تصديقها\nنسخة من الوثيقة\nهوية صاحب الطلب\nتوثيق مسبق لدى سلطات {h}\nوصل دفع الرسوم (لكل وثيقة)",
            ],
            'delay' => ['fr' => '48 heures', 'en' => '48 hours', 'ar' => '48 ساعة'],
        ],
        [
            'slug' => 'actes-etat-civil', 'icon' => 'users',
            'name' => ['fr' => "Actes d'état civil", 'en' => 'Civil status records', 'ar' => 'وثائق الحالة المدنية'],
            'summary' => [
                'fr' => 'Naissance, mariage, décès : transcription et copies d’actes.',
                'en' => 'Birth, marriage, death: transcription and copies of records.',
                'ar' => 'الولادة والزواج والوفاة: تسجيل ونسخ من الوثائق.',
            ],
            'description' => [
                'fr' => "Le consulat transcrit les actes d'état civil dressés au {h} concernant des ressortissants de {c} et délivre des copies intégrales ou extraits.\n\nLes actes étrangers doivent être légalisés et traduits si nécessaire.",
                'en' => "The consulate transcribes civil status records drawn up in {h} for nationals of {c} and issues full copies or extracts.\n\nForeign records must be legalised and translated where necessary.",
                'ar' => "تسجّل القنصلية وثائق الحالة المدنية المحررة في {h} الخاصة بالمواطنين وتسلّم نسخًا كاملة أو مستخرجات.\n\nيجب تصديق الوثائق الأجنبية وترجمتها عند الحاجة.",
            ],
            'documents' => [
                'fr' => "Acte étranger original (légalisé)\nTraduction certifiée si nécessaire\nPièces d'identité des intéressés\nLivret de famille (le cas échéant)\nQuittance de paiement des frais",
                'en' => "Original foreign record (legalised)\nCertified translation if necessary\nID documents of the persons concerned\nFamily record book (if applicable)\nPayment receipt",
                'ar' => "الوثيقة الأجنبية الأصلية (مصدّقة)\nترجمة معتمدة عند الحاجة\nوثائق هوية المعنيين\nدفتر الحالة العائلية (إن وجد)\nوصل دفع الرسوم",
            ],
            'delay' => ['fr' => '1 à 3 semaines', 'en' => '1 to 3 weeks', 'ar' => 'من أسبوع إلى 3 أسابيع'],
        ],
        [
            'slug' => 'procuration', 'icon' => 'pen',
            'name' => ['fr' => 'Procuration', 'en' => 'Power of attorney', 'ar' => 'الوكالة'],
            'summary' => [
                'fr' => 'Établissement d’une procuration pour être représenté en {c}.',
                'en' => 'Drawing up a power of attorney to be represented in {c}.',
                'ar' => 'إعداد وكالة للتمثيل في {c}.',
            ],
            'description' => [
                'fr' => "Le mandant doit se présenter en personne au consulat pour signer la procuration devant l'agent habilité.\n\nIndiquez précisément l'objet de la procuration et l'identité du mandataire.",
                'en' => "The principal must appear in person at the consulate to sign the power of attorney before the authorised officer.\n\nState precisely the purpose of the power of attorney and the identity of the agent.",
                'ar' => "يجب أن يحضر الموكِّل شخصيًا إلى القنصلية للتوقيع على الوكالة أمام الموظف المخوَّل.\n\nحدّد بدقة موضوع الوكالة وهوية الوكيل.",
            ],
            'documents' => [
                'fr' => "Pièce d'identité du mandant\nPièce d'identité du mandataire (copie)\nProjet de procuration précisant l'objet\nDocuments liés à l'objet (titre, acte…)\nQuittance de paiement des frais",
                'en' => "Principal's ID\nAgent's ID (copy)\nDraft power of attorney stating its purpose\nDocuments related to the purpose (title deed, record…)\nPayment receipt",
                'ar' => "هوية الموكِّل\nهوية الوكيل (نسخة)\nمشروع الوكالة يحدد الموضوع\nوثائق متعلقة بالموضوع (سند، عقد…)\nوصل دفع الرسوم",
            ],
            'delay' => ['fr' => 'Le jour même', 'en' => 'Same day', 'ar' => 'في اليوم نفسه'],
        ],
        [
            'slug' => 'laissez-passer', 'icon' => 'plane',
            'name' => ['fr' => 'Laissez-passer', 'en' => 'Emergency travel document', 'ar' => 'وثيقة المرور'],
            'summary' => [
                'fr' => 'Document de voyage d’urgence en cas de perte ou de vol du passeport.',
                'en' => 'Emergency travel document in case of loss or theft of your passport.',
                'ar' => 'وثيقة سفر طارئة في حال ضياع جواز السفر أو سرقته.',
            ],
            'description' => [
                'fr' => "Le laissez-passer est un titre de voyage à usage unique, valable pour un seul trajet vers {c}.\n\nEn cas de vol, présentez le récépissé de déclaration délivré par la police.",
                'en' => "The emergency travel document is a single-use travel title, valid for one journey to {c}.\n\nIn case of theft, present the police report receipt.",
                'ar' => "وثيقة المرور سند سفر لمرة واحدة صالح لرحلة واحدة نحو {c}.\n\nفي حالة السرقة، قدّم وصل التصريح الصادر عن الشرطة.",
            ],
            'documents' => [
                'fr' => "Déclaration de perte ou de vol (police)\nCopie de l'ancien passeport ou toute pièce d'identité\n2 photos d'identité récentes\nBillet d'avion (réservation)\nQuittance de paiement des frais",
                'en' => "Loss or theft report (police)\nCopy of the old passport or any ID\n2 recent passport photos\nAir ticket (reservation)\nPayment receipt",
                'ar' => "تصريح بالضياع أو السرقة (الشرطة)\nنسخة من الجواز القديم أو أي وثيقة هوية\nصورتان شخصيتان حديثتان\nتذكرة طيران (حجز)\nوصل دفع الرسوم",
            ],
            'delay' => ['fr' => '24 à 48 heures', 'en' => '24 to 48 hours', 'ar' => 'من 24 إلى 48 ساعة'],
        ],
    ],

    'faqs' => [
        [
            'q' => ['fr' => 'Comment prendre rendez-vous au consulat ?', 'en' => 'How do I book an appointment at the consulate?', 'ar' => 'كيف أحجز موعدًا في القنصلية؟'],
            'a' => [
                'fr' => "Utilisez le bouton « Prendre rendez-vous » : choisissez le service, une date puis un créneau disponible. Vous recevrez un email contenant votre référence unique.",
                'en' => "Use the “Book an appointment” button: choose the service, a date and an available slot. You will receive an email with your unique reference.",
                'ar' => "استخدم زر «حجز موعد»: اختر الخدمة والتاريخ ثم موعدًا متاحًا. ستتلقى بريدًا إلكترونيًا يتضمن مرجعك الفريد.",
            ],
        ],
        [
            'q' => ['fr' => 'Puis-je modifier ou annuler mon rendez-vous ?', 'en' => 'Can I change or cancel my appointment?', 'ar' => 'هل يمكنني تعديل موعدي أو إلغاؤه؟'],
            'a' => [
                'fr' => "Contactez-nous au moins 24 h à l'avance par téléphone, WhatsApp ou via le formulaire de contact en indiquant votre référence.",
                'en' => "Contact us at least 24 hours in advance by phone, WhatsApp or through the contact form, quoting your reference.",
                'ar' => "تواصل معنا قبل 24 ساعة على الأقل عبر الهاتف أو واتساب أو نموذج الاتصال مع ذكر مرجعك.",
            ],
        ],
        [
            'q' => ['fr' => 'Quels sont les moyens de paiement des frais consulaires ?', 'en' => 'How can I pay consular fees?', 'ar' => 'ما هي وسائل دفع الرسوم القنصلية؟'],
            'a' => [
                'fr' => "Dans cette démo, aucun paiement n'est géré en ligne. Dans la version complète, les paiements par mobile money et par virement seront proposés.",
                'en' => "In this demo no payment is handled online. In the full version, mobile money and bank transfer payments will be offered.",
                'ar' => "في هذا العرض التجريبي لا تتم أي مدفوعات عبر الإنترنت. في النسخة الكاملة ستتوفر المدفوعات عبر المحفظة الإلكترونية والتحويل البنكي.",
            ],
        ],
        [
            'q' => ['fr' => 'Combien de temps faut-il pour obtenir un passeport ?', 'en' => 'How long does it take to get a passport?', 'ar' => 'كم يستغرق الحصول على جواز السفر؟'],
            'a' => [
                'fr' => "Le délai indicatif est de 4 à 6 semaines après la prise des données biométriques. Ce délai est fictif dans cette démonstration.",
                'en' => "The indicative delay is 4 to 6 weeks after biometric data capture. This delay is fictitious in this demonstration.",
                'ar' => "المدة التقريبية من 4 إلى 6 أسابيع بعد أخذ البيانات البيومترية. وهذه المدة وهمية في هذا العرض التجريبي.",
            ],
        ],
        [
            'q' => ['fr' => 'Que faire en cas de perte ou de vol de mon passeport ?', 'en' => 'What should I do if my passport is lost or stolen?', 'ar' => 'ماذا أفعل إذا ضاع جواز سفري أو سُرق؟'],
            'a' => [
                'fr' => "Déclarez la perte ou le vol à la police, puis présentez-vous au consulat avec le récépissé pour demander un laissez-passer ou un nouveau passeport.",
                'en' => "Report the loss or theft to the police, then come to the consulate with the receipt to request an emergency travel document or a new passport.",
                'ar' => "بلّغ الشرطة عن الضياع أو السرقة، ثم توجّه إلى القنصلية مع الوصل لطلب وثيقة مرور أو جواز جديد.",
            ],
        ],
        [
            'q' => ['fr' => 'Les informations de ce site sont-elles officielles ?', 'en' => 'Is the information on this site official?', 'ar' => 'هل معلومات هذا الموقع رسمية؟'],
            'a' => [
                'fr' => "Non. Il s'agit d'une démonstration non officielle : adresses, téléphones, frais et délais sont fictifs. Ne vous fiez pas à ces informations pour une démarche réelle.",
                'en' => "No. This is an unofficial demonstration: addresses, phone numbers, fees and delays are fictitious. Do not rely on this information for a real procedure.",
                'ar' => "لا. هذا عرض تجريبي غير رسمي: العناوين وأرقام الهاتف والرسوم والآجال كلها وهمية. لا تعتمد على هذه المعلومات في إجراء حقيقي.",
            ],
        ],
    ],

    'pages' => [
        [
            'slug' => 'mentions-legales',
            'title' => ['fr' => 'Mentions légales', 'en' => 'Legal notice', 'ar' => 'إشعار قانوني'],
            'body' => [
                'fr' => "DÉMO / MAQUETTE NON OFFICIELLE.\n\nCe site est une maquette de démonstration d'une plateforme multi-consulats. Il n'est ni édité, ni validé, ni exploité par {n} ni par aucune autorité de {c} ou du {h}.\n\nAucun logo, armoirie ou signe officiel n'est utilisé. Les adresses, numéros de téléphone, adresses email, frais et délais affichés sont fictifs.\n\nÉditeur de la démonstration : équipe projet de la plateforme (contact via le formulaire de contact).",
                'en' => "UNOFFICIAL DEMO / MOCKUP.\n\nThis site is a demonstration mockup of a multi-consulate platform. It is not published, validated or operated by {n} or by any authority of {c} or {h}.\n\nNo official logo, coat of arms or sign is used. The addresses, phone numbers, email addresses, fees and delays displayed are fictitious.\n\nPublisher of the demonstration: the platform project team (contact via the contact form).",
                'ar' => "عرض تجريبي / نموذج غير رسمي.\n\nهذا الموقع نموذج تجريبي لمنصة متعددة القنصليات. لا يصدر عن {n} ولا عن أي سلطة في {c} أو {h}، ولم تعتمده أي جهة منها.\n\nلا يُستخدم أي شعار أو رمز رسمي. العناوين وأرقام الهاتف والبريد الإلكتروني والرسوم والآجال المعروضة كلها وهمية.\n\nناشر العرض التجريبي: فريق مشروع المنصة (التواصل عبر نموذج الاتصال).",
            ],
        ],
        [
            'slug' => 'confidentialite',
            'title' => ['fr' => 'Politique de confidentialité', 'en' => 'Privacy policy', 'ar' => 'سياسة الخصوصية'],
            'body' => [
                'fr' => "Cette démonstration collecte uniquement les données saisies dans les formulaires (nom, email, téléphone, message) afin de simuler la gestion des rendez-vous et des messages.\n\nCes données sont stockées dans la base de la démonstration, ne sont ni vendues ni transmises à des tiers, et peuvent être supprimées à tout moment par l'administrateur.\n\nNous vous conseillons de ne saisir aucune donnée personnelle réelle ou sensible dans cette démo.",
                'en' => "This demonstration only collects the data entered in the forms (name, email, phone, message) in order to simulate appointment and message handling.\n\nThis data is stored in the demonstration database, is neither sold nor shared with third parties, and can be deleted at any time by the administrator.\n\nWe advise you not to enter any real or sensitive personal data in this demo.",
                'ar' => "يجمع هذا العرض التجريبي فقط البيانات المدخلة في النماذج (الاسم والبريد والهاتف والرسالة) لمحاكاة إدارة المواعيد والرسائل.\n\nتُخزَّن هذه البيانات في قاعدة بيانات العرض، ولا تُباع ولا تُنقل إلى أطراف ثالثة، ويمكن للمسؤول حذفها في أي وقت.\n\nننصحك بعدم إدخال أي بيانات شخصية حقيقية أو حساسة في هذا العرض.",
            ],
        ],
        [
            'slug' => 'demo-non-officielle',
            'title' => ['fr' => 'Démo non officielle', 'en' => 'Unofficial demo', 'ar' => 'عرض غير رسمي'],
            'body' => [
                'fr' => "⚠ Ce site est une DÉMO / MAQUETTE NON OFFICIELLE.\n\nIl illustre comment un consulat africain pourrait digitaliser ses services : information, rendez-vous en ligne, actualités et contact. Il ne représente pas {n} et ne produit aucun effet juridique ni administratif.\n\nToutes les données (adresses, téléphones, frais, délais, personnes citées) sont fictives.",
                'en' => "⚠ This site is an UNOFFICIAL DEMO / MOCKUP.\n\nIt illustrates how an African consulate could digitalise its services: information, online appointments, news and contact. It does not represent {n} and has no legal or administrative effect.\n\nAll data (addresses, phone numbers, fees, delays, people mentioned) is fictitious.",
                'ar' => "⚠ هذا الموقع عرض تجريبي / نموذج غير رسمي.\n\nيوضح كيف يمكن لقنصلية إفريقية رقمنة خدماتها: معلومات ومواعيد عبر الإنترنت وأخبار وتواصل. وهو لا يمثل {n} ولا ينتج أي أثر قانوني أو إداري.\n\nجميع البيانات (العناوين والهواتف والرسوم والآجال والأشخاص المذكورون) وهمية.",
            ],
        ],
    ],
];
