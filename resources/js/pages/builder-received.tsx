import '../../css/builder.css';

import type { Currency } from '@/components/builder/types';
import SiteLayout from '@/components/site/site-layout';
import { formatMoney } from '@/lib/money';
import { useSite } from '@/lib/tieco';
import { Link } from '@inertiajs/react';

interface Props {
    submitted: { number: string; total_minor: number; currency: string; has_unpriced_items: boolean; email_sent: boolean };
    currency: Currency;
}

export default function BuilderReceived({ submitted, currency }: Props) {
    const { t, locale, url } = useSite();

    return (
        <SiteLayout title={t('b.done.title')}>
            <div className="tt-b tt-b-done">
                <div className="tt-w">
                    <div className="tt-b-ok" aria-hidden="true">
                        <svg viewBox="0 0 24 24"><path d="M5 12.5l4.5 4.5L19 7.5" /></svg>
                    </div>
                    <h1 className="tt-h2">{t('b.done.title')}</h1>
                    <p className="tt-b-hint">{t('b.done.text')}</p>
                    <div className="tt-b-number">
                        <small>{t('b.done.number')}</small>
                        <strong dir="ltr">{submitted.number}</strong>
                    </div>
                    <p className="tt-b-total-line">
                        {t('b.done.estimate')}: <b><bdi>{formatMoney(submitted.total_minor, currency, locale)}</bdi></b>
                    </p>
                    {submitted.has_unpriced_items && <p className="tt-b-warn">{t('b.done.unpriced')}</p>}
                    {submitted.email_sent && <p className="tt-b-hint">{t('b.done.email')}</p>}
                    <div className="tt-b-nav center">
                        <Link className="tt-btn pri" href={url()}>{t('b.done.home')}</Link>
                        <Link className="tt-btn out" href={url('/start')}>{t('b.done.another')}</Link>
                    </div>
                </div>
            </div>
        </SiteLayout>
    );
}
