import { useEffect } from 'react';

/** Adds .is-in to every .tt-rv element when it scrolls into view (once). */
export function useReveal(deps: unknown[] = []) {
    useEffect(() => {
        const els = Array.from(document.querySelectorAll<HTMLElement>('.tt-rv:not(.is-in)'));
        if (!('IntersectionObserver' in window)) {
            els.forEach((el) => el.classList.add('is-in'));
            return;
        }
        const io = new IntersectionObserver(
            (entries) =>
                entries.forEach((e) => {
                    if (e.isIntersecting) {
                        e.target.classList.add('is-in');
                        io.unobserve(e.target);
                    }
                }),
            { threshold: 0.15 },
        );
        els.forEach((el) => io.observe(el));

        return () => io.disconnect();
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, deps);
}
