import { fmt, formatMoney } from '@/lib/money';
import { useSite } from '@/lib/tieco';
import type { BType, Currency } from './types';

const ICONS: Record<string, string> = {
    web: 'M5 4h14a2 2 0 0 1 2 2v9a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2zM8 21h8M12 17v4',
    store: 'M3 4h2l2.4 10.2a2 2 0 0 0 2 1.6h7.2a2 2 0 0 0 2-1.5L20 8H6M10 20.5h.01M17 20.5h.01',
    code: 'M8 8l-4 4 4 4M16 8l4 4-4 4M13 6l-2 12',
    mobile: 'M9 2h6a2 2 0 0 1 2 2v16a2 2 0 0 1-2 2H9a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2zM11 18h2',
    brand: 'M4 20l4-1 11-11-3-3L5 16l-1 4zM14 7l3 3',
    ads: 'M4 10v4l12 5V5L4 10zM8 15l1 5',
    camera: 'M5 7h14a2 2 0 0 1 2 2v9a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V9a2 2 0 0 1 2-2zM8 7l1.5-3h5L16 7M12 10a3.5 3.5 0 1 0 0 7 3.5 3.5 0 0 0 0-7z',
    calendar: 'M5 5h14a2 2 0 0 1 2 2v12a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V7a2 2 0 0 1 2-2zM8 3v4M16 3v4M3 11h18',
    blog: 'M5 4h14v16H5zM8 9h8M8 13h8M8 17h5',
    bundle: 'M12 3l9 5-9 5-9-5 9-5zM3 13l9 5 9-5M3 17.5l9 5 9-5',
};

function iconFor(key: string): string {
    if (key === 'online_store') return ICONS.store;
    if (key === 'blog') return ICONS.blog;
    if (key === 'booking_system') return ICONS.calendar;
    if (key === 'custom_software') return ICONS.code;
    if (key === 'mobile_app') return ICONS.mobile;
    if (key === 'branding') return ICONS.brand;
    if (key === 'ads_marketing') return ICONS.ads;
    if (key === 'photo_video') return ICONS.camera;
    if (key === 'bundle') return ICONS.bundle;

    return ICONS.web;
}

interface Props {
    types: BType[];
    selectedId: number | null;
    onSelect: (type: BType) => void;
    currency: Currency;
}

export function TypePicker({ types, selectedId, onSelect, currency }: Props) {
    const { t, locale } = useSite();

    return (
        <div className="tt-b-types" role="radiogroup" aria-label={t('b.type.title')}>
            {types.map((type) => {
                const selected = type.id === selectedId;

                return (
                    <button
                        type="button"
                        role="radio"
                        aria-checked={selected}
                        key={type.id}
                        className={`tt-b-type${selected ? ' is-selected' : ''}`}
                        onClick={() => onSelect(type)}
                    >
                        <svg viewBox="0 0 24 24" aria-hidden="true"><path d={iconFor(type.key)} /></svg>
                        <b>{type.name}</b>
                        <small>
                            {type.base_price_minor === null
                                ? t('b.type.onrequest')
                                : fmt(t('b.type.from'), { price: formatMoney(type.base_price_minor, currency, locale) })}
                        </small>
                    </button>
                );
            })}
        </div>
    );
}
