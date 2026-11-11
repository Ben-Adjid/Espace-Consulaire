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
  fee: string | null;
  processing_time: string | null;
  icon: string;
  allow_appointment: boolean;
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
  full_name: string;
  cancellable: boolean;
  cancelled_at: string | null;
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
