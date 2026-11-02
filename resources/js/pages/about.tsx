import '../../css/pages.css';

import SiteLayout from '@/components/site/site-layout';
import { useSite } from '@/lib/tieco';
import { Link } from '@inertiajs/react';

interface Props {
    custom: { title: string; body: string[] } | null;
    services: { id: number; slug: string; name: string }[];
    team: { id: number; name: string; role: string; photo: string | null }[];
}

export default function About({ custom, services, team }: Props) {
    const { t, url } = useSite();
    const title = custom?.title || t('about.title');

    return (
        <SiteLayout title={title} description={t('about.lead')} services={services}>
            <div className="tt-pg">
                <div className="tt-w">
                    <header className="tt-pg-head">
                        <h1>{title}</h1>
                        {!custom?.body.length && <p>{t('about.lead')}</p>}
                    </header>

                    {custom && custom.body.length > 0 && (
                        <div className="tt-pg-prose">{custom.body.map((p, i) => <p key={i}>{p}</p>)}</div>
                    )}

                    <div className="tt-pg-two">
                        <div className="tt-pg-block">
                            <h2>{t('about.what_title')}</h2>
                            <ul className="tt-pg-list">
                                {services.map((s) => <li key={s.id}><Link href={url(`/services/${s.slug}`)}>{s.name}</Link></li>)}
                            </ul>
                        </div>
                        <div className="tt-pg-block">
                            <h2>{t('about.how_title')}</h2>
                            <ul className="tt-pg-list">{[1, 2, 3].map((n) => <li key={n}>{t(`about.how.${n}`)}</li>)}</ul>
                        </div>
                    </div>

                    {team.length > 0 && (
                        <div className="tt-pg-block">
                            <h2>{t('about.team_title')}</h2>
                            <div className="tt-pg-team">
                                {team.map((m) => (
                                    <div key={m.id} className="tt-pg-member">
                                        <div className="ph" style={m.photo ? { backgroundImage: `url(${m.photo})` } : undefined} role="img" aria-label={m.name} />
                                        <b>{m.name}</b><span>{m.role}</span>
                                    </div>
                                ))}
                            </div>
                        </div>
                    )}

                    <div className="tt-pg-cta">
                        <div><h2>{t('svc.cta_title')}</h2><p>{t('svc.cta_text')}</p></div>
                        <Link className="tt-btn pri" href={url('/start')}>{t('about.cta')} <span className="tt-arr" aria-hidden="true">→</span></Link>
                    </div>
                </div>
            </div>
        </SiteLayout>
    );
}
