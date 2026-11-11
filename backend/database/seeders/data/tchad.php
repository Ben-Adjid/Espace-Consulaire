<?php

/* Consulat du Tchad au Sénégal — DONNÉES FICTIVES (démo non officielle). */

return [
    'vars' => [
        'c' => ['fr' => 'Tchad', 'en' => 'Chad', 'ar' => 'تشاد'],
        'a' => ['fr' => 'tchadien', 'en' => 'Chadian', 'ar' => 'التشادي'],
        'h' => ['fr' => 'Sénégal', 'en' => 'Senegal', 'ar' => 'السنغال'],
        'n' => ['fr' => 'Consulat du Tchad au Sénégal', 'en' => 'Consulate of Chad in Senegal', 'ar' => 'قنصلية تشاد في السنغال'],
    ],

    'consulate' => [
        'slug' => 'tchad-senegal',
        'ref_prefix' => 'TCH-SN',
        'primary_color' => '#002664',   // bleu
        'secondary_color' => '#FECB00', // jaune
        'accent_color' => '#C60C30',    // rouge
        'latitude' => 14.6937,
        'longitude' => -17.4441,
        'phone' => '+221 33 800 00 01',
        'email' => 'contact@tchad-senegal.demo.test',
        'whatsapp' => '+221 77 000 00 01',
        'consul_name' => 'M. Mahamat Démo (personnage fictif)',
        'languages' => ['fr', 'en', 'ar'],
        'name' => ['fr' => 'Consulat du Tchad au Sénégal', 'en' => 'Consulate of Chad in Senegal', 'ar' => 'قنصلية تشاد في السنغال'],
        'represented_country' => ['fr' => 'Tchad', 'en' => 'Chad', 'ar' => 'تشاد'],
        'host_country' => ['fr' => 'Sénégal', 'en' => 'Senegal', 'ar' => 'السنغال'],
        'address' => [
            'fr' => "12 rue Exemple, Plateau\nDakar, Sénégal (adresse fictive)",
            'en' => "12 Example Street, Plateau\nDakar, Senegal (fictitious address)",
            'ar' => "12 شارع المثال، البلاتو\nداكار، السنغال (عنوان وهمي)",
        ],
        'opening_hours' => [
            'fr' => "Lundi – Vendredi : 09h00 – 15h00\nSamedi et dimanche : fermé\nDépôt des dossiers jusqu'à 14h00",
            'en' => "Monday – Friday: 9:00 am – 3:00 pm\nSaturday and Sunday: closed\nFile submission until 2:00 pm",
            'ar' => "الاثنين – الجمعة: 09:00 – 15:00\nالسبت والأحد: مغلق\nإيداع الملفات حتى 14:00",
        ],
        'hero_title' => [
            'fr' => 'Consulat du Tchad au Sénégal',
            'en' => 'Consulate of Chad in Senegal',
            'ar' => 'قنصلية تشاد في السنغال',
        ],
        'hero_subtitle' => [
            'fr' => 'Vos démarches consulaires en ligne : informations, documents requis et rendez-vous, simplement.',
            'en' => 'Your consular procedures online: information, required documents and appointments, made simple.',
            'ar' => 'إجراءاتك القنصلية عبر الإنترنت: معلومات ووثائق مطلوبة ومواعيد بكل سهولة.',
        ],
        'consul_message' => [
            'fr' => "Chers compatriotes, chers amis du Tchad,\n\nNous avons à cœur de rapprocher le consulat de vous. Ce site vous permet de vous informer sur les services, de préparer votre dossier et de réserver un rendez-vous sans vous déplacer inutilement.\n\nNotre équipe reste à votre écoute.",
            'en' => "Dear compatriots, dear friends of Chad,\n\nWe are committed to bringing the consulate closer to you. This site lets you learn about our services, prepare your file and book an appointment without unnecessary trips.\n\nOur team remains at your disposal.",
            'ar' => "أعزائي المواطنين، أصدقاء تشاد،\n\nنحرص على تقريب القنصلية منكم. يتيح لكم هذا الموقع الاطلاع على الخدمات وإعداد ملفكم وحجز موعد دون تنقل غير ضروري.\n\nفريقنا في خدمتكم.",
        ],
        'mission' => [
            'fr' => "Protéger les intérêts des ressortissants tchadiens résidant au Sénégal, délivrer les documents administratifs et consulaires, et faciliter les relations entre nos deux pays.",
            'en' => "Protect the interests of Chadian nationals living in Senegal, issue administrative and consular documents, and facilitate relations between our two countries.",
            'ar' => "حماية مصالح المواطنين التشاديين المقيمين في السنغال، وإصدار الوثائق الإدارية والقنصلية، وتسهيل العلاقات بين بلدينا.",
        ],
    ],

    'fees' => [
        'passeport' => '60 000 FCFA',
        'carte-consulaire' => '15 000 FCFA',
        'visa' => '50 000 FCFA',
        'legalisation-documents' => '5 000 FCFA / doc.',
        'actes-etat-civil' => '10 000 FCFA',
        'procuration' => '12 000 FCFA',
        'laissez-passer' => '25 000 FCFA',
    ],

    'news' => [
        [
            'slug' => 'nouveaux-horaires-ete', 'days_ago' => 3,
            'title' => ['fr' => "Nouveaux horaires d'accueil", 'en' => 'New opening hours', 'ar' => 'ساعات استقبال جديدة'],
            'excerpt' => [
                'fr' => "Le consulat vous accueille désormais de 9h à 15h du lundi au vendredi.",
                'en' => 'The consulate now welcomes you from 9 am to 3 pm, Monday to Friday.',
                'ar' => 'تستقبلكم القنصلية الآن من 9 صباحًا إلى 3 مساءً من الاثنين إلى الجمعة.',
            ],
            'body' => [
                'fr' => "À compter de ce mois, le consulat accueille le public du lundi au vendredi de 9h00 à 15h00. Le dépôt des dossiers est possible jusqu'à 14h00.\n\nNous vous invitons à réserver votre rendez-vous en ligne pour limiter l'attente.",
                'en' => "From this month, the consulate is open to the public from Monday to Friday, 9:00 am to 3:00 pm. Files can be submitted until 2:00 pm.\n\nWe invite you to book your appointment online to limit waiting time.",
                'ar' => "ابتداءً من هذا الشهر، تستقبل القنصلية الجمهور من الاثنين إلى الجمعة من 09:00 إلى 15:00، ويمكن إيداع الملفات حتى 14:00.\n\nندعوكم إلى حجز موعدكم عبر الإنترنت لتقليل الانتظار.",
            ],
        ],
        [
            'slug' => 'rendez-vous-en-ligne', 'days_ago' => 10,
            'title' => ['fr' => 'Lancement de la prise de rendez-vous en ligne', 'en' => 'Online appointment booking is live', 'ar' => 'إطلاق حجز المواعيد عبر الإنترنت'],
            'excerpt' => [
                'fr' => 'Réservez votre créneau en quelques clics, 24h/24.',
                'en' => 'Book your slot in a few clicks, 24/7.',
                'ar' => 'احجز موعدك ببضع نقرات، على مدار الساعة.',
            ],
            'body' => [
                'fr' => "Le service de rendez-vous en ligne est ouvert. Choisissez votre service, une date et un créneau disponible : une référence unique vous est envoyée par email.\n\nPensez à préparer les documents requis, listés sur la page de chaque service.",
                'en' => "The online appointment service is now open. Choose your service, a date and an available slot: a unique reference is sent to you by email.\n\nRemember to prepare the required documents listed on each service page.",
                'ar' => "خدمة حجز المواعيد عبر الإنترنت متاحة الآن. اختر الخدمة والتاريخ وموعدًا متاحًا، وسيصلك مرجع فريد عبر البريد الإلكتروني.\n\nتذكّر تحضير الوثائق المطلوبة المذكورة في صفحة كل خدمة.",
            ],
        ],
        [
            'slug' => 'journee-communaute', 'days_ago' => 21,
            'title' => ['fr' => 'Journée de rencontre avec la communauté', 'en' => 'Community meeting day', 'ar' => 'يوم لقاء مع الجالية'],
            'excerpt' => [
                'fr' => 'Une journée d’échanges et d’informations ouverte à tous les ressortissants.',
                'en' => 'A day of discussion and information open to all nationals.',
                'ar' => 'يوم للنقاش والإعلام مفتوح لجميع المواطنين.',
            ],
            'body' => [
                'fr' => "Le consulat organise une journée de rencontre avec la communauté : présentation des services, échanges avec les agents et permanence d'information.\n\nL'entrée est libre. Les détails (date et lieu) seront précisés prochainement.",
                'en' => "The consulate is organising a community meeting day: presentation of services, discussions with staff and an information desk.\n\nEntry is free. Details (date and venue) will be announced soon.",
                'ar' => "تنظم القنصلية يومًا للقاء الجالية: عرض للخدمات ونقاش مع الموظفين ومكتب للإعلام.\n\nالدخول مجاني، وسيُعلن قريبًا عن التفاصيل (التاريخ والمكان).",
            ],
        ],
    ],

    // Créneaux hebdomadaires : [jours (1=lundi…5=vendredi), début, fin, capacité, service_slug|null]
    'slots' => [
        [[1, 2, 3, 4, 5], '09:00', '10:00', 3, null],
        [[1, 2, 3, 4, 5], '10:00', '11:00', 3, null],
        [[1, 2, 3, 4, 5], '11:00', '12:00', 2, null],
        [[1, 2, 3, 4, 5], '13:00', '14:00', 2, null],
        [[2, 4], '14:00', '14:30', 5, 'legalisation-documents'],
    ],

    'users' => [
        'admin' => ['name' => 'Admin Consulat Tchad', 'email' => 'admin.tchad@demo.test'],
        'staff' => ['name' => 'Agent Consulat Tchad', 'email' => 'staff.tchad@demo.test'],
    ],

    'sample_messages' => [
        ['name' => 'Awa Test', 'email' => 'awa.test@example.test', 'subject' => 'Renouvellement de passeport', 'message' => "Bonjour, je souhaite savoir si je peux renouveler mon passeport sans présenter l'ancien (perdu)."],
        ['name' => 'Ibrahim Exemple', 'email' => 'ibrahim.exemple@example.test', 'subject' => 'Horaires du samedi', 'message' => "Bonjour, le consulat est-il ouvert le samedi matin pour les légalisations ?"],
    ],
];
