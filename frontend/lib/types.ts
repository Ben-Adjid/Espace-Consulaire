// Formes des réponses de l'API Laravel. Toutes les réponses simples sont enveloppées dans `data`.

export type Lang = 'fr' | 'en' | 'ar';

export interface Theme {
  primary: string;
  secondary: string;
  accent: string;
}

export interface Consulate {
  slug: string;
  name: string;
  represented_country: string;
  host_country: string;
  /** Pays de résidence dont s'occupe ce consulat (rattachement par résidence). */
  covered_countries?: string[];
  consul_name: string | null;
  hero_title: string;
  hero_subtitle: string | null;
  consul_message: string | null;
  mission: string | null;
  address: string | null;
  opening_hours: string | null;
  phone: string | null;
  email: string | null;
  whatsapp: string | null;
  latitude: number | null;
  longitude: number | null;
  logo_url: string | null;
  initials: string;
  languages: Lang[];
  theme: Theme;
}

export interface Service {
  slug: string;
  name: string;
  summary: string | null;
  description: string | null;
  documents: string[];
  /** Pièces avec leurs conditions : adult, minor, first, renewal (vide = pour tous). */
  document_items?: DocumentItem[];
  fee: string | null;
  processing_time: string | null;
  icon: string;
  allow_appointment: boolean;
}

export interface DocumentItem {
  label: string;
  tags: string[];
}

export interface Association {
  name: string;
  acronym: string | null;
  description: string | null;
  email: string | null;
  phone: string | null;
  is_recognized: boolean;
  officers: { role: string; role_label: string; full_name: string; school: string | null; mandate_start: string | null; mandate_end: string | null }[];
  activities: { title: string; description: string | null; starts_on: string; location: string | null }[];
  activities_page: { page: number; last_page: number; total: number };
  archives: { person_name: string; title: string | null; summary: string | null }[];
}

export interface CitizenReportView {
  id: number;
  category: string;
  category_label: string;
  subject: string;
  status: string;
  status_label: string;
  response: string | null;
  created_at: string;
}

export interface NewsItem {
  slug: string;
  title: string;
  excerpt: string | null;
  body: string | null;
  image_url: string | null;
  published_at: string | null;
}

export interface Faq {
  id: number;
  question: string;
  answer: string;
}

export interface PageContent {
  slug: string;
  title: string;
  body: string | null;
}

export interface Slot {
  id: number;
  start_time: string;
  end_time: string;
  remaining: number;
}

export interface AppointmentView {
  reference: string;
  status: 'pending' | 'confirmed' | 'cancelled' | 'completed';
  status_label: string;
  date: string;
  start_time: string;
  end_time: string;
  service: string | null;
  service_slug: string | null;
  full_name: string;
  cancellable: boolean;
  cancelled_at: string | null;
  /** Ticket de file d'attente (ex. A-007), attribué à l'arrivée au guichet. */
  ticket?: string | null;
  checked_in_at?: string | null;
  /** Suivi du dossier après le dépôt. */
  case_status?: 'submitted' | 'processing' | 'ready' | 'collected' | 'rejected' | null;
  case_status_label?: string | null;
  /** Dossier en ligne de la même démarche : statut seulement. */
  pre_request?: { id: number; status: string; status_label: string } | null;
  case_note?: string | null;
  case_updated_at?: string | null;
  on_behalf_name?: string | null;
}

export interface SearchResult {
  type: 'service' | 'news' | 'faq';
  title: string;
  slug: string | null;
  excerpt: string | null;
}

export interface Paginated<T> {
  data: T[];
  links: { next: string | null; prev: string | null };
  meta: { current_page: number; last_page: number };
}
