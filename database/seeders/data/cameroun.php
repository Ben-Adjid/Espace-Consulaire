<?php

/* Consulat du Cameroun au Sénégal — DONNÉES FICTIVES (démo non officielle). */

return [
    'vars' => [
        'c' => ['fr' => 'Cameroun', 'en' => 'Cameroon', 'ar' => 'الكاميرون'],
        'a' => ['fr' => 'camerounais', 'en' => 'Cameroonian', 'ar' => 'الكاميروني'],
        'h' => ['fr' => 'Sénégal', 'en' => 'Senegal', 'ar' => 'السنغال'],
        'n' => ['fr' => 'Consulat du Cameroun au Sénégal', 'en' => 'Consulate of Cameroon in Senegal', 'ar' => 'قنصلية الكاميرون في السنغال'],
    ],

    'consulate' => [
        'slug' => 'cameroun-senegal',
        'ref_prefix' => 'CMR-SN',
        'primary_color' => '#007A5E',   // vert
        'secondary_color' => '#FCD116', // jaune
        'accent_color' => '#CE1126',    // rouge
        'latitude' => 14.7106,
        'longitude' => -17.4674,
        'phone' => '+221 33 800 00 02',
        'email' => 'contact@cameroun-senegal.demo.test',
        'whatsapp' => '+221 77 000 00 02',
        'consul_name' => 'Mme Ngono Exemple (personnage fictif)',
        'languages' => ['fr', 'en'],
        'name' => ['fr' => 'Consulat du Cameroun au Sénégal', 'en' => 'Consulate of Cameroon in Senegal', 'ar' => 'قنصلية الكاميرون في السنغال'],
        'represented_country' => ['fr' => 'Cameroun', 'en' => 'Cameroon', 'ar' => 'الكاميرون'],
        'host_country' => ['fr' => 'Sénégal', 'en' => 'Senegal', 'ar' => 'السنغال'],
        'address' => [
            'fr' => "45 avenue Modèle, Mermoz\nDakar, Sénégal (adresse fictive)",
            'en' => "45 Model Avenue, Mermoz\nDakar, Senegal (fictitious address)",
            'ar' => "45 شارع النموذج، ميرموز\nداكار، السنغال (عنوان وهمي)",
        ],
        'opening_hours' => [
            'fr' => "Lundi – Vendredi : 08h30 – 14h30\nSamedi et dimanche : fermé\nPause : 12h00 – 12h30",
            'en' => "Monday – Friday: 8:30 am – 2:30 pm\nSaturday and Sunday: closed\nBreak: 12:00 – 12:30 pm",
            'ar' => "الاثنين – الجمعة: 08:30 – 14:30\nالسبت والأحد: مغلق\nاستراحة: 12:00 – 12:30",
        ],
        'hero_title' => [
            'fr' => 'Consulat du Cameroun au Sénégal',
            'en' => 'Consulate of Cameroon in Senegal',
            'ar' => 'قنصلية الكاميرون في السنغال',
        ],
        'hero_subtitle' => [
            'fr' => 'Une plateforme simple pour vous informer, préparer vos dossiers et prendre rendez-vous en ligne.',
            'en' => 'A simple platform to get informed, prepare your files and book appointments online.',
            'ar' => 'منصة بسيطة للاطلاع على المعلومات وإعداد الملفات وحجز المواعيد عبر الإنترنت.',
        ],
        'consul_message' => [
            'fr' => "Chers compatriotes, chers partenaires,\n\nLa digitalisation de nos services répond à une attente forte de la communauté camerounaise du Sénégal : moins d'attente, plus de transparence.\n\nJe remercie nos équipes pour leur engagement quotidien à votre service.",
            'en' => "Dear compatriots, dear partners,\n\nThe digitalisation of our services meets a strong expectation of the Cameroonian community in Senegal: less waiting, more transparency.\n\nI thank our teams for their daily commitment to serving you.",
            'ar' => "أعزائي المواطنين وشركائنا،\n\nتستجيب رقمنة خدماتنا لتطلعات قوية لدى الجالية الكاميرونية في السنغال: انتظار أقل وشفافية أكبر.\n\nأشكر فرقنا على التزامها اليومي بخدمتكم.",
        ],
        'mission' => [
            'fr' => "Accompagner les ressortissants camerounais au Sénégal, délivrer les actes et documents consulaires et promouvoir les liens entre le Cameroun et le Sénégal.",
            'en' => "Support Cameroonian nationals in Senegal, issue consular records and documents, and promote ties between Cameroon and Senegal.",
            'ar' => "مرافقة المواطنين الكاميرونيين في السنغال، وإصدار الوثائق والسجلات القنصلية، وتعزيز الروابط بين الكاميرون والسنغال.",
        ],
    ],

    'fees' => [
        'passeport' => '75 000 FCFA',
        'carte-consulaire' => '20 000 FCFA',
        'visa' => '55 000 FCFA',
        'legalisation-documents' => '6 000 FCFA / doc.',
        'actes-etat-civil' => '12 000 FCFA',
        'procuration' => '15 000 FCFA',
        'laissez-passer' => '30 000 FCFA',
    ],

    'news' => [
        [
            'slug' => 'ouverture-portail-numerique', 'days_ago' => 2,
            'title' => ['fr' => 'Ouverture du portail numérique du consulat', 'en' => "The consulate's digital portal is open", 'ar' => 'افتتاح البوابة الرقمية للقنصلية'],
            'excerpt' => [
                'fr' => 'Informations, documents requis et rendez-vous : tout est en ligne.',
                'en' => 'Information, required documents and appointments: everything is online.',
                'ar' => 'المعلومات والوثائق المطلوبة والمواعيد: كل شيء عبر الإنترنت.',
            ],
            'body' => [
                'fr' => "Le consulat met en service son portail numérique. Vous pouvez désormais consulter les services, vérifier la liste des documents à fournir et réserver votre rendez-vous.\n\nCe portail est une démonstration : les informations affichées sont fictives.",
                'en' => "The consulate is launching its digital portal. You can now browse services, check the list of documents to provide and book your appointment.\n\nThis portal is a demonstration: the information shown is fictitious.",
                'ar' => "تطلق القنصلية بوابتها الرقمية. يمكنكم الآن الاطلاع على الخدمات ومراجعة قائمة الوثائق المطلوبة وحجز موعدكم.\n\nهذه البوابة عرض تجريبي، والمعلومات المعروضة وهمية.",
            ],
        ],
        [
            'slug' => 'fermeture-jour-ferie', 'days_ago' => 8,
            'title' => ['fr' => 'Fermeture exceptionnelle pour jour férié', 'en' => 'Exceptional closure for a public holiday', 'ar' => 'إغلاق استثنائي بمناسبة عطلة رسمية'],
            'excerpt' => [
                'fr' => 'Le consulat sera fermé à l’occasion d’une fête nationale.',
                'en' => 'The consulate will be closed for a national holiday.',
                'ar' => 'ستكون القنصلية مغلقة بمناسبة عيد وطني.',
            ],
            'body' => [
                'fr' => "Le consulat sera exceptionnellement fermé à l'occasion d'une fête nationale. Les rendez-vous prévus ce jour-là seront reportés ; vous serez contactés par téléphone ou email.\n\nMerci de votre compréhension.",
                'en' => "The consulate will be exceptionally closed for a national holiday. Appointments scheduled that day will be rescheduled; you will be contacted by phone or email.\n\nThank you for your understanding.",
                'ar' => "ستكون القنصلية مغلقة استثنائيًا بمناسبة عيد وطني. ستُؤجَّل المواعيد المقررة في ذلك اليوم وسيتم الاتصال بكم هاتفيًا أو عبر البريد الإلكتروني.\n\nشكرًا على تفهمكم.",
            ],
        ],
        [
            'slug' => 'rappel-documents-passeport', 'days_ago' => 19,
            'title' => ['fr' => 'Rappel : documents pour une demande de passeport', 'en' => 'Reminder: documents for a passport application', 'ar' => 'تذكير: وثائق طلب جواز السفر'],
            'excerpt' => [
                'fr' => 'Évitez un second déplacement en vérifiant votre dossier avant de venir.',
                'en' => 'Avoid a second trip by checking your file before coming.',
                'ar' => 'تجنّب زيارة ثانية بالتحقق من ملفك قبل الحضور.',
            ],
            'body' => [
                'fr' => "Pour éviter un second déplacement, vérifiez la checklist des documents sur la page « Passeport » avant votre rendez-vous.\n\nLes dossiers incomplets ne peuvent pas être instruits.",
                'en' => "To avoid a second trip, check the document checklist on the “Passport” page before your appointment.\n\nIncomplete files cannot be processed.",
                'ar' => "لتجنّب زيارة ثانية، راجعوا قائمة الوثائق في صفحة «جواز السفر» قبل موعدكم.\n\nلا يمكن دراسة الملفات غير المكتملة.",
            ],
        ],
    ],

    'slots' => [
        [[1, 2, 3, 4, 5], '08:30', '09:30', 4, null],
        [[1, 2, 3, 4, 5], '09:30', '10:30', 4, null],
        [[1, 2, 3, 4, 5], '10:30', '11:30', 3, null],
        [[1, 2, 3, 4, 5], '12:30', '13:30', 2, null],
        [[3], '13:30', '14:15', 6, 'legalisation-documents'],
    ],

    'users' => [
        'admin' => ['name' => 'Admin Consulat Cameroun', 'email' => 'admin.cameroun@demo.test'],
        'staff' => ['name' => 'Agent Consulat Cameroun', 'email' => 'staff.cameroun@demo.test'],
    ],

    'sample_messages' => [
        ['name' => 'Paul Exemple', 'email' => 'paul.exemple@example.test', 'subject' => 'Carte consulaire', 'message' => "Bonjour, quels documents faut-il pour une première carte consulaire pour un étudiant ?"],
    ],
];
