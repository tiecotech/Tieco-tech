import type { Currency } from '@/components/builder/types';

/** Display only: amounts are integer minor units computed by the server. */
export function formatMoney(minor: number, currency: Currency, locale: string): string {
    const factor = Math.pow(10, currency.decimals);
    const value = minor / factor;
    const tag = locale === 'ar' ? 'ar-u-nu-latn' : 'en-US';

    return new Intl.NumberFormat(tag, {
        style: 'currency',
        currency: currency.code,
        minimumFractionDigits: Number.isInteger(value) ? 0 : currency.decimals,
        maximumFractionDigits: currency.decimals,
    }).format(value);
}

/** Replace {name} placeholders. */
export function fmt(template: string, vars: Record<string, string | number>): string {
    return template.replace(/\{(\w+)\}/g, (_, key: string) => String(vars[key] ?? ''));
}

/** Pick the active locale from a {en, ar} object. */
export function loc(names: Record<string, string>, locale: string): string {
    return names[locale] ?? names.en ?? Object.values(names)[0] ?? '';
}
