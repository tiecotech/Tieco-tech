import { BrandMark } from '@/components/brand/logo';
import { ServiceIcon } from '@/components/site/icons';
import { HeroNetwork } from '@/components/site/hero-network';
import { ProjectRow } from '@/components/site/project-parts';
import { useSite, type ProjectItem, type ServiceItem } from '@/lib/tieco';
import { Link } from '@inertiajs/react';
import { useEffect, useRef, type PointerEvent } from 'react';

const Arrow = () => <span className="tt-arr" aria-hidden="true">→</span>;

export function Hero() {
    const { t, url } = useSite();
    const hero = useRef<HTMLDivElement>(null);
    const tilt = useRef<HTMLDivElement>(null);
    const reduce = typeof window !== 'undefined' && window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    const move = (e: PointerEvent<HTMLDivElement>) => {
        const b = e.currentTarget.getBoundingClientRect();
        const x = (e.clientX - b.left) / b.width;
        const y = (e.clientY - b.top) / b.height;
        hero.current?.style.setProperty('--mx', `${x * 100}%`);
        hero.current?.style.setProperty('--my', `${y * 100}%`);
        if (!reduce && tilt.current) tilt.current.style.transform = `rotateY(${(x - 0.5) * 14}deg) rotateX(${(0.5 - y) * 14}deg)`;
    };
    const leave = () => tilt.current && (tilt.current.style.transform = '');

    return (
        <div className="tt-hero" ref={hero} onPointerMove={move} onPointerLeave={leave}>
            <div className="tt-blob tt-b1" /><div className="tt-blob tt-b2" /><div className="tt-blob tt-b3" /><div className="tt-glow" /><HeroNetwork />
            <div className="tt-w tt-hero-grid">
                <div>
                    <h1 className="tt-h1">
                        <span className="tt-ln"><span>{t('hero.line1')}</span></span>
                        <span className="tt-ln"><span className="tt-grad">{t('hero.line2')}</span></span>
                    </h1>
                    <p className="tt-sub tt-fade">{t('hero.sub')}</p>
                    <div className="tt-cta tt-fade">
                        <Link className="tt-btn pri" href={url('/start')}>{t('hero.cta')} <Arrow /></Link>
                        <a className="tt-btn txt" href={`${url()}#services`}>{t('hero.more')} <Arrow /></a>
                    </div>
                </div>
                <div className="tt-art" aria-hidden="true">
                    <div className="tt-tilt" ref={tilt}>
                        <svg className="tt-orbit" viewBox="0 0 100 100"><circle className="r" cx="50" cy="50" r="49" /><circle cx="50" cy="1" r="1.6" fill="#ff7a45" /><circle cx="96" cy="68" r="1.1" fill="#00b6ff" /></svg>
                        <svg className="tt-orbit two" viewBox="0 0 100 100"><circle className="r d" cx="50" cy="50" r="49" /><circle cx="50" cy="99" r="1.5" fill="#ff3ebe" /></svg>
                        <div className="tt-slab" />
                        <BrandMark />
                        {[1, 2, 3, 4].map((n) => <span key={n} className={`tt-chip tt-c${n}`}>{t(`hero.chip${n}`)}</span>)}
                    </div>
                </div>
            </div>
        </div>
    );
}

export function Marquee({ services }: { services: ServiceItem[] }) {
    if (!services.length) return null;
    const names = services.map((s) => s.name);

    return (
        <div className="tt-mq" aria-hidden="true">
            <div className="tt-mqt">
                {[...names, ...names].map((n, i) => <span key={i}>{n}</span>)}
            </div>
        </div>
    );
}

export function Services({ services }: { services: ServiceItem[] }) {
    const { t } = useSite();

    return (
        <section className="tt-sec" id="services">
            <div className="tt-w">
                <h2 className="tt-h2 tt-rv">{t('services.title')}</h2>
                <p className="tt-lead tt-rv">{t('services.lead')}</p>
                <div className="tt-svc tt-rv">
                    {services.map((s) => (
                        <div className="tt-sv" key={s.id}>
                            <ServiceIcon name={s.icon} />
                            <h3>{s.name}</h3>
                            {s.summary && <p>{s.summary}</p>}
                        </div>
                    ))}
                </div>
            </div>
        </section>
    );
}

