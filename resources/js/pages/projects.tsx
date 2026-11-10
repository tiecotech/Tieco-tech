import '../../css/pages.css';

import { ProjectRow } from '@/components/site/project-parts';
import SiteLayout from '@/components/site/site-layout';
import { useSite, type ProjectItem } from '@/lib/tieco';
import { Link } from '@inertiajs/react';

interface Props { projects: ProjectItem[]; services: { id: number; name: string }[] }

export default function Projects({ projects, services }: Props) {
    const { t, url } = useSite();

    return (
        <SiteLayout title={t('projects.title')} description={t('projects.meta')} services={services}>
            <div className="tt-pg">
                <div className="tt-w">
                    <header className="tt-pg-head">
                        <h1>{t('projects.title')}</h1>
                        <p>{t('projects.lead')}</p>
                    </header>
                    <div className="pj-list">
                        {projects.map((p, i) => <ProjectRow key={p.id} p={p} flip={i % 2 === 1} />)}
                    </div>
                    <section className="pj-cta tt-rv">
                        <h2>{t('project.cta_title')}</h2>
                        <p>{t('project.cta_text')}</p>
                        <Link className="tt-btn pri" href={url('/start')}>{t('project.cta_btn')} <span className="tt-arr" aria-hidden="true">→</span></Link>
                    </section>
                </div>
            </div>
        </SiteLayout>
    );
}
