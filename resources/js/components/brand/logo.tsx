import left from '@/assets/brand/tieco-symbol-left.png';
import right from '@/assets/brand/tieco-symbol-right.png';
import symbol from '@/assets/brand/tieco-symbol.png';
import wordmarkNavy from '@/assets/brand/tieco-wordmark-navy.png';
import wordmarkWhite from '@/assets/brand/tieco-wordmark-white.png';
import type { CSSProperties } from 'react';

/** Symbol + wordmark. The navy/white wordmark swaps with the user's colour scheme (see tieco.css). */
export function Logo({ href, name = 'Tieco Tech', symbolHeight }: { href: string; name?: string; symbolHeight?: number }) {
    return (
        <a href={href} className="tt-logo" aria-label={name}>
            <img className="tt-sy" src={symbol} alt="" style={symbolHeight ? { height: symbolHeight } : undefined} />
            <img className="tt-wm tt-wm-n" src={wordmarkNavy} alt={name} />
            <img className="tt-wm tt-wm-w" src={wordmarkWhite} alt={name} />
        </a>
    );
}

/** Animated hero mark: two pieces fly in and join, then a light sweep passes over the whole symbol. */
export function BrandMark() {
    return (
        <div className="tt-mark" style={{ ['--sym' as string]: `url(${symbol})` } as CSSProperties}>
            <img className="tt-pl" src={left} alt="" />
            <img className="tt-pr" src={right} alt="" />
            <div className="tt-shine" />
        </div>
    );
}