// Set to false to hide the "Our work" section and its nav links again.
export const SHOW_WORK = true;

export function Work({ projects }: { projects: ProjectItem[] }) {
    const { t, url } = useSite();

    return (
        <section className="tt-sec tt-alt" id="work">
            <div className="tt-w">
                <h2 className="tt-h2 tt-rv">{t('work.title')}</h2>
                <p className="tt-lead tt-rv">{t('work.lead')}</p>
                {projects.length ? (
                    <>
                        <div className="pj-list">
                            {projects.map((p, i) => <ProjectRow key={p.id} p={p} flip={i % 2 === 1} />)}
                        </div>
                        <div className="pj-all tt-rv">
                            <Link className="tt-btn out" href={url('/projects')}>{t('work.all')} <Arrow /></Link>
                        </div>
                    </>
                ) : (
                    <div className="tt-empty tt-rv">
                        <div>
                            <h3 style={{ fontSize: 24, margin: '0 0 8px' }}>{t('work.empty_title')}</h3>
                            <p style={{ color: 'var(--tt-mut)', margin: '0 0 22px' }}>{t('work.empty_text')}</p>
                            <Link className="tt-btn out" href={url('/start')}>{t('work.cta')} <Arrow /></Link>
                        </div>
                        <div className="tt-vis" aria-hidden="true">
                            <svg width="170" viewBox="0 0 160 110" fill="none">
                                <rect x="8" y="8" width="144" height="94" rx="10" stroke="#8b5cff" strokeWidth="2" /><path d="M8 34h144" stroke="#8b5cff" strokeWidth="2" />
                                <circle cx="22" cy="21" r="3" fill="#ff7a45" /><circle cx="33" cy="21" r="3" fill="#ff3ebe" />
                                <path className="ln" d="M30 86l26-28 20 18 18-14 36 24" stroke="#00b6ff" strokeWidth="2.5" strokeLinejoin="round" />
                            </svg>
                        </div>
                    </div>
                )}
            </div>
        </section>
    );
}

function processCurve(points: { x: number; y: number }[]) {
    if (points.length < 2) {
        return '';
    }

    let d = `M ${points[0].x.toFixed(1)} ${points[0].y.toFixed(1)}`;

    for (let i = 1; i < points.length; i++) {
        const a = points[i - 1];
        const b = points[i];
        const dx = b.x - a.x;
        const dy = b.y - a.y;
        const dist = Math.hypot(dx, dy) || 1;
        const amp = Math.min(48, dist * 0.32) * (i % 2 === 1 ? 1 : -1);
        const px = (-dy / dist) * amp;
        const py = (dx / dist) * amp;
        const c1x = a.x + dx * 0.32 + px;
        const c1y = a.y + dy * 0.32 + py;
        const c2x = a.x + dx * 0.68 - px;
        const c2y = a.y + dy * 0.68 - py;
        d += ` C ${c1x.toFixed(1)} ${c1y.toFixed(1)} ${c2x.toFixed(1)} ${c2y.toFixed(1)} ${b.x.toFixed(1)} ${b.y.toFixed(1)}`;
    }

    return d;
}

