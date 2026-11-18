import { usePage } from '@inertiajs/react';

export type Locale = 'en' | 'ar';

export interface SharedProps {
    locale: Locale;
    dir: 'ltr' | 'rtl';
    canonical: string;
    ui: Record<string, string>;
    alternate: { locale: Locale; url: string; label: string };
    legal?: { slug: string; title: string }[];
    site: { name: string; contact: { email?: string | null; phone?: string | null; whatsapp?: string | null }; support?: boolean };
    [key: string]: unknown;
}

export interface ServiceItem { id: number; slug: string; icon: string | null; name: string; summary: string | null }
export interface ProjectStat { value: string; label: string }
export interface ProjectItem {
    id: number; slug: string; title: string; summary: string | null; category: string | null; cover: string | null;
    url: string | null; host: string | null; tech: string[];
    /** [highlight, base] colours taken from the client's own identity. */
    accent: [string, string] | null;
    logo: string | null; logo_light: boolean; desktop: string | null; mobile: string | null; stats: ProjectStat[];
}
export interface ProjectDetail extends ProjectItem {
    year: number | null; role: string[]; paragraphs: string[];
    features: { icon: string; title: string; text: string }[];
    gallery: { src: string; alt: string | null; mobile: boolean }[];
}

/** Locale-aware helpers: t() reads UI strings from lang/{locale}/ui.php, url() prefixes the locale. */
export function useSite() {
    const props = usePage().props as unknown as SharedProps;
    const t = (key: string) => props.ui[key] ?? key;
    const url = (path = '') => `/${props.locale}${path}`;

    return { ...props, t, url };
}
