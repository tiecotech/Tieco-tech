import '../../css/support.css';

import SiteLayout from '@/components/site/site-layout';
import { useSite } from '@/lib/tieco';
import { Link } from '@inertiajs/react';
import { useState } from 'react';

interface Props {
    bank: { bank_name: string; holder: string; account: string; branch: string } | null;
    shamcash: { holder: string; account: string; qr: string | null } | null;
}

function CopyBtn({ value }: { value: string }) {
    const { t } = useSite();
    const [done, setDone] = useState(false);
    const copy = async () => {
        try {
            await navigator.clipboard.writeText(value);
            setDone(true);
            setTimeout(() => setDone(false), 1600);
        } catch {
            /* clipboard blocked: the number stays visible to copy by hand */
        }
    };

    return <button type="button" className="tt-sup-copy" onClick={copy}>{done ? t('sup.copied') : t('sup.copy')}</button>;
}

const ICONS = [
    'M4 17l6-6 4 4 6-8M14 7h6v6', // growth
    'M12 3l9 5-9 5-9-5 9-5zM6 10.5V16c3 2.5 9 2.5 12 0v-5.5', // talent / graduation
    'M12 21s-7-4.5-7-10a4 4 0 0 1 7-2.6A4 4 0 0 1 19 11c0 5.5-7 10-7 10z', // community / heart
];

export default function Support({ bank, shamcash }: Props) {
    const { t, url, site } = useSite();
    const wa = site.contact?.whatsapp;
    const share = async () => {
        const data = { title: site.name, text: t('sup.share_text'), url: window.location.href };
        try {
            if (navigator.share) await navigator.share(data);
            else await navigator.clipboard.writeText(data.url);
        } catch {
            /* user closed the share sheet */
        }
    };
    const title = t('sup.title');

    return (
        <SiteLayout title={title} description={t('sup.meta')}>
            <div className="tt-sup">
                <div className="tt-w">
                    <header className="tt-sup-hero tt-rv">
                        <span className="tt-sup-eyebrow"><i />{t('sup.eyebrow')}</span>
                        <h1><span>{title}</span></h1>
                        <p>{t('sup.lead')}</p>
                        <a className="tt-btn pri" href="#ways">{t('sup.cta')} <span className="tt-arr" aria-hidden="true">↓</span></a>
                    </header>

                    <section className="tt-sup-sec tt-rv">
                        <h2>{t('sup.why_title')}</h2>
                        <div className="tt-sup-why">
                            {[1, 2, 3].map((n) => (
                                <div key={n}>
                                    <span className="tt-sup-ic"><svg viewBox="0 0 24 24" aria-hidden="true"><path d={ICONS[n - 1]} /></svg></span>
                                    <b>{t(`sup.why.${n}.t`)}</b>
                                    <p>{t(`sup.why.${n}.d`)}</p>
                                </div>
                            ))}
                        </div>
                    </section>

                    <section className="tt-sup-sec tt-rv" id="ways">
                        <h2>{t('sup.ways_title')}</h2>
                        <p>{t('sup.ways_lead')}</p>
                        <div className="tt-sup-ways">
                            {bank && (
                                <div className="tt-sup-card">
                                    <div className="tt-sup-card-head">
                                        <div><small>{t('sup.bank')}</small><b>{bank.bank_name || t('sup.bank')}</b></div>
                                        <span className="tt-sup-chip" aria-hidden="true" />
                                    </div>
                                    <div>
                                        <small style={{ opacity: 0.7, fontSize: 12 }}>{t('sup.account')}</small>
                                        <div className="tt-sup-num" dir="ltr">{bank.account}</div>
                                    </div>
                                    <div className="tt-sup-row">
                                        <div>
                                            {bank.holder && <><small>{t('sup.holder')}</small><b>{bank.holder}</b></>}
                                            {bank.branch && <small style={{ marginTop: 6 }}>{t('sup.branch')}: {bank.branch}</small>}
                                        </div>
                                        <CopyBtn value={bank.account} />
                                    </div>
                                </div>
                            )}
                            {shamcash && (
                                <div className="tt-sup-card sham">
                                    <div className="tt-sup-card-head">
                                        <div><small>{t('sup.sham')}</small><b>{shamcash.holder || 'Sham Cash'}</b></div>
                                    </div>
                                    <div className="tt-sup-qr">
                                        {shamcash.qr && <img src={shamcash.qr} alt={`${t('sup.sham')} QR`} loading="lazy" />}
                                        <p>{t('sup.sham_scan')}</p>
                                    </div>
                                    {shamcash.account && (
                                        <div className="tt-sup-row">
                                            <div><small>{t('sup.sham_account')}</small><b dir="ltr">{shamcash.account}</b></div>
                                            <CopyBtn value={shamcash.account} />
                                        </div>
                                    )}
                                </div>
                            )}
                        </div>
                        <p className="tt-sup-note">{t('sup.client_note')}</p>
                        <div className="tt-sup-thanks">
                            <div><b>{t('sup.thanks_title')}</b><p>{t('sup.thanks_text')}</p></div>
                            {wa
                                ? <a className="tt-btn pri" href={`https://wa.me/${wa.replace(/\D/g, '')}?text=${encodeURIComponent(t('sup.thanks_msg'))}`} target="_blank" rel="noopener noreferrer">{t('sup.thanks_btn')}</a>
                                : <Link className="tt-btn pri" href={url('/contact')}>{t('nav.contact')}</Link>}
                        </div>
                    </section>

                    <section className="tt-sup-sec tt-rv">
                        <h2>{t('sup.free_title')}</h2>
                        <div className="tt-sup-free" style={{ marginTop: 28 }}>
                            <div><b>{t('sup.free.1.t')}</b><p>{t('sup.free.1.d')}</p></div>
                            <div><b>{t('sup.free.2.t')}</b><p>{t('sup.free.2.d')}</p><button type="button" className="tt-btn out" onClick={share}>{t('sup.share')}</button></div>
                            <div><b>{t('sup.free.3.t')}</b><p>{t('sup.free.3.d')}</p><Link className="tt-btn out" href={url('/start')}>{t('sup.start')}</Link></div>
                        </div>
                    </section>

                    <p className="tt-sup-closing tt-rv"><span aria-hidden="true" />{t('sup.closing')}</p>
                </div>
            </div>
        </SiteLayout>
    );
}
