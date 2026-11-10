import '../../../css/projects.css';

import { useSite, type ProjectItem } from '@/lib/tieco';
import { Link } from '@inertiajs/react';
import type { CSSProperties, ReactNode } from 'react';

const ICONS: Record<string, ReactNode> = {
    globe: <><circle cx="12" cy="12" r="9" /><path d="M3 12h18M12 3c2.8 3 2.8 15 0 18M12 3c-2.8 3-2.8 15 0 18" /></>,
    file: <><path d="M14 3H7a2 2 0 00-2 2v14a2 2 0 002 2h10a2 2 0 002-2V8z" /><path d="M14 3v5h5M9 13h6M9 17h4" /></>,
    layout: <><rect x="3" y="4" width="18" height="16" rx="3" /><path d="M3 10h18M9 10v10" /></>,
    search: <><circle cx="11" cy="11" r="7" /><path d="M20 20l-3.5-3.5" /></>,
    dashboard: <><rect x="3" y="3" width="7" height="9" rx="1.5" /><rect x="14" y="3" width="7" height="5" rx="1.5" /><rect x="14" y="12" width="7" height="9" rx="1.5" /><rect x="3" y="16" width="7" height="5" rx="1.5" /></>,
    spark: <path d="M12 3l1.8 5.2L19 10l-5.2 1.8L12 17l-1.8-5.2L5 10l5.2-1.8zM18 16l.8 2.2L21 19l-2.2.8L18 22l-.8-2.2L15 19l2.2-.8z" />,
    book: <><path d="M4 5a2 2 0 012-2h13v16H6a2 2 0 00-2 2zM4 19a2 2 0 002 2h13" /><path d="M8 7h7" /></>,
    chat: <><path d="M4 5h16a1 1 0 011 1v10a1 1 0 01-1 1h-9l-5 4v-4H4a1 1 0 01-1-1V6a1 1 0 011-1z" /><path d="M8 10h8M8 13h5" /></>,
    heart: <path d="M12 20s-7-4.4-9-9a5 5 0 019-3 5 5 0 019 3c-2 4.6-9 9-9 9z" />,
    clock: <><circle cx="12" cy="12" r="9" /><path d="M12 7v5l3 2" /></>,
    link: <path d="M10 14a4 4 0 005.7 0l3-3a4 4 0 00-5.7-5.7l-1 1M14 10a4 4 0 00-5.7 0l-3 3a4 4 0 005.7 5.7l1-1" />,
    external: <path d="M7 17L17 7M9 7h8v8" />,
};

export function PjIcon({ name }: { name: string }) {
    return (
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.8" strokeLinecap="round" strokeLinejoin="round" aria-hidden="true">
            {ICONS[name] ?? ICONS.spark}
        </svg>
    );
}

export const accentStyle = (accent: [string, string] | null): CSSProperties =>
    ({ '--a1': accent?.[0] ?? '#6a3bff', '--a2': accent?.[1] ?? '#ff3ebe' }) as CSSProperties;

/** A desktop browser with the home page, and a phone with the mobile version overlapping it. */
export function Mockup({ p, size = 'md' }: { p: ProjectItem; size?: 'md' | 'lg' }) {
    return (
        <div className={`pj-stage pj-${size}`} style={accentStyle(p.accent)}>
            <div className="pj-glow" aria-hidden="true" />
            <div className="pj-browser">
                <div className="pj-bar" aria-hidden="true"><i /><i /><i /><span>{p.host}</span></div>
                {p.desktop && <img src={p.desktop} alt={p.title} loading="lazy" width="1440" height="900" />}
            </div>
            {p.mobile && (
                <div className="pj-phone">
                    <img src={p.mobile} alt="" loading="lazy" width="390" height="844" />
                </div>
            )}
            {p.logo && (
                <div className={`pj-logo${p.logo_light ? ' is-light' : ''}`}>
                    <img src={p.logo} alt="" loading="lazy" />
                </div>
            )}
        </div>
    );
}

export function TechChips({ tech }: { tech: string[] }) {
    return <ul className="pj-tech">{tech.map((x) => <li key={x}>{x}</li>)}</ul>;
}

export function LiveBadge() {
    const { t } = useSite();

    return <span className="pj-live"><i aria-hidden="true" />{t('work.live')}</span>;
}

const Arrow = () => <span className="tt-arr" aria-hidden="true">→</span>;

/** One project as a case-study row: mockup on one side, story on the other. */
export function ProjectRow({ p, flip = false }: { p: ProjectItem; flip?: boolean }) {
    const { t, url } = useSite();

    return (
        <article className={`pj-row tt-rv${flip ? ' is-flip' : ''}`} style={accentStyle(p.accent)}>
            <Link className="pj-media" href={url(`/projects/${p.slug}`)} aria-label={p.title} tabIndex={-1}>
                <Mockup p={p} />
            </Link>
            <div className="pj-copy">
                <div className="pj-tags">
                    {p.category && <span className="pj-pill">{p.category}</span>}
                    <LiveBadge />
                </div>
                <h3><Link href={url(`/projects/${p.slug}`)}>{p.title}</Link></h3>
                {p.summary && <p className="pj-sum">{p.summary}</p>}
                {p.stats.length > 0 && (
                    <dl className="pj-stats">
                        {p.stats.map((s) => <div key={s.label}><dt>{s.label}</dt><dd>{s.value}</dd></div>)}
                    </dl>
                )}
                <TechChips tech={p.tech} />
                <div className="pj-actions">
                    <Link className="tt-btn pri" href={url(`/projects/${p.slug}`)}>{t('work.view')} <Arrow /></Link>
                    {p.url && <a className="tt-btn out" href={p.url} target="_blank" rel="noopener noreferrer">{t('work.visit')} <span className="pj-ext"><PjIcon name="external" /></span></a>}
                </div>
            </div>
        </article>
    );
}
