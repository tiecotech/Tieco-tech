import '../../css/coming-soon.css';

import symbolLeft from '@/assets/brand/tieco-symbol-left.png';
import symbolRight from '@/assets/brand/tieco-symbol-right.png';
import wordmark from '@/assets/brand/tieco-wordmark-white.png';
import { Head } from '@inertiajs/react';
import { useEffect, useRef, useState, type ReactNode } from 'react';

type Lang = 'en' | 'ar';

interface Text {
    meta_title: string; meta_description: string; badge: string; title: string; subtitle: string; lead: string;
    days: string; hours: string; minutes: string; seconds: string; launch: string; done: string; contact: string;
    lang: string; steps: string[]; rights: string;
}

interface Props { locale: Lang; text: Text; launchDate: string | null; whatsapp: string | null; email: string | null }

/** Launch date as local midnight, matching how the date is entered in the dashboard. */
function target(date: string | null): number | null {
    const m = date?.match(/^(\d{4})-(\d{2})-(\d{2})$/);

    return m ? new Date(+m[1], +m[2] - 1, +m[3]).getTime() : null;
}

function useCountdown(at: number | null) {
    const [now, setNow] = useState<number | null>(null); // null until mounted, so server and client agree
    useEffect(() => {
        setNow(Date.now());
        const id = window.setInterval(() => setNow(Date.now()), 1000);

        return () => window.clearInterval(id);
    }, []);
    if (at === null || now === null) return null;
    const s = Math.max(0, Math.floor((at - now) / 1000));

    return { left: s, d: Math.floor(s / 86400), h: Math.floor((s % 86400) / 3600), m: Math.floor((s % 3600) / 60), s: s % 60 };
}

/** Slowly drifting points joined by faint lines: a quiet "network" behind the content. */
function Network() {
    const ref = useRef<HTMLCanvasElement>(null);
    useEffect(() => {
        const canvas = ref.current;
        const ctx = canvas?.getContext('2d');
        if (!canvas || !ctx) return;
        const still = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
        const colors = ['106,59,255', '255,62,190', '255,122,69'];
        let w = 0, h = 0, raf = 0;
        let dots: { x: number; y: number; vx: number; vy: number; c: string }[] = [];

        const resize = () => {
            const dpr = Math.min(window.devicePixelRatio || 1, 2);
            w = canvas.clientWidth; h = canvas.clientHeight;
            canvas.width = w * dpr; canvas.height = h * dpr;
            ctx.setTransform(dpr, 0, 0, dpr, 0, 0);
            const n = Math.round(Math.min(70, (w * h) / 22000));
            dots = Array.from({ length: n }, (_, i) => ({
                x: Math.random() * w, y: Math.random() * h,
                vx: (Math.random() - 0.5) * 0.25, vy: (Math.random() - 0.5) * 0.25, c: colors[i % colors.length],
            }));
        };
        const draw = () => {
            ctx.clearRect(0, 0, w, h);
            for (const p of dots) {
                if (!still) {
                    p.x += p.vx; p.y += p.vy;
                    if (p.x < 0 || p.x > w) p.vx *= -1;
                    if (p.y < 0 || p.y > h) p.vy *= -1;
                }
            }
            for (let i = 0; i < dots.length; i++) {
                for (let j = i + 1; j < dots.length; j++) {
                    const a = dots[i], b = dots[j];
                    const dist = Math.hypot(a.x - b.x, a.y - b.y);
                    if (dist < 140) {
                        ctx.strokeStyle = `rgba(${a.c},${0.16 * (1 - dist / 140)})`;
                        ctx.lineWidth = 1;
                        ctx.beginPath(); ctx.moveTo(a.x, a.y); ctx.lineTo(b.x, b.y); ctx.stroke();
                    }
                }
            }
            for (const p of dots) {
                ctx.fillStyle = `rgba(${p.c},.7)`;
                ctx.beginPath(); ctx.arc(p.x, p.y, 1.6, 0, Math.PI * 2); ctx.fill();
            }
            if (!still) raf = requestAnimationFrame(draw);
        };

        resize(); draw();
        window.addEventListener('resize', resize);

        return () => { cancelAnimationFrame(raf); window.removeEventListener('resize', resize); };
    }, []);

    return <canvas ref={ref} className="cs-net" aria-hidden="true" />;
}

/** Cycles through the build steps with a typed-out line, like a deploy log. */
function BuildLog({ steps }: { steps: string[] }) {
    const [i, setI] = useState(0);
    useEffect(() => {
        const id = window.setInterval(() => setI((n) => (n + 1) % steps.length), 2800);

        return () => window.clearInterval(id);
    }, [steps.length]);

    return (
        <div className="cs-log" aria-hidden="true">
            <span className="cs-log-dots"><i /><i /><i /></span>
            <span className="cs-log-line" key={i}>
                <b>{'>'}</b> {steps[i]}<span className="cs-caret" />
            </span>
            <span className="cs-log-bar"><span style={{ width: `${((i + 1) / steps.length) * 100}%` }} /></span>
        </div>
    );
}

function Unit({ value, label }: { value: number | null; label: string }) {
    const v = value === null ? '--' : String(value).padStart(2, '0');

    return (
        <div className="cs-unit">
            <b><span key={v}>{v}</span></b>
            <small>{label}</small>
        </div>
    );
}

