import '../../css/builder.css';

import { DetailsForm } from '@/components/builder/details-form';
import { OptionsPanel } from '@/components/builder/options-panel';
import { PagesControl } from '@/components/builder/pages-control';
import { SummaryPanel } from '@/components/builder/summary-panel';
import { TypePicker } from '@/components/builder/type-picker';
import type { BDiscount, BType, Currency, FormState, Limits } from '@/components/builder/types';
import { useEstimate } from '@/components/builder/use-estimate';
import SiteLayout from '@/components/site/site-layout';
import { formatMoney, loc } from '@/lib/money';
import { useSite } from '@/lib/tieco';
import { router } from '@inertiajs/react';
import { useEffect, useRef, useState } from 'react';

interface Props {
    types: BType[];
    discounts: BDiscount[];
    currency: Currency;
    limits: Limits;
    preselect: string;
    presetPages?: number | null;
    presetOptions?: string[];
}

const STEPS = ['b.step.type', 'b.step.customize', 'b.step.details', 'b.step.review'];
const PHONE = /^\+[1-9]\d{7,14}$/;

function normalizePhone(raw: string): string | null {
    let p = raw.replace(/[\s().-]+/g, '');
    if (p.startsWith('00')) p = `+${p.slice(2)}`;

    return PHONE.test(p) ? p : null;
}

function startingPages(type: BType | null): number | null {
    if (!type || !type.supports_pages) return null;

    return Math.min(type.max_pages, Math.max(type.min_pages, type.included_pages));
}

function initialState(type: BType | null, presetPages?: number | null, presetOptions: string[] = []): FormState {
    const options: Record<number, number> = {};
    const taken = new Set<string>();
    type?.options.forEach((o) => {
        if (!presetOptions.includes(o.code)) return;
        if (o.exclusive_group) {
            if (taken.has(o.exclusive_group)) return;
            taken.add(o.exclusive_group);
        }
        options[o.id] = 1;
    });
    let pages = startingPages(type);
    if (type && pages !== null && presetPages) pages = Math.min(type.max_pages, Math.max(type.min_pages, presetPages));

    return {
        typeId: type?.id ?? null, pages, options,
        customer_name: '', phone: '', email: '', country: '', preferred_contact: 'whatsapp', whatsapp_opt_in: false,
        description: '', goal: '', audience: '', expected_date: '', budget: '', files: [], website: '',
    };
}

