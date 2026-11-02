import { usePage } from '@inertiajs/react';

export type Locale = 'en' | 'ar';

export interface SharedProps {
    locale: Locale;
    dir: 'ltr' | 'rtl';
    canonical: string;
    ui: Record<string, string>;
    alternate: { locale: Locale; url: string; label: string };
    legal?: { slug: string; title: string }[];
    site: { name: string; contact: { email?: string | null; phone?: string | null; whatsapp?: string | null } };
    [key: string]: unknown;
}

export interface ServiceItem { id: number; slug: string; icon: string | null; name: string; summary: string | null }
export interface ProjectItem { id: number; slug: string; title: string; summary: string | null; category: string | null; cover: string | null }

/** Locale-aware helpers: t() reads UI strings from lang/{locale}/ui.php, url() prefixes the locale. */
export function useSite() {
    const props = usePage().props as unknown as SharedProps;
    const t = (key: string) => props.ui[key] ?? key;
    const url = (path = '') => `/${props.locale}${path}`;

    return { ...props, t, url };
}
