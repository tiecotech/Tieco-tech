import { formatMoney } from '@/lib/money';
import { useSite } from '@/lib/tieco';
import type { BOption, BType, Currency } from './types';

interface Props {
    type: BType;
    selected: Record<number, number>;
    onChange: (next: Record<number, number>) => void;
    currency: Currency;
}

function priceLabel(opt: BOption, currency: Currency, locale: string, t: (k: string) => string): string {
    if (opt.amount === null) return t('b.price.onrequest');
    if (opt.pricing_mode === 'percent_bp') return `+${opt.amount / 100}% ${t('b.price.percent')}`;
    const money = `+${formatMoney(opt.amount, currency, locale)}`;

    return opt.pricing_mode === 'per_page' ? `${money} ${t('b.price.per_page')}` : money;
}

/** Add-ons, platform choice and creative services, each with its price effect shown before choosing. */
export function OptionsPanel({ type, selected, onChange, currency }: Props) {
    const { t, locale } = useSite();

    const toggle = (opt: BOption) => {
        const next = { ...selected };
        if (next[opt.id]) {
            delete next[opt.id];
        } else {
            if (opt.exclusive_group) {
                type.options.filter((o) => o.exclusive_group === opt.exclusive_group).forEach((o) => delete next[o.id]);
            }
            next[opt.id] = 1;
        }
        onChange(next);
    };

    const setQty = (opt: BOption, qty: number) => {
        onChange({ ...selected, [opt.id]: Math.min(opt.max_quantity, Math.max(1, qty)) });
    };

    const groups: Array<['addon' | 'platform' | 'creative', string]> = [
        ['platform', 'b.custom.platform'],
        ['addon', 'b.custom.addons'],
        ['creative', 'b.custom.creative'],
    ];
    const present = groups.filter(([g]) => type.options.some((o) => o.group === g));

    if (present.length === 0) return <p className="tt-b-hint">{t('b.custom.none')}</p>;

    return (
        <>
            {present.map(([group, label]) => (
                <fieldset className="tt-b-group" key={group}>
                    <legend>{t(label)}</legend>
                    <div className="tt-b-opts">
                        {type.options.filter((o) => o.group === group).map((opt) => {
                            const qty = selected[opt.id] ?? 0;
                            const on = qty > 0;

                            return (
                                <div className={`tt-b-opt${on ? ' is-on' : ''}`} key={opt.id}>
                                    <button
                                        type="button"
                                        role={opt.exclusive_group ? 'radio' : 'checkbox'}
                                        aria-checked={on}
                                        className="tt-b-opt-main"
                                        onClick={() => toggle(opt)}
                                    >
                                        <span className="tt-b-check" aria-hidden="true">{on ? '✓' : ''}</span>
                                        <span className="tt-b-opt-text">
                                            <b>{opt.name}</b>
                                            {opt.description && <small>{opt.description}</small>}
                                        </span>
                                        <span className="tt-b-opt-price"><bdi>{priceLabel(opt, currency, locale, t)}</bdi></span>
                                    </button>
                                    {on && opt.max_quantity > 1 && (
                                        <div className="tt-b-qty">
                                            <span>{t('b.custom.qty')}</span>
                                            <button type="button" className="tt-b-step sm" aria-label="-" onClick={() => setQty(opt, qty - 1)} disabled={qty <= 1}>−</button>
                                            <b>{qty}</b>
                                            <button type="button" className="tt-b-step sm" aria-label="+" onClick={() => setQty(opt, qty + 1)} disabled={qty >= opt.max_quantity}>+</button>
                                        </div>
                                    )}
                                </div>
                            );
                        })}
                    </div>
                </fieldset>
            ))}
        </>
    );
}