export default function Builder({ types, discounts, currency, limits, preselect, presetPages, presetOptions }: Props) {
    const { t, locale, url } = useSite();
    const preset = types.find((x) => x.key === preselect) ?? null;
    const [step, setStep] = useState(preset ? 1 : 0);
    const [reached, setReached] = useState(preset ? 1 : 0);
    const [state, setState] = useState<FormState>(() => initialState(preset, presetPages, presetOptions));
    const [errors, setErrors] = useState<Record<string, string>>({});
    const [sending, setSending] = useState(false);
    const top = useRef<HTMLDivElement>(null);
    const firstRender = useRef(true);

    const patch = (p: Partial<FormState>) => setState((s) => ({ ...s, ...p }));
    const type = types.find((x) => x.id === state.typeId) ?? null;

    const { estimate, error: estimateError, loading } = useEstimate(
        url('/start/estimate'),
        type
            ? {
                  project_type_id: type.id,
                  pages: type.supports_pages ? state.pages : null,
                  options: state.options,
                  phone: state.phone,
                  whatsapp_opt_in: state.whatsapp_opt_in,
                  attachment_count: state.files.length,
              }
            : null,
    );

    useEffect(() => {
        if (firstRender.current) {
            firstRender.current = false;

            return;
        }
        top.current?.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }, [step]);

    const selectType = (next: BType) => {
        setState((s) => ({ ...s, typeId: next.id, pages: startingPages(next), options: {} }));
        setErrors({});
        goTo(1);
    };

    const goTo = (n: number) => {
        setStep(n);
        setReached((r) => Math.max(r, n));
    };

    const validate = (s: number): Record<string, string> => {
        const e: Record<string, string> = {};
        if (s >= 0 && !type) e.type = t('b.err.type');
        if (s >= 1 && type?.kind === 'mobile' && !type.options.some((o) => o.group === 'platform' && state.options[o.id])) {
            e.options = t('b.custom.platform_required');
        }
        if (s >= 2) {
            if (!state.customer_name.trim()) e.customer_name = t('b.err.name');
            if (!state.phone.trim() && !state.email.trim()) {
                e.phone = t('b.err.contact');
                e.email = t('b.err.contact');
            }
            if (state.whatsapp_opt_in && !normalizePhone(state.phone)) e.phone = t('b.err.phone_format');
            if (state.description.trim().length < 10) e.description = t('b.err.description');
        }

        return e;
    };

    const next = () => {
        const e = validate(step);
        setErrors(e);
        if (Object.keys(e).length === 0) goTo(Math.min(3, step + 1));
    };

    const submit = () => {
        const e = validate(2);
        setErrors(e);
        if (!type || Object.keys(e).length > 0) {
            setStep(Object.keys(e).includes('options') ? 1 : 2);

            return;
        }
        setSending(true);
        router.post(
            url('/start'),
            {
                project_type_id: type.id,
                pages: type.supports_pages ? state.pages : null,
                options: state.options,
                customer_name: state.customer_name,
                phone: state.phone,
                email: state.email,
                country: state.country,
                preferred_contact: state.preferred_contact,
                whatsapp_opt_in: state.whatsapp_opt_in ? 1 : 0,
                description: state.description,
                goal: state.goal,
                audience: state.audience,
                expected_date: state.expected_date,
                budget: state.budget,
                attachments: state.files,
                website: state.website,
            },
            {
                forceFormData: true,
                preserveScroll: true,
                onError: (errs) => {
                    setErrors(errs as Record<string, string>);
                    const keys = Object.keys(errs);
                    setStep(keys.some((k) => k === 'project_type_id' || k === 'pages' || k.startsWith('options')) ? 1 : 2);
                },
                onFinish: () => setSending(false),
            },
        );
    };

    const money = (n: number) => formatMoney(n, currency, locale);
    const hasErrors = Object.keys(errors).length > 0;

    return (
        <SiteLayout title={t('b.title')}>
            <div className="tt-b" ref={top}>
                <div className="tt-w">
                    <header className="tt-b-head">
                        <h1 className="tt-h2">{t('b.title')}</h1>
                        <p className="tt-lead">{t('b.lead')}</p>
                    </header>

                    <ol className="tt-b-steps">
                        {STEPS.map((key, i) => (
                            <li key={key} className={i === step ? 'is-current' : i < step ? 'is-done' : ''}>
                                <button type="button" disabled={i > reached} onClick={() => setStep(i)}>
                                    <span>{i + 1}</span>
                                    {t(key)}
                                </button>
                            </li>
                        ))}
                    </ol>

                    <div className="tt-b-grid">
                        <div className="tt-b-main">
                            {hasErrors && step !== 0 && <div className="tt-b-alert" role="alert">{t('b.err.generic')}</div>}

                            {step === 0 && (
                                <section>
                                    <h2>{t('b.type.title')}</h2>
                                    <p className="tt-b-hint">{t('b.type.lead')}</p>
                                    <TypePicker types={types} selectedId={state.typeId} onSelect={selectType} currency={currency} />
                                    {errors.type && <small className="err" role="alert">{errors.type}</small>}
                                </section>
                            )}

                            {step === 1 && type && (
                                <section>
                                    <h2>{t('b.custom.title')}</h2>
                                    <p className="tt-b-chosen">{type.name}</p>
                                    {type.supports_pages && state.pages !== null && (
                                        <fieldset className="tt-b-group">
                                            <legend>{t('b.custom.pages')}</legend>
                                            <PagesControl type={type} value={state.pages} onChange={(pages) => patch({ pages })} currency={currency} />
                                        </fieldset>
                                    )}
                                    <OptionsPanel type={type} selected={state.options} onChange={(options) => patch({ options })} currency={currency} />
                                    {errors.options && <small className="err" role="alert">{errors.options}</small>}
                                </section>
                            )}

                            {step === 2 && (
                                <section>
                                    <h2>{t('b.details.title')}</h2>
                                    <DetailsForm state={state} patch={patch} errors={errors} discounts={discounts} limits={limits} currency={currency} />
                                </section>
                            )}

                            {step === 3 && type && (
                                <section>
                                    <h2>{t('b.review.title')}</h2>
                                    <div className="tt-b-card">
                                        <div className="tt-b-card-head"><b>{t('b.review.project')}</b><button type="button" onClick={() => setStep(1)}>{t('b.review.edit')}</button></div>
                                        <p>{type.name}{type.supports_pages && state.pages ? `, ${state.pages} ${t('b.custom.pages_label').toLowerCase()}` : ''}</p>
                                        {estimate && estimate.lines.filter((l) => l.kind === 'option').length > 0 && (
                                            <ul>{estimate.lines.filter((l) => l.kind === 'option').map((l, i) => <li key={`${l.code}-${i}`}>{loc(l.name, locale)}{l.quantity > 1 ? ` × ${l.quantity}` : ''}</li>)}</ul>
                                        )}
                                    </div>
                                    <div className="tt-b-card">
                                        <div className="tt-b-card-head"><b>{t('b.review.you')}</b><button type="button" onClick={() => setStep(2)}>{t('b.review.edit')}</button></div>
                                        <p>{state.customer_name}</p>
                                        <p dir="ltr" className="tt-b-start">{[state.phone, state.email].filter(Boolean).join('  ·  ')}</p>
                                        <p className="tt-b-muted">{state.description}</p>
                                    </div>
                                    <div className="tt-b-card">
                                        <div className="tt-b-card-head"><b>{t('b.review.files')}</b></div>
                                        <p>{state.files.length ? state.files.map((f) => f.name).join(', ') : t('b.review.none')}</p>
                                    </div>
                                    {estimate && <p className="tt-b-total-line">{t('b.sum.total')}: <b><bdi>{money(estimate.total_minor)}</bdi></b></p>}
                                    <p className="tt-b-note">{t('b.review.note')}</p>
                                </section>
                            )}

                            <div className="tt-b-nav">
                                {step > 0 ? (
                                    <button type="button" className="tt-btn txt" onClick={() => setStep(step - 1)}><span className="tt-arr" aria-hidden="true">←</span> {t('b.back')}</button>
                                ) : <span />}
                                {step > 0 && step < 3 && (
                                    <button type="button" className="tt-btn pri" onClick={next}>{t('b.next')} <span className="tt-arr" aria-hidden="true">→</span></button>
                                )}
                                {step === 3 && (
                                    <button type="button" className="tt-btn pri" onClick={submit} disabled={sending || !estimate}>
                                        {sending ? t('b.review.sending') : t('b.review.send')}
                                    </button>
                                )}
                            </div>
                        </div>

                        <SummaryPanel type={type} estimate={estimate} loading={loading} error={estimateError} currency={currency} />
                    </div>
                </div>
                {estimate && (
                    <div className="tt-b-bar" aria-hidden="true">
                        <span>{t('b.sum.total')}</span>
                        <b><bdi>{money(estimate.total_minor)}</bdi></b>
                    </div>
                )}
            </div>
        </SiteLayout>
    );
}
