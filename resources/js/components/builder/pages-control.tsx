import { fmt, formatMoney } from '@/lib/money';
import { useSite } from '@/lib/tieco';
import type { CSSProperties } from 'react';
import type { BType, Currency } from './types';

interface Props {
    type: BType;
    value: number;
    onChange: (pages: number) => void;
    currency: Currency;
}

/** Slider + number field + stepper buttons + quick picks, all bound to one value. */
export function PagesControl({ type, value, onChange, currency }: Props) {
    const { t, locale } = useSite();
    const { min_pages: min, max_pages: max } = type;
    const clamp = (n: number) => Math.min(max, Math.max(min, Math.round(Number.isFinite(n) ? n : min)));
    const pct = max > min ? ((value - min) / (max - min)) * 100 : 0;
    const quick = Array.from(new Set([1, 5, 10, 20].map(clamp)));

    return (
        <div className="tt-b-pages">
            <div className="tt-b-pages-row">
                <button type="button" className="tt-b-step" aria-label="-" onClick={() => onChange(clamp(value - 1))} disabled={value <= min}>−</button>
                <input
                    className="tt-b-num"
                    type="number"
                    inputMode="numeric"
                    min={min}
                    max={max}
                    value={value}
                    aria-label={t('b.custom.pages_label')}
                    onChange={(e) => onChange(clamp(Number(e.target.value)))}
                />
                <button type="button" className="tt-b-step" aria-label="+" onClick={() => onChange(clamp(value + 1))} disabled={value >= max}>+</button>
                <div className="tt-b-quick">
                    {quick.map((n) => (
                        <button type="button" key={n} className={`tt-b-chip${value === n ? ' is-on' : ''}`} onClick={() => onChange(n)}>{n}</button>
                    ))}
                </div>
            </div>
            <input
                className="tt-b-range"
                type="range"
                min={min}
                max={max}
                step={1}
                value={value}
                aria-label={t('b.custom.pages_label')}
                style={{ '--pct': `${pct}%` } as CSSProperties}
                onChange={(e) => onChange(clamp(Number(e.target.value)))}
            />
            <div className="tt-b-range-ends"><span>{min}</span><span>{max}</span></div>
            <p className="tt-b-hint">
                {type.extra_page_price_minor === null
                    ? fmt(t('b.custom.pages_included'), { n: type.included_pages })
                    : fmt(t('b.custom.pages_hint'), { n: type.included_pages, price: formatMoney(type.extra_page_price_minor, currency, locale) })}
            </p>
        </div>
    );
}
