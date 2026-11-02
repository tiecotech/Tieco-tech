import SiteLayout from '@/components/site/site-layout';
import { CtaBand, Hero, Marquee, Process, Services, SHOW_WORK, Why, Work } from '@/components/site/sections';
import { useSite, type ProjectItem, type ServiceItem } from '@/lib/tieco';

export default function Home({ services, projects }: { services: ServiceItem[]; projects: ProjectItem[] }) {
    const { t } = useSite();

    return (
        <SiteLayout title={t('meta.title')} description={t('meta.description')} services={services}>
            <Hero />
            <Marquee services={services} />
            <Services services={services} />
            {SHOW_WORK && <Work projects={projects} />}
            <Process />
            <Why />
            <CtaBand />
        </SiteLayout>
    );
}
