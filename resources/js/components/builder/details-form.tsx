import { fmt, formatMoney } from '@/lib/money';
import { useSite } from '@/lib/tieco';
import { useRef, useState, type DragEvent, type ReactNode } from 'react';
import type { BDiscount, Currency, FormState, Limits } from './types';

interface Props {
    state: FormState;
    patch: (p: Partial<FormState>) => void;
    errors: Record<string, string>;
    discounts: BDiscount[];
    limits: Limits;
    currency: Currency;
}

function Field({ id, label, error, hint, optional, children }: { id: string; label: string; error?: string; hint?: string; optional?: boolean; children: ReactNode }) {
    const { t } = useSite();

    return (
        <div className={`tt-b-field${error ? ' has-error' : ''}`}>
            <label htmlFor={id}>
                {label}
                {optional && <em> ({t('b.optional')})</em>}
            </label>
            {children}
            {hint && !error && <small>{hint}</small>}
            {error && <small className="err" role="alert">{error}</small>}
        </div>
    );
}

function discountText(d: BDiscount, currency: Currency, locale: string): string {
    const value = d.type === 'percent_bp' ? `${d.value / 100}%` : formatMoney(d.value, currency, locale);
    const cap = d.type === 'percent_bp' && d.max_discount_minor !== null ? ` (max ${formatMoney(d.max_discount_minor, currency, locale)})` : '';

    return `${value}${cap}`;
}

