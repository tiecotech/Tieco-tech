import '../../css/pages.css';

import { ServiceIcon } from '@/components/site/icons';
import SiteLayout from '@/components/site/site-layout';
import { useSite, type ServiceItem } from '@/lib/tieco';
import { Link } from '@inertiajs/react';
import type { ReactNode } from 'react';

interface Detail extends ServiceItem {
    description: string | null;
    problems: string[];
    deliverables: string[];
    features: string[];
    steps: { title: string; text: string }[];
    faqs: { q: string; a: string }[];
}

function List({ title, items }: { title: string; items: string[] }): ReactNode {
    if (!items.length) return null;

    return (
        <div className="tt-pg-block">
            <h2>{title}</h2>
            <ul className="tt-pg-list">{items.map((x, i) => <li key={i}>{x}</li>)}</ul>
        </div>
    );
}

export default function ServiceShow({ service, others }: { service: Detail; others: ServiceItem[] }) {
    const { t, url } = useSite();
    const hasDetail = Boolean(service.description || service.problems.length || service.deliverables.length || service.features.length || service.steps.length || service.faqs.length);

    return (
        <SiteLayout title={service.name} description={service.summary ?? undefined} services={others}>
            <div className="tt-pg">
                <div className="tt-w">
                    <Link className="tt-pg-back" href={url('/services')}>← {t('svc.back')}</Link>
                    <header className="tt-pg-head">
                        <h1>{service.name}</h1>
                        {service.summary && <p>{service.summary}</p>}
                    </header>

                    {service.description && (
                        <div className="tt-pg-prose">
                            {service.description.split(/\n{2,}/).map((p, i) => <p key={i}>{p}</p>)}
                        </div>
                    )}

                    <div className="tt-pg-two">
                        <List title={t('svc.problems')} items={service.problems} />
                        <List title={t('svc.deliverables')} items={service.deliverables} />
                    </div>
                    <List title={t('svc.features')} items={service.features} />

                    {service.steps.length > 0 && (
                        <div className="tt-pg-block">
                            <h2>{t('svc.steps')}</h2>
                            <div className="tt-pg-steps">
                                {service.steps.map((s, i) => <div key={i}><b>{s.title}</b><span>{s.text}</span></div>)}
                            </div>
                        </div>
                    )}

                    {service.faqs.length > 0 && (
                        <div className="tt-pg-block tt-pg-faq">
                            <h2>{t('svc.faqs')}</h2>
                            {service.faqs.map((f, i) => <details key={i}><summary>{f.q}</summary><p>{f.a}</p></details>)}
                        </div>
                    )}

                    {!hasDetail && <p className="tt-pg-note" style={{ marginTop: 32 }}>{t('svc.more_soon')}</p>}

                    <div className="tt-pg-cta">
                        <div><h2>{t('svc.cta_title')}</h2><p>{t('svc.cta_text')}</p></div>
                        <Link className="tt-btn pri" href={url('/start')}>{t('hero.cta')} <span className="tt-arr" aria-hidden="true">→</span></Link>
                    </div>

                    {others.length > 0 && (
                        <div className="tt-pg-block">
                            <h2>{t('svc.others')}</h2>
                            <div className="tt-pg-grid">
                                {others.map((s) => (
                                    <Link key={s.id} className="tt-pg-svc" href={url(`/services/${s.slug}`)}>
                                        <ServiceIcon name={s.icon} />
                                        <h3>{s.name}</h3>
                                        {s.summary && <p>{s.summary}</p>}
                                    </Link>
                                ))}
                            </div>
                        </div>
                    )}
                </div>
            </div>
        </SiteLayout>
    );
}
