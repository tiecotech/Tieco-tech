import '../../css/builder.css';
import '../../css/pages.css';

import SiteLayout from '@/components/site/site-layout';
import { useSite } from '@/lib/tieco';
import { Link, useForm } from '@inertiajs/react';
import type { FormEvent } from 'react';

interface Props {
    details: { email?: string | null; phone?: string | null; whatsapp?: string | null; address?: string | null };
    sent: boolean;
}

export default function Contact({ details, sent }: Props) {
    const { t, url } = useSite();
    const form = useForm({ name: '', email: '', phone: '', subject: '', message: '', preferred_contact: '', website: '' });
    const e = form.errors;
    const has = Boolean(details.email || details.phone || details.whatsapp || details.address);

    const submit = (ev: FormEvent) => {
        ev.preventDefault();
        form.post(url('/contact'), { preserveScroll: true, onSuccess: () => form.reset() });
    };

    if (sent) {
        return (
            <SiteLayout title={t('contact.sent_title')}>
                <div className="tt-pg"><div className="tt-w tt-pg-ok">
                    <h1>{t('contact.sent_title')}</h1>
                    <p>{t('contact.sent_text')}</p>
                    <Link className="tt-btn out" href={url('/contact')} preserveState={false}>{t('contact.another')}</Link>
                </div></div>
            </SiteLayout>
        );
    }

    const field = (id: keyof typeof form.data, label: string, opts: { type?: string; optional?: boolean; area?: boolean; dir?: 'ltr' } = {}) => (
        <div className={`tt-b-field${e[id] ? ' has-error' : ''}`}>
            <label htmlFor={`c-${id}`}>{label}{opts.optional && <em> ({t('contact.optional')})</em>}</label>
            {opts.area ? (
                <textarea className="tt-b-in" id={`c-${id}`} rows={6} maxLength={4000} value={form.data[id]} onChange={(x) => form.setData(id, x.target.value)} />
            ) : (
                <input className="tt-b-in" id={`c-${id}`} type={opts.type ?? 'text'} dir={opts.dir} value={form.data[id]} onChange={(x) => form.setData(id, x.target.value)} />
            )}
            {e[id] && <small className="err" role="alert">{e[id]}</small>}
        </div>
    );

    return (
        <SiteLayout title={t('contact.title')} description={t('contact.lead')}>
            <div className="tt-pg">
                <div className="tt-w">
                    <header className="tt-pg-head"><h1>{t('contact.title')}</h1><p>{t('contact.lead')}</p></header>
                    <div className="tt-pg-contact">
                        <form className="tt-pg-form" onSubmit={submit} noValidate>
                            {Object.keys(e).length > 0 && <div className="tt-b-alert" role="alert">{t('contact.check')}</div>}
                            {field('name', t('contact.name'))}
                            <div className="tt-b-two">
                                {field('phone', t('contact.phone'), { dir: 'ltr' })}
                                {field('email', t('contact.email'), { type: 'email', optional: true, dir: 'ltr' })}
                            </div>
                            <small style={{ color: 'var(--tt-mut)' }}>{t('contact.need_one')}</small>
                            {field('subject', t('contact.subject'))}
                            {field('message', t('contact.message'), { area: true })}
                            <div className="tt-b-field">
                                <label htmlFor="c-pref">{t('contact.preferred')} <em>({t('contact.optional')})</em></label>
                                <select className="tt-b-in" id="c-pref" value={form.data.preferred_contact} onChange={(x) => form.setData('preferred_contact', x.target.value)}>
                                    <option value="" />
                                    {['phone', 'whatsapp', 'email'].map((m) => <option key={m} value={m}>{t(`contact.method.${m}`)}</option>)}
                                </select>
                            </div>
                            <div className="tt-hp" aria-hidden="true">
                                <label htmlFor="c-website">Website</label>
                                <input className="tt-b-in" id="c-website" tabIndex={-1} autoComplete="off" value={form.data.website} onChange={(x) => form.setData('website', x.target.value)} />
                            </div>
                            <div><button type="submit" className="tt-btn pri" disabled={form.processing}>{form.processing ? t('contact.sending') : t('contact.send')}</button></div>
                        </form>

                        <aside className="tt-pg-side">
                            <div className="tt-pg-box">
                                <h2>{t('contact.details')}</h2>
                                {has ? (
                                    <>
                                        {details.phone && <a href={`tel:${details.phone}`} dir="ltr" style={{ textAlign: 'start' }}>{details.phone}</a>}
                                        {details.whatsapp && <a href={`https://wa.me/${details.whatsapp.replace(/\D/g, '')}`} target="_blank" rel="noopener noreferrer">WhatsApp</a>}
                                        {details.email && <a href={`mailto:${details.email}`}>{details.email}</a>}
                                        {details.address && <p style={{ marginTop: 8 }}>{details.address}</p>}
                                    </>
                                ) : <p>{t('contact.none_yet')}</p>}
                            </div>
                            <div className="tt-pg-box">
                                <h2>{t('contact.builder_title')}</h2>
                                <p>{t('contact.builder_text')}</p>
                                <Link className="tt-btn pri" style={{ width: 'fit-content' }} href={url('/start')}>{t('hero.cta')} <span className="tt-arr" aria-hidden="true">→</span></Link>
                            </div>
                        </aside>
                    </div>
                </div>
            </div>
        </SiteLayout>
    );
}
