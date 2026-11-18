import { Logo } from '@/components/brand/logo';
import { GuideWidget, openGuide } from '@/components/guide/guide-widget';
import { SHOW_WORK } from '@/components/site/sections';
import { useReveal } from '@/hooks/use-reveal';
import { useSite } from '@/lib/tieco';
import { Head, Link } from '@inertiajs/react';
import { useEffect, useState, type PropsWithChildren } from 'react';

function Header() {
    const { t, url, alternate, site } = useSite();
    const [scrolled, setScrolled] = useState(false);
    const [open, setOpen] = useState(false);

    useEffect(() => {
        const onScroll = () => setScrolled(window.scrollY > 20);
        onScroll();
        window.addEventListener('scroll', onScroll, { passive: true });

        return () => window.removeEventListener('scroll', onScroll);
    }, []);

    const links = [
        ['nav.services', '/services'],
        ['nav.work', '/projects'],
        ['nav.about', '/about'],
        ['nav.contact', '/contact'],
    ].filter(([k]) => SHOW_WORK || k !== 'nav.work') as [string, string][];
    // Paths starting with '#' are home-page sections; everything else is a page.
    const href = (h: string) => (h.startsWith('#') ? `${url()}${h}` : url(h));

    return (
        <header className={`tt-header${scrolled ? ' is-scrolled' : ''}`}>
            <div className="tt-w tt-bar">
                <Logo href={url()} name={site.name} />
                <nav className="tt-nav" aria-label="Main">
                    {links.map(([k, h]) => (
                        <Link key={k} href={href(h)}>{t(k)}</Link>
                    ))}
                </nav>
                <a className="tt-lang" href={alternate.url} hrefLang={alternate.locale} lang={alternate.locale} aria-label={t('nav.language')}>
                    {alternate.label}
                </a>
                <button type="button" className="tt-btn out tt-cta-h" onClick={openGuide}>{t('nav.talk')}</button>
                <button className="tt-burger" type="button" aria-expanded={open} aria-label={t('nav.menu')} onClick={() => setOpen(!open)}>
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round">
                        <path d={open ? 'M6 6l12 12M18 6L6 18' : 'M4 7h16M4 12h16M4 17h16'} />
                    </svg>
                </button>
            </div>
            <div className={`tt-menu${open ? ' is-open' : ''}`}>
                {links.map(([k, h]) => (
                    <Link key={k} href={href(h)} onClick={() => setOpen(false)}>{t(k)}</Link>
                ))}
                <Link className="tt-btn pri" href={url('/start')} style={{ justifySelf: 'start', width: 'fit-content' }}>{t('hero.cta')}</Link>
            </div>
        </header>
    );
}

function Footer({ services }: { services: { id: number; name: string }[] }) {
    const { t, url, site, legal } = useSite();
    const c = site.contact ?? {};
    const hasContact = Boolean(c.email || c.phone || c.whatsapp);

    return (
        <footer className="tt-footer">
            <div className="tt-w">
                <div className="tt-fg">
                    <div>
                        <Logo href={url()} name={site.name} symbolHeight={30} />
                        <p style={{ margin: '14px 0 0', maxWidth: '30ch' }}>{t('footer.tagline')}</p>
                    </div>
                    <div className="l">
                        <h4>{t('footer.services')}</h4>
                        {services.slice(0, 5).map((s) => (
                            <Link key={s.id} href={url('/services')}>{s.name}</Link>
                        ))}
                    </div>
                    <div className="l">
                        <h4>{t('footer.company')}</h4>
                        <Link href={url('/about')}>{t('nav.about')}</Link>
                        <Link href={url('/contact')}>{t('nav.contact')}</Link>
                        {(legal ?? []).map((p) => <Link key={p.slug} href={url(`/legal/${p.slug}`)}>{p.title}</Link>)}
                        {SHOW_WORK && <Link href={url('/projects')}>{t('nav.work')}</Link>}
                        <Link href={url('/start')}>{t('hero.cta')}</Link>
                        {site.support && <Link href={url('/support')} style={{ color: 'var(--tt-o)' }}>♥ {t('nav.support')}</Link>}
                    </div>
                    <div className="l">
                        <h4>{t('footer.contact')}</h4>
                        {hasContact ? (
                            <>
                                {c.email && <a href={`mailto:${c.email}`}>{c.email}</a>}
                                {c.phone && <a href={`tel:${c.phone}`}>{c.phone}</a>}
                                {c.whatsapp && <a href={`https://wa.me/${c.whatsapp.replace(/\D/g, '')}`} rel="noopener noreferrer" target="_blank">WhatsApp</a>}
                            </>
                        ) : (
                            <span>{t('footer.contact_soon')}</span>
                        )}
                    </div>
                </div>
                <div className="tt-copy">© {new Date().getFullYear()} {site.name}. {t('footer.rights')}</div>
            </div>
        </footer>
    );
}

interface Props extends PropsWithChildren {
    title?: string;
    description?: string;
    services?: { id: number; name: string }[];
}

export default function SiteLayout({ children, title, description, services = [] }: Props) {
    const { locale, alternate, canonical, site } = useSite();
    useReveal();

    return (
        <div className="tt">
            <Head title={title}>
                {description && <meta name="description" content={description} />}
                <link rel="canonical" href={canonical} />
                <link rel="alternate" hrefLang={locale} href={canonical} />
                <link rel="alternate" hrefLang={alternate.locale} href={alternate.url} />
                <meta property="og:site_name" content={site.name} />
                <meta property="og:locale" content={locale === 'ar' ? 'ar_AR' : 'en_US'} />
                {title && <meta property="og:title" content={title} />}
                {description && <meta property="og:description" content={description} />}
            </Head>
            <Header />
            <main>{children}</main>
            <Footer services={services} />
            <GuideWidget />
        </div>
    );
}