export function DetailsForm({ state, patch, errors, discounts, limits, currency }: Props) {
    const { t, locale } = useSite();
    const [fileError, setFileError] = useState('');
    const [over, setOver] = useState(false);
    const input = useRef<HTMLInputElement>(null);

    const addFiles = (list: FileList | File[]) => {
        const incoming = Array.from(list);
        const next = [...state.files];
        let problem = '';
        for (const f of incoming) {
            const ext = f.name.split('.').pop()?.toLowerCase() ?? '';
            if (!limits.mimes.includes(ext)) problem = t('b.err.file_type');
            else if (f.size > limits.max_kb * 1024) problem = t('b.err.file_size');
            else if (next.length >= limits.max_files) problem = t('b.err.files_count');
            else next.push(f);
        }
        setFileError(problem);
        patch({ files: next });
        if (input.current) input.current.value = '';
    };

    const onDrop = (e: DragEvent<HTMLDivElement>) => {
        e.preventDefault();
        setOver(false);
        addFiles(e.dataTransfer.files);
    };

    const serverFileError = Object.entries(errors).find(([k]) => k.startsWith('attachments'))?.[1];

    return (
        <div className="tt-b-form">
            <div className="tt-b-two">
                <Field id="f-name" label={t('b.details.name')} error={errors.customer_name}>
                    <input id="f-name" className="tt-b-in" value={state.customer_name} autoComplete="name" onChange={(e) => patch({ customer_name: e.target.value })} />
                </Field>
                <Field id="f-country" label={t('b.details.country')} optional error={errors.country}>
                    <input id="f-country" className="tt-b-in" value={state.country} autoComplete="country-name" onChange={(e) => patch({ country: e.target.value })} />
                </Field>
            </div>
            <div className="tt-b-two">
                <Field id="f-phone" label={t('b.details.phone')} hint={t('b.details.phone_hint')} error={errors.phone}>
                    <input id="f-phone" className="tt-b-in" type="tel" dir="ltr" placeholder="+963..." value={state.phone} autoComplete="tel" onChange={(e) => patch({ phone: e.target.value })} />
                </Field>
                <Field id="f-email" label={t('b.details.email')} optional error={errors.email}>
                    <input id="f-email" className="tt-b-in" type="email" dir="ltr" value={state.email} autoComplete="email" onChange={(e) => patch({ email: e.target.value })} />
                </Field>
            </div>
            <label className="tt-b-check-row">
                <input type="checkbox" checked={state.whatsapp_opt_in} onChange={(e) => patch({ whatsapp_opt_in: e.target.checked })} />
                <span>{t('b.details.whatsapp_optin')}</span>
            </label>

            <fieldset className="tt-b-group">
                <legend>{t('b.details.contact')}</legend>
                <div className="tt-b-radios">
                    {(['whatsapp', 'phone', 'email'] as const).map((c) => (
                        <label key={c} className={`tt-b-radio${state.preferred_contact === c ? ' is-on' : ''}`}>
                            <input type="radio" name="contact" checked={state.preferred_contact === c} onChange={() => patch({ preferred_contact: c })} />
                            {t(`b.details.contact.${c}`)}
                        </label>
                    ))}
                </div>
            </fieldset>

            <Field id="f-desc" label={t('b.details.description')} error={errors.description}>
                <textarea id="f-desc" className="tt-b-in" rows={5} placeholder={t('b.details.description_ph')} value={state.description} onChange={(e) => patch({ description: e.target.value })} />
            </Field>
            <div className="tt-b-two">
                <Field id="f-goal" label={t('b.details.goal')} optional error={errors.goal}>
                    <textarea id="f-goal" className="tt-b-in" rows={3} value={state.goal} onChange={(e) => patch({ goal: e.target.value })} />
                </Field>
                <Field id="f-aud" label={t('b.details.audience')} optional error={errors.audience}>
                    <textarea id="f-aud" className="tt-b-in" rows={3} value={state.audience} onChange={(e) => patch({ audience: e.target.value })} />
                </Field>
            </div>
            <div className="tt-b-two">
                <Field id="f-date" label={t('b.details.date')} optional error={errors.expected_date}>
                    <input id="f-date" className="tt-b-in" type="date" value={state.expected_date} onChange={(e) => patch({ expected_date: e.target.value })} />
                </Field>
                <Field id="f-budget" label={`${t('b.details.budget')} (${currency.code})`} optional hint={t('b.details.budget_hint')} error={errors.budget}>
                    <input id="f-budget" className="tt-b-in" type="number" min={0} inputMode="decimal" value={state.budget} onChange={(e) => patch({ budget: e.target.value })} />
                </Field>
            </div>

            <fieldset className="tt-b-group">
                <legend>{t('b.details.files')} <em>({t('b.optional')})</em></legend>
                <div
                    className={`tt-b-drop${over ? ' is-over' : ''}`}
                    onDragOver={(e) => { e.preventDefault(); setOver(true); }}
                    onDragLeave={() => setOver(false)}
                    onDrop={onDrop}
                >
                    <button type="button" onClick={() => input.current?.click()}>{t('b.details.files_drop')}</button>
                    <small>{fmt(t('b.details.files_hint'), { n: limits.max_files, mb: Math.round(limits.max_kb / 1024), types: limits.mimes.join(', ') })}</small>
                    <input ref={input} type="file" multiple hidden accept={limits.mimes.map((m) => `.${m}`).join(',')} onChange={(e) => e.target.files && addFiles(e.target.files)} />
                </div>
                {(fileError || serverFileError) && <small className="err" role="alert">{fileError || serverFileError}</small>}
                {state.files.length > 0 && (
                    <ul className="tt-b-files">
                        {state.files.map((f, i) => (
                            <li key={`${f.name}-${i}`}>
                                <span>{f.name} <em>({Math.max(1, Math.round(f.size / 1024))} KB)</em></span>
                                <button type="button" onClick={() => patch({ files: state.files.filter((_, j) => j !== i) })}>{t('b.details.files_remove')}</button>
                            </li>
                        ))}
                    </ul>
                )}
            </fieldset>

            {discounts.length > 0 && (
                <div className="tt-b-disc">
                    <b>{t('b.details.discounts')}</b>
                    <ul>
                        {discounts.map((d) => (
                            <li key={d.code}>
                                <span className="tt-b-disc-val"><bdi>−{discountText(d, currency, locale)}</bdi></span>
                                <span>{d.name}{d.description ? `: ${d.description}` : ''}</span>
                            </li>
                        ))}
                    </ul>
                </div>
            )}

            {/* Honeypot: hidden from people, tempting to bots. */}
            <div className="tt-b-hp" aria-hidden="true">
                <label htmlFor="f-website">Website</label>
                <input id="f-website" tabIndex={-1} autoComplete="off" value={state.website} onChange={(e) => patch({ website: e.target.value })} />
            </div>
        </div>
    );
}
