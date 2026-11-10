# PLAN — Plateforme multi-consulats (démo non officielle)

## 1. Architecture

Une seule base de code Laravel 12 / une seule base MySQL `consulat_platform`, plusieurs consulats (multi-tenant « par colonne »).

```
Visiteur ──► /{consulate_slug}/…  ──► middleware SetCurrentConsulate
                                       ├─ charge le consulat (actif) depuis le slug
                                       ├─ fixe la langue (session / ?lang=) + dir RTL
                                       └─ partage le thème (variables CSS) aux vues
Admin    ──► /admin (Filament v3) ──► Global scope `BelongsToConsulate`
                                       ├─ super_admin : voit tout
                                       └─ consulate_admin / staff : uniquement leur consulat
```

| Couche | Contenu |
|---|---|
| `app/Models` | Consulate, Service, News, Faq, Page, TimeSlot, Appointment, ContactMessage, User |
| `app/Models/Concerns` | `BelongsToConsulate` (global scope + auto-remplissage de `consulate_id`) |
| `app/Services` | `AvailabilityService` (créneaux/capacité), `ConsulateContext` (consulat courant) |
| `app/Actions` | `CreateAppointment` (transaction + verrou anti double réservation) |
| `app/Policies` | une policy par ressource, vérifie rôle/permission + appartenance au consulat |
| `app/Http` | Controllers publics, Form Requests, Middleware |
| `app/Livewire` | `BookingForm` (prise de rendez-vous en 3 étapes) |
| `app/Filament` | Resources, Widgets du dashboard |
| `app/Mail` | `AppointmentConfirmation` (traduit dans la langue du visiteur) |
| `lang/` | `fr.json`, `en.json`, `ar.json` + validation |

## 2. Schéma de base de données

Toutes les tables de contenu portent `consulate_id` (FK → `consulates`, `cascadeOnDelete`, indexée).
Les colonnes marquées *(i18n)* sont des JSON `{fr,en,ar}` gérés par `spatie/laravel-translatable`.

- **consulates** : slug (unique), ref_prefix, name *(i18n)*, represented_country *(i18n)*, host_country *(i18n)*, logo, primary_color, secondary_color, accent_color, address *(i18n)*, latitude, longitude, phone, email, whatsapp, opening_hours *(i18n)*, languages (json), consul_name, consul_message *(i18n)*, mission *(i18n)*, hero_title *(i18n)*, hero_subtitle *(i18n)*, is_active
- **users** : + consulate_id (nullable, FK), is_active
- **roles / permissions…** : tables `spatie/laravel-permission`
- **services** : consulate_id, slug (unique par consulat), name, summary, description, required_documents *(i18n, 1 item/ligne)*, fee, processing_time *(i18n)*, icon, sort_order, is_active, allow_appointment
- **news** : consulate_id, slug, title, excerpt, body *(i18n)*, image, published_at, is_published
- **faqs** : consulate_id, question, answer *(i18n)*, sort_order, is_published
- **pages** : consulate_id, slug (unique par consulat), title, body *(i18n)*, is_published
- **time_slots** : consulate_id, service_id (nullable = tous services), weekday (0‑6), start_time, end_time, capacity, is_active
- **appointments** : consulate_id, service_id, time_slot_id, reference (unique), appointment_date, start_time, end_time, full_name, email, phone, notes, status (pending/confirmed/cancelled/completed), locale, confirmed_at ; index (time_slot_id, appointment_date, status)
- **contact_messages** : consulate_id, name, email, phone, subject, message, is_read, ip_address

## 3. Étapes (un commit par étape)

1. **setup** — Laravel 12, Filament 3, Spatie, Tailwind/Vite, .env, docs
2. **migrations** — toutes les tables, FK et index
3. **modèles** — modèles, traits, enums, policies, rôles/permissions
4. **admin** — panneau Filament, resources, dashboard
5. **site public** — layout, accueil, services, actualités, FAQ, contact, pages
6. **rendez-vous** — disponibilité, booking Livewire, email, anti double booking
7. **i18n/RTL** — FR/EN/AR, RTL, SEO (meta, OG, sitemap)
8. **seeders** — Tchad & Cameroun + comptes de démo
9. **tests** — multi-tenant, rendez-vous, contact, pages publiques
10. **polish** — README, V2 (structure), vérification finale