export function Process() {
    const { t } = useSite();
    const ref = useRef<HTMLDivElement>(null);
    const track = useRef<SVGPathElement>(null);
    const flow = useRef<SVGPathElement>(null);
    const hash = useRef<SVGPathElement>(null);

    useEffect(() => {
        const el = ref.current;
        if (!el) {
            return;
        }

        const reduce = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

        const layout = () => {
            const box = el.getBoundingClientRect();
            const points = Array.from(el.querySelectorAll<HTMLElement>('.tt-dot')).map((dot) => {
                const r = dot.getBoundingClientRect();

                return { x: r.left + r.width / 2 - box.left, y: r.top + r.height / 2 - box.top };
            });
            const d = processCurve(points);
            [track.current, flow.current, hash.current].forEach((path) => {
                if (path) {
                    path.setAttribute('d', d);
                }
            });
            const grad = el.querySelector('#tt-process-grad');
            const last = points[points.length - 1];
            if (grad && last) {
                grad.setAttribute('x1', String(points[0].x));
                grad.setAttribute('y1', String(points[0].y));
                grad.setAttribute('x2', String(last.x));
                grad.setAttribute('y2', String(last.y));
            }
            paint();
        };

        const paint = () => {
            const box = el.getBoundingClientRect();
            const p = reduce
                ? 1
                : Math.min(1, Math.max(0, (window.innerHeight * 0.72 - box.top) / Math.max(box.height, 1)));
            const path = flow.current;
            const len = path?.getTotalLength() ?? 0;
            if (path && len) {
                path.style.strokeDasharray = String(len);
                path.style.strokeDashoffset = String(len * (1 - p));
            }
            el.querySelectorAll('.tt-st').forEach((n, i) => n.classList.toggle('is-on', p > 0 && p >= i / 4 - 0.02));
        };

        layout();
        const ro = new ResizeObserver(layout);
        ro.observe(el);
        window.addEventListener('scroll', paint, { passive: true });
        window.addEventListener('resize', layout);

        return () => {
            ro.disconnect();
            window.removeEventListener('scroll', paint);
            window.removeEventListener('resize', layout);
        };
    }, []);

    return (
        <section className="tt-sec" id="process">
            <div className="tt-w">
                <h2 className="tt-h2 tt-rv">{t('process.title')}</h2>
                <p className="tt-lead tt-rv">{t('process.lead')}</p>
                <div className="tt-steps" ref={ref}>
                    <svg className="tt-steps-svg" aria-hidden="true">
                        <defs>
                            <linearGradient id="tt-process-grad" gradientUnits="userSpaceOnUse" x1="0" y1="0" x2="100%" y2="0">
                                <stop offset="0%" stopColor="var(--tt-p)" />
                                <stop offset="50%" stopColor="var(--tt-m)" />
                                <stop offset="100%" stopColor="var(--tt-o)" />
                            </linearGradient>
                            <filter id="tt-process-glow" x="-20%" y="-40%" width="140%" height="180%">
                                <feGaussianBlur stdDeviation="3.5" result="b" />
                                <feMerge>
                                    <feMergeNode in="b" />
                                    <feMergeNode in="SourceGraphic" />
                                </feMerge>
                            </filter>
                        </defs>
                        <path ref={track} className="tt-steps-track" />
                        <path ref={hash} className="tt-steps-hash" />
                        <path ref={flow} className="tt-steps-flow" filter="url(#tt-process-glow)" />
                    </svg>
                    {[1, 2, 3, 4, 5].map((n) => (
                        <div className="tt-st" key={n}>
                            <span className="tt-dot">{n}</span>
                            <div>
                                <h3>{t(`process.${n}.title`)}</h3>
                                <p>{t(`process.${n}.text`)}</p>
                            </div>
                        </div>
                    ))}
                </div>
            </div>
        </section>
    );
}

export function Why() {
    const { t } = useSite();

    return (
        <section className="tt-sec tt-alt" id="about">
            <div className="tt-w tt-why">
                <div className="tt-rv"><h2 className="tt-h2">{t('why.title')}</h2><p className="tt-lead" style={{ margin: 0 }}>{t('why.lead')}</p></div>
                <ul className="tt-rv">
                    {[1, 2, 3].map((n) => <li key={n}><b>{t(`why.${n}.title`)}</b><span>{t(`why.${n}.text`)}</span></li>)}
                </ul>
            </div>
        </section>
    );
}

export function CtaBand() {
    const { t, url } = useSite();
    return (
        <div className="tt-band" id="contact">
            <div className="tt-w">
                <h2 className="tt-h2">{t('cta.title')}</h2>
                <Link className="tt-btn light" href={url('/start')}>{t('cta.button')} <Arrow /></Link>
            </div>
        </div>
    );
}
