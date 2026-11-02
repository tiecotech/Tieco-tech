import '../../css/pages.css';

import { ServiceIcon } from '@/components/site/icons';
import SiteLayout from '@/components/site/site-layout';
import { useSite, type ServiceItem } from '@/lib/tieco';
import { Link } from '@inertiajs/react';

export default function Services({ services }: { services: ServiceItem[] }) {
    const { t, url } = useSite();

    return (
        <SiteLayout title={t('svc.page_title')} description={t('svc.page_lead')} services={services}>
            <div className="tt-pg">
                <div className="tt-w">
                    <header className="tt-pg-head">
                        <h1>{t('svc.page_title')}</h1>
                        <p>{t('svc.page_lead')}</p>
                    </header>
                    <div className="tt-pg-grid">
                        {services.map((s) => (
                            <Link key={s.id} className="tt-pg-svc" href={url(`/services/${s.slug}`)}>
                                <ServiceIcon name={s.icon} />
                                <h2>{s.name}</h2>
                                {s.summary && <p>{s.summary}</p>}
                                <span className="more">{t('svc.view')} <span className="tt-arr" aria-hidden="true">→</span></span>
                            </Link>
                        ))}
                    </div>
                    <div className="tt-pg-cta">
                        <div><h2>{t('svc.cta_title')}</h2><p>{t('svc.cta_text')}</p></div>
                        <Link className="tt-btn pri" href={url('/start')}>{t('hero.cta')} <span className="tt-arr" aria-hidden="true">→</span></Link>
                    </div>
                </div>
            </div>
        </SiteLayout>
    );
}
