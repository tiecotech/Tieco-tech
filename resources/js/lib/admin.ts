import type { Currency } from '@/components/builder/types';
import { usePage } from '@inertiajs/react';

export interface AdminNotification {
    id: string;
    data: Record<string, string | number | null>;
    at: string | null;
}

export interface AdminProps {
    locale: 'en' | 'ar';
    dir: 'ltr' | 'rtl';
    ui: Record<string, string>;
    currency: Currency;
    admin: {
        user: { name: string; email: string; role: string | null };
        can: string[];
        unread: number;
        notifications: AdminNotification[];
    };
    [key: string]: unknown;
}

/** Locale-aware helpers for the admin area (no /{locale} prefix; language comes from the user's profile). */
export function useAdmin() {
    const props = usePage().props as unknown as AdminProps;
    const t = (key: string) => props.ui[key] ?? key;
    const url = (path = '') => `/admin${path}`;
    const can = (permission: string) => props.admin.can.includes('*') || props.admin.can.includes(permission);

    return { ...props, t, url, can };
}

export function names(value: Record<string, string> | string | null | undefined, locale: string): string {
    if (!value) return '';
    if (typeof value === 'string') return value;

    return value[locale] ?? value.en ?? Object.values(value)[0] ?? '';
}
