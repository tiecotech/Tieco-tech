import AdminLayout from '@/components/admin/admin-layout';
import { Txt } from '@/components/admin/fields';
import { names, useAdmin } from '@/lib/admin';
import { Link, router } from '@inertiajs/react';
import { useState } from 'react';

interface Props {
    statuses: { id: number; key: string; name: string }[];
    types: { id: number; name: Record<string, string> | string }[];
    today: string;
}

/** Archive a deal closed outside the website so the dashboard tracks all business in one place. */
export default function CreateDeal({ statuses, types, today }: Props) {
    const { t, url, locale, currency } = useAdmin();
    const defaultStatus = statuses.find((s) => s.key === 'accepted') ?? statuses[0];
    const [d, setD] = useState({
        customer_name: '', phone: '', email: '', country: '',
        project_type_id: '', title: '', description: '', request_status_id: String(defaultStatus?.id ?? ''),
        final_price: '', sold_at: today, paid_amount: '', paid_at: today, method: 'cash',
    });
    const [errors, setErrors] = useState<Record<string, string>>({});
    const [busy, setBusy] = useState(false);
    const set = (k: keyof typeof d) => (v: string) => setD({ ...d, [k]: v });

    const submit = () => {
        // Only send sale/payment fields when they are filled, so "not sold yet" stays empty.
        const payload: Record<string, string | null> = { ...d, project_type_id: d.project_type_id || null };
        if (!d.final_price) { payload.final_price = null; payload.sold_at = null; }
        if (!d.paid_amount) { payload.paid_amount = null; payload.paid_at = null; payload.method = null; }
        setBusy(true);
        router.post(url('/requests'), payload, { onError: (e) => setErrors(e as Record<string, string>), onFinish: () => setBusy(false) });
    };

    return (
        <AdminLayout title={t('s.new_title')}>
            <Link className="ta-back" href={url('/requests')}>← {t('a.show.back')}</Link>
            <p className="ta-lead">{t('s.new_lead')}</p>

            <form onSubmit={(e) => { e.preventDefault(); submit(); }} style={{ display: 'grid', gap: 18, maxWidth: 820 }}>
                <section className="ta-card" style={{ display: 'grid', gap: 12 }}>
                    <h2>{t('s.customer')}</h2>
                    <div className="ta-row" style={{ flexWrap: 'wrap', gap: 12 }}>
                        <Txt label={`${t('s.name')} *`} value={d.customer_name} onChange={set('customer_name')} error={errors.customer_name} />
                        <Txt label={t('s.phone')} dir="ltr" value={d.phone} onChange={set('phone')} error={errors.phone} hint="+963..." />
                    </div>
                    <div className="ta-row" style={{ flexWrap: 'wrap', gap: 12 }}>
                        <Txt label={t('s.email')} type="email" dir="ltr" value={d.email} onChange={set('email')} error={errors.email} />
                        <Txt label={t('s.country')} value={d.country} onChange={set('country')} error={errors.country} />
                    </div>
                </section>

                <section className="ta-card" style={{ display: 'grid', gap: 12 }}>
                    <h2>{t('s.project')}</h2>
                    <div className="ta-row" style={{ flexWrap: 'wrap', gap: 12 }}>
                        <label className="ta-fld" style={{ flex: '1 1 220px' }}>
                            <span>{t('s.type')}</span>
                            <select className="ta-in" value={d.project_type_id} onChange={(e) => set('project_type_id')(e.target.value)}>
                                <option value="">{t('s.type_none')}</option>
                                {types.map((x) => <option key={x.id} value={x.id}>{names(x.name, locale)}</option>)}
                            </select>
                        </label>
                        <Txt label={`${t('s.title')}${d.project_type_id ? '' : ' *'}`} value={d.title} onChange={set('title')} error={errors.title} hint={t('s.title_hint')} />
                    </div>
                    <Txt label={t('s.description')} area={3} value={d.description} onChange={set('description')} error={errors.description} />
                    <label className="ta-fld" style={{ maxWidth: 320 }}>
                        <span>{t('s.status')}</span>
                        <select className="ta-in" value={d.request_status_id} onChange={(e) => set('request_status_id')(e.target.value)}>
                            {statuses.map((s) => <option key={s.id} value={s.id}>{s.name}</option>)}
                        </select>
                    </label>
                </section>

                <section className="ta-card" style={{ display: 'grid', gap: 12 }}>
                    <h2>{t('s.deal')}</h2>
                    <p className="ta-muted">{t('s.optional')}</p>
                    <div className="ta-row" style={{ flexWrap: 'wrap', gap: 12 }}>
                        <Txt label={`${t('s.final_price')} (${currency.code})`} type="number" dir="ltr" value={d.final_price} onChange={set('final_price')} error={errors.final_price} />
                        <Txt label={t('s.sold_at')} type="date" value={d.sold_at} onChange={set('sold_at')} error={errors.sold_at} />
                    </div>
                    <h3>{t('s.first_payment')}</h3>
                    <div className="ta-row" style={{ flexWrap: 'wrap', gap: 12 }}>
                        <Txt label={`${t('s.amount')} (${currency.code})`} type="number" dir="ltr" value={d.paid_amount} onChange={set('paid_amount')} error={errors.paid_amount} />
                        <Txt label={t('s.paid_at')} type="date" value={d.paid_at} onChange={set('paid_at')} error={errors.paid_at} />
                        <label className="ta-fld" style={{ flex: '1 1 160px' }}>
                            <span>{t('s.method')}</span>
                            <select className="ta-in" value={d.method} onChange={(e) => set('method')(e.target.value)}>
                                {['cash', 'transfer', 'card', 'other'].map((m) => <option key={m} value={m}>{t(`s.m.${m}`)}</option>)}
                            </select>
                        </label>
                    </div>
                </section>

                <div><button className="ta-btn pri" type="submit" disabled={busy || !d.customer_name.trim()}>{t('s.save')}</button></div>
            </form>
        </AdminLayout>
    );
}
