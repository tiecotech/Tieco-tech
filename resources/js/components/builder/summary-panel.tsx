import { formatMoney, loc } from '@/lib/money';
import { useSite } from '@/lib/tieco';
import { useEffect, useRef, useState } from 'react';
import type { BType, Currency, Estimate } from './types';

/** Smoothly tweens the displayed total so price changes feel alive. */
function useTween(target: number): number {
    const [shown, setShown] = useState(target);
    const from = useRef(target);

    useEffect(() => {
        if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
            setShown(target);
            from.current = target;

            return;
        }
        const start = performance.now();
        const origin = from.current;
        let frame = 0;
        const tick = (now: number) => {
            const p = Math.min(1, (now - start) / 450);
            const eased = 1 - Math.pow(1 - p, 3);
            const value = Math.round(origin + (target - origin) * eased);
            setShown(value);
            from.current = value;
            if (p < 1) frame = requestAnimationFrame(tick);
        };
        frame = requestAnimationFrame(tick);

        return () => cancelAnimationFrame(frame);
    }, [target]);

    return shown;
}

interface Props {
    type: BType | null;
    estimate: Estimate | null;
    loading: boolean;
    error: boolean;
    currency: Currency;
}

export function SummaryPanel({ type, estimate, loading, error, currency }: Props) {
    const { t, locale } = useSite();
    const total = useTween(estimate?.total_minor ?? 0);
    const money = (n: number) => formatMoney(n, currency, locale);

    return (
        <aside className="tt-b-sum" aria-live="polite">
            <h2>{t('b.sum.title')}</h2>
            {!type || !estimate ? (
                <p className="tt-b-hint">{error ? t('b.sum.error') : type ? t('b.sum.loading') : t('b.sum.empty')}</p>
            ) : (
                <>
                    <ul className="tt-b-lines">
                        {estimate.lines.map((line, i) => (
                            <li key={`${line.code}-${i}`}>
                                <span>
                                    {loc(line.name, locale)}
                                    {line.quantity > 1 && <em> × {line.quantity}</em>}
                                </span>
                                <b>{line.priced ? <bdi>{money(line.line_total_minor)}</bdi> : t('b.price.onrequest')}</b>
                            </li>
                        ))}
                    </ul>
                    {estimate.discounts.length > 0 && (
                        <ul className="tt-b-lines discounts">
                            {estimate.discounts.map((d) => (
                                <li key={d.code}>
                                    <span>{loc(d.name, locale)}</span>
                                    <b><bdi>−{money(d.amount_minor)}</bdi></b>
                                </li>
                            ))}
                        </ul>
                    )}
                    <div className="tt-b-total">
                        <span>{t('b.sum.total')}</span>
                        <strong className={loading ? 'is-loading' : ''}><bdi>{money(total)}</bdi></strong>
                    </div>
                    {estimate.has_unpriced_items && <p className="tt-b-warn">{t('b.sum.unpriced')}</p>}
                    {error && <p className="tt-b-warn">{t('b.sum.error')}</p>}
                </>
            )}
            <p className="tt-b-note">{t('b.sum.note')}</p>
        </aside>
    );
}
