import '../../css/pages.css';

import { LiveBadge, Mockup, PjIcon, TechChips, accentStyle } from '@/components/site/project-parts';
import SiteLayout from '@/components/site/site-layout';
import { useSite, type ProjectDetail, type ProjectItem } from '@/lib/tieco';
import { Link } from '@inertiajs/react';
import { useCallback, useEffect, useState } from 'react';

interface Props { project: ProjectDetail; others: ProjectItem[]; services: { id: number; name: string }[] }

const Arrow = () => <span className="tt-arr" aria-hidden="true">→</span>;

function Lightbox({ items, index, onClose, onMove }: {
    items: ProjectDetail['gallery']; index: number; onClose: () => void; onMove: (d: number) => void;
}) {
    const { t } = useSite();
    useEffect(() => {
        const key = (e: KeyboardEvent) => {
            if (e.key === 'Escape') onClose();
            if (e.key === 'ArrowRight') onMove(1);
            if (e.key === 'ArrowLeft') onMove(-1);
        };
        window.addEventListener('keydown', key);
        document.body.style.overflow = 'hidden';

        return () => { window.removeEventListener('keydown', key); document.body.style.overflow = ''; };
    }, [onClose, onMove]);
    const item = items[index];

    return (
        <div className="pj-lb" role="dialog" aria-modal="true" aria-label={item.alt ?? ''} onClick={onClose}>
            <button type="button" className="pj-lb-x" onClick={onClose} aria-label={t('project.close')}>×</button>
            <figure onClick={(e) => e.stopPropagation()}>
                <img src={item.src} alt={item.alt ?? ''} />
                {item.alt && <figcaption>{item.alt}</figcaption>}
            </figure>
        </div>
    );
}

export default function Project({ project: p, others, services }: Props) {
    const { t, url } = useSite();
    const [open, setOpen] = useState<number | null>(null);
    const move = useCallback((d: number) => setOpen((i) => (i === null ? i : (i + d + p.gallery.length) % p.gallery.length)), [p.gallery.length]);
    const close = useCallback(() => setOpen(null), []);

    return (
        <SiteLayout title={p.title} description={p.summary ?? undefined} services={services}>
            <div className="pj-page" style={accentStyle(p.accent)}>
                <header className="pj-hero">
                    <div className="tt-w pj-hero-in">
                        <div className="pj-hero-copy">
                            <Link className="pj-back" href={url('/projects')}><span className="tt-arr" aria-hidden="true">←</span> {t('project.back')}</Link>
                            <div className="pj-tags">
                                {p.category && <span className="pj-pill">{p.category}</span>}
                                <LiveBadge />
                            </div>
                            <h1>{p.title}</h1>
                            {p.summary && <p className="pj-sum">{p.summary}</p>}
                            <div className="pj-actions">
                                {p.url && <a className="tt-btn pri" href={p.url} target="_blank" rel="noopener noreferrer">{t('work.visit')} <span className="pj-ext"><PjIcon name="external" /></span></a>}
                                <Link className="tt-btn out" href={url('/start')}>{t('project.cta_btn')} <Arrow /></Link>
                            </div>
                            <dl className="pj-meta">
                                {p.year && <div><dt>{t('project.year')}</dt><dd>{p.year}</dd></div>}
                                {p.category && <div><dt>{t('project.type')}</dt><dd>{p.category}</dd></div>}
                                {p.role.length > 0 && <div className="wide"><dt>{t('project.role')}</dt><dd>{p.role.join(' · ')}</dd></div>}
                            </dl>
                        </div>
                        <Mockup p={p} size="lg" />
                    </div>
                </header>

                {p.stats.length > 0 && (
                    <div className="tt-w">
                        <dl className="pj-band tt-rv">
                            {p.stats.map((s) => <div key={s.label}><dd>{s.value}</dd><dt>{s.label}</dt></div>)}
                        </dl>
                    </div>
                )}

                <section className="pj-sec2">
                    <div className="tt-w pj-two">
                        <h2>{t('project.overview')}</h2>
                        <div className="pj-prose tt-rv">{p.paragraphs.map((para, i) => <p key={i}>{para}</p>)}</div>
                    </div>
                </section>

                {p.features.length > 0 && (
                    <section className="pj-sec2">
                        <div className="tt-w">
                            <h2>{t('project.features')}</h2>
                            <div className="pj-feats">
                                {p.features.map((f) => (
                                    <article className="pj-feat tt-rv" key={f.title}>
                                        <span className="pj-feat-ico"><PjIcon name={f.icon} /></span>
                                        <h3>{f.title}</h3>
                                        <p>{f.text}</p>
                                    </article>
                                ))}
                            </div>
                        </div>
                    </section>
                )}

                {p.gallery.length > 0 && (
                    <section className="pj-sec2">
                        <div className="tt-w">
                            <h2>{t('project.gallery')}</h2>
                            <div className="pj-gal">
                                {p.gallery.map((g, i) => (
                                    <button type="button" key={g.src} className={`pj-shot tt-rv${g.mobile ? ' is-mobile' : ''}`} onClick={() => setOpen(i)} aria-label={g.alt ?? p.title}>
                                        <span className="pj-shot-frame">
                                            {!g.mobile && <span className="pj-bar" aria-hidden="true"><i /><i /><i /></span>}
                                            <img src={g.src} alt={g.alt ?? ''} loading="lazy" />
                                        </span>
                                        {g.alt && <small>{g.alt}</small>}
                                    </button>
                                ))}
                            </div>
                        </div>
                    </section>
                )}

                <section className="pj-sec2">
                    <div className="tt-w pj-two">
                        <h2>{t('project.tech')}</h2>
                        <div className="tt-rv"><TechChips tech={p.tech} /></div>
                    </div>
                </section>

                {others.length > 0 && (
                    <section className="pj-sec2">
                        <div className="tt-w">
                            <h2>{t('project.more')}</h2>
                            <div className="pj-more">
                                {others.map((o) => (
                                    <Link key={o.id} href={url(`/projects/${o.slug}`)} className="pj-mini tt-rv" style={accentStyle(o.accent)}>
                                        <div className="pj-mini-img" style={o.desktop ? { backgroundImage: `url(${o.desktop})` } : undefined} />
                                        <div className="pj-mini-b"><b>{o.title}</b><span>{o.category}</span></div>
                                        <Arrow />
                                    </Link>
                                ))}
                            </div>
                        </div>
                    </section>
                )}

                <div className="tt-w">
                    <section className="pj-cta tt-rv">
                        <h2>{t('project.cta_title')}</h2>
                        <p>{t('project.cta_text')}</p>
                        <Link className="tt-btn pri" href={url('/start')}>{t('project.cta_btn')} <Arrow /></Link>
                    </section>
                </div>
            </div>
            {open !== null && <Lightbox items={p.gallery} index={open} onClose={close} onMove={move} />}
        </SiteLayout>
    );
}
