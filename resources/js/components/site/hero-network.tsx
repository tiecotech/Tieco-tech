import { useEffect, useRef } from 'react';

interface Dot { x: number; y: number; vx: number; vy: number; c: string }

const RGB = ['139,92,255', '255,62,190', '255,122,69']; // brand purple, magenta, orange

/**
 * A faint network of drifting dots inside the hero, joined to the cursor when it is near.
 * It fills its parent, stops for visitors who prefer reduced motion, when the hero is off screen
 * or the tab is hidden, and is left out on small screens.
 */
export function HeroNetwork() {
    const canvas = useRef<HTMLCanvasElement>(null);

    useEffect(() => {
        const el = canvas.current;
        const host = el?.parentElement;
        const ctx = el?.getContext('2d');
        if (!el || !host || !ctx || window.innerWidth < 760) return;

        const reduced = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
        let w = 0, h = 0, raf = 0, mx = -9999, my = -9999, visible = true;
        let dots: Dot[] = [];

        const resize = () => {
            const dpr = Math.min(window.devicePixelRatio || 1, 1.5);
            w = host.clientWidth; h = host.clientHeight;
            el.width = w * dpr; el.height = h * dpr;
            ctx.setTransform(dpr, 0, 0, dpr, 0, 0);
            const n = Math.round(Math.min(46, (w * h) / 22000));
            dots = Array.from({ length: n }, (_, i) => ({
                x: Math.random() * w, y: Math.random() * h,
                vx: (Math.random() - 0.5) * 0.16, vy: (Math.random() - 0.5) * 0.16, c: RGB[i % RGB.length],
            }));
        };

        const draw = () => {
            ctx.clearRect(0, 0, w, h);
            if (!reduced) {
                for (const p of dots) {
                    p.x += p.vx; p.y += p.vy;
                    if (p.x < 0 || p.x > w) p.vx *= -1;
                    if (p.y < 0 || p.y > h) p.vy *= -1;
                }
            }
            for (let i = 0; i < dots.length; i++) {
                const a = dots[i];
                for (let j = i + 1; j < dots.length; j++) {
                    const b = dots[j], d = Math.hypot(a.x - b.x, a.y - b.y);
                    if (d < 140) {
                        ctx.strokeStyle = `rgba(${a.c},${0.13 * (1 - d / 140)})`;
                        ctx.lineWidth = 1;
                        ctx.beginPath(); ctx.moveTo(a.x, a.y); ctx.lineTo(b.x, b.y); ctx.stroke();
                    }
                }
                const dm = Math.hypot(a.x - mx, a.y - my);
                if (dm < 170) {
                    ctx.strokeStyle = `rgba(${a.c},${0.3 * (1 - dm / 170)})`;
                    ctx.beginPath(); ctx.moveTo(a.x, a.y); ctx.lineTo(mx, my); ctx.stroke();
                }
            }
            for (const p of dots) {
                ctx.fillStyle = `rgba(${p.c},.5)`;
                ctx.beginPath(); ctx.arc(p.x, p.y, 1.6, 0, Math.PI * 2); ctx.fill();
            }
            if (!reduced && visible && !document.hidden) raf = requestAnimationFrame(draw);
        };
        const restart = () => { cancelAnimationFrame(raf); raf = requestAnimationFrame(draw); };

        const move = (e: PointerEvent) => { const b = host.getBoundingClientRect(); mx = e.clientX - b.left; my = e.clientY - b.top; };
        const leave = () => { mx = my = -9999; };
        const io = new IntersectionObserver(([entry]) => { visible = entry.isIntersecting; if (visible) restart(); });
        const ro = new ResizeObserver(resize);

        resize(); draw();
        io.observe(host); ro.observe(host);
        host.addEventListener('pointermove', move, { passive: true });
        host.addEventListener('pointerleave', leave);
        document.addEventListener('visibilitychange', restart);

        return () => {
            cancelAnimationFrame(raf); io.disconnect(); ro.disconnect();
            host.removeEventListener('pointermove', move);
            host.removeEventListener('pointerleave', leave);
            document.removeEventListener('visibilitychange', restart);
        };
    }, []);

    return <canvas ref={canvas} className="tt-net" aria-hidden="true" />;
}