export default function ComingSoon({ locale, text, launchDate, whatsapp, email }: Props) {
    const at = target(launchDate);
    const c = useCountdown(at);
    const other: Lang = locale === 'ar' ? 'en' : 'ar';
    const dateLabel = at ? new Intl.DateTimeFormat(locale === 'ar' ? 'ar-SY-u-nu-latn' : 'en-GB', { day: 'numeric', month: 'long', year: 'numeric' }).format(at) : null;
    const wa = whatsapp?.replace(/\D/g, '');

    return (
        <div className="tt cs" dir={locale === 'ar' ? 'rtl' : 'ltr'} lang={locale}>
            <Head title={text.meta_title}>
                <meta name="description" content={text.meta_description} />
                <meta name="theme-color" content="#070b22" />
            </Head>

            <div className="cs-bg" aria-hidden="true">
                <div className="cs-aurora cs-a1" /><div className="cs-aurora cs-a2" /><div className="cs-aurora cs-a3" />
                <div className="cs-grid" />
            </div>
            <Network />

            <header className="cs-top">
                <a className="cs-lang" href={`/${other}`} hrefLang={other} lang={other}>{text.lang}</a>
            </header>

            <main className="cs-main">
                <div className="cs-logo" role="img" aria-label="Tieco Tech">
                    <svg className="cs-orbit" viewBox="0 0 200 200" aria-hidden="true">
                        <defs>
                            <linearGradient id="cs-ring" x1="0" y1="0" x2="1" y2="1">
                                <stop offset="0" stopColor="#6a3bff" /><stop offset=".55" stopColor="#ff3ebe" /><stop offset="1" stopColor="#ff7a45" />
                            </linearGradient>
                        </defs>
                        <circle className="cs-ring r1" cx="100" cy="100" r="94" />
                        <circle className="cs-ring r2" cx="100" cy="100" r="80" />
                        <circle className="cs-sat" cx="100" cy="6" r="3" />
                    </svg>
                    <div className="cs-mark">
                        <img className="cs-half l" src={symbolLeft} alt="" />
                        <img className="cs-half r" src={symbolRight} alt="" />
                    </div>
                </div>

                <img className="cs-word" src={wordmark} alt="Tieco Tech" />

                <p className="cs-badge"><span className="cs-pulse" />{text.badge}</p>
                <h1 className="cs-title">{text.title}</h1>
                <p className="cs-sub" dir={locale === 'ar' ? 'ltr' : 'rtl'}>{text.subtitle}</p>
                <p className="cs-lead">{text.lead}</p>

                <BuildLog steps={text.steps} />

                {at !== null && (
                    c && c.left === 0 ? (
                        <p className="cs-done">{text.done}</p>
                    ) : (
                        <div className="cs-count" dir="ltr">
                            <Unit value={c?.d ?? null} label={text.days} />
                            <Unit value={c?.h ?? null} label={text.hours} />
                            <Unit value={c?.m ?? null} label={text.minutes} />
                            <Unit value={c?.s ?? null} label={text.seconds} />
                        </div>
                    )
                )}
                {dateLabel && <p className="cs-date">{text.launch}: <bdi>{dateLabel}</bdi></p>}

                {(wa || email) && (
                    <div className="cs-contact">
                        {wa && <p>{text.contact}</p>}
                        <div className="cs-actions">
                            {wa && (
                                <a className="cs-wa" href={`https://wa.me/${wa}`} target="_blank" rel="noopener noreferrer">
                                    <svg viewBox="0 0 32 32" aria-hidden="true"><path d="M16 3C8.8 3 3 8.8 3 16c0 2.3.6 4.5 1.7 6.4L3 29l6.8-1.8A13 13 0 0 0 16 29c7.2 0 13-5.8 13-13S23.2 3 16 3zm0 23.7c-2 0-3.9-.6-5.5-1.6l-.4-.2-4 1 1.1-3.9-.3-.4A10.7 10.7 0 1 1 16 26.7zm5.9-8c-.3-.2-1.9-.9-2.2-1-.3-.1-.5-.2-.7.2-.2.3-.8 1-1 1.2-.2.2-.4.2-.7.1-.3-.2-1.4-.5-2.6-1.6-1-.9-1.6-1.9-1.8-2.3-.2-.3 0-.5.1-.7l.5-.6c.2-.2.2-.3.3-.5.1-.2.1-.4 0-.6l-1-2.3c-.2-.6-.5-.5-.7-.5h-.6c-.2 0-.6.1-.9.4-.3.3-1.2 1.1-1.2 2.8s1.2 3.3 1.4 3.5c.2.2 2.4 3.7 5.9 5.1 2.2.9 3 .9 4 .8.6-.1 1.9-.8 2.2-1.6.3-.8.3-1.4.2-1.6-.1-.2-.3-.3-.6-.4z" /></svg>
                                    <span dir="ltr">{whatsapp}</span>
                                </a>
                            )}
                            {email && <a className="cs-mail" href={`mailto:${email}`} dir="ltr">{email}</a>}
                        </div>
                    </div>
                )}
            </main>

            <footer className="cs-foot">© {new Date().getFullYear()} {text.rights}</footer>
        </div>
    );
}

// Full-screen branded page: no site or admin layout.
ComingSoon.layout = (page: ReactNode) => page;
