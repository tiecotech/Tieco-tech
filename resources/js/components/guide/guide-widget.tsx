import '../../../css/guide.css';

import { useSite } from '@/lib/tieco';
import { Link } from '@inertiajs/react';
import { useEffect, useRef, useState } from 'react';

export const OPEN_GUIDE_EVENT = 'tieco:guide';
export const openGuide = () => window.dispatchEvent(new Event(OPEN_GUIDE_EVENT));

type Stage = 'start' | 'web' | 'app' | 'system' | 'creative' | 'extras' | 'done' | 'unsure';
interface Line { from: 'bot' | 'me'; text: string }

const WEB = ['personal_site', 'company_site', 'service_site', 'blog'];
const CREATIVE = ['branding', 'ads_marketing', 'photo_video', 'bundle'];
const WEB_EXTRAS = ['seo', 'multilingual', 'cms', 'gallery', 'advanced_contact'];
const EXTRAS: Record<string, string[]> = {
    personal_site: WEB_EXTRAS, company_site: WEB_EXTRAS, service_site: [...WEB_EXTRAS, 'booking'], blog: ['articles', ...WEB_EXTRAS],
    online_store: ['payments', 'user_accounts', 'seo', 'multilingual', 'cms'],
    branding: ['logo_identity', 'graphic_design'], ads_marketing: ['ad_design', 'campaign_management'], photo_video: ['photography', 'video_production'],
    bundle: ['logo_identity', 'graphic_design', 'ad_design', 'campaign_management', 'photography', 'video_production'],
};

/** Free guided advisor: fixed questions, no AI service, no data sent anywhere. It only builds a link with a pre-filled selection. */
export function GuideWidget() {
    const { t, url, locale, site } = useSite();
    const [open, setOpen] = useState(false);
    const [stage, setStage] = useState<Stage>('start');
    const [type, setType] = useState<string | null>(null);
    const [options, setOptions] = useState<string[]>([]);
    const [faq, setFaq] = useState<number | null>(null);
    const [log, setLog] = useState<Line[]>([]);
    const scroller = useRef<HTMLDivElement>(null);

    useEffect(() => {
        const on = () => setOpen(true);
        window.addEventListener(OPEN_GUIDE_EVENT, on);

        return () => window.removeEventListener(OPEN_GUIDE_EVENT, on);
    }, []);
    useEffect(() => {
        if (!open) return;
        const esc = (e: KeyboardEvent) => e.key === 'Escape' && setOpen(false);
        window.addEventListener('keydown', esc);

        return () => window.removeEventListener('keydown', esc);
    }, [open]);
    useEffect(() => { scroller.current?.scrollTo({ top: scroller.current.scrollHeight, behavior: 'smooth' }); }, [log, stage, options, open]);

    const say = (from: 'bot' | 'me', text: string) => setLog((l) => [...l, { from, text }]);
    const reset = () => { setStage('start'); setType(null); setOptions([]); setFaq(null); setLog([]); };

    const chooseStart = (k: string) => {
        say('me', t(`g.c.${k}`));
        if (k === 'web') { say('bot', t('g.q.web')); setStage('web'); }
        else if (k === 'store') { setType('online_store'); say('bot', t('g.q.extras')); setStage('extras'); }
        else if (k === 'app') { say('bot', t('g.q.app')); setStage('app'); }
        else if (k === 'system') { say('bot', t('g.q.system')); setStage('system'); }
        else if (k === 'creative') { say('bot', t('g.q.creative')); setStage('creative'); }
        else { say('bot', t('g.unsure.text')); setStage('unsure'); }
    };
    const chooseType = (key: string) => {
        say('me', t(`g.t.${key}`));
        setType(key);
        if (EXTRAS[key]) { say('bot', t('g.q.extras')); setStage('extras'); } else setStage('done');
    };
    const choosePlatform = (code: string) => { say('me', t(`g.o.${code}`)); setType('mobile_app'); setOptions([code]); setStage('done'); };
    const finishExtras = () => { say('me', options.length ? options.map((o) => t(`g.o.${o}`)).join(', ') : t('g.skip')); setStage('done'); };
    const toggle = (code: string) => setOptions((o) => (o.includes(code) ? o.filter((x) => x !== code) : [...o, code]));

    const query = type ? new URLSearchParams({ type, ...(options.length ? { options: options.join(',') } : {}) }).toString() : '';
    const summary = type ? `${t(`g.t.${type}`)}${options.length ? ` ${t('g.wa.options')} ${options.map((o) => t(`g.o.${o}`)).join('، ')}` : ''}` : '';
    const digits = (site.contact?.whatsapp ?? '').replace(/\D/g, '');
    const wa = (text: string) => (digits ? `https://wa.me/${digits}?text=${encodeURIComponent(text)}` : null);
    const waDone = wa(`${t('g.wa.hello')} ${summary}`);
    const waUnsure = wa(t('g.wa.unsure'));

    const chips = (items: string[], prefix: string, on: (k: string) => void) => (
        <div className="tt-g-chips">{items.map((k) => <button key={k} type="button" onClick={() => on(k)}>{t(`${prefix}${k}`)}</button>)}</div>
    );

    return (
        <div className="tt-g">
            {!open && (
                <button type="button" className="tt-g-fab" onClick={() => setOpen(true)} aria-label={t('g.open')}>
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" aria-hidden="true"><path d="M21 12a8 8 0 0 1-11.6 7.1L4 20l1-4.6A8 8 0 1 1 21 12z" /></svg>
                    <span>{t('g.launch')}</span>
                </button>
            )}
            {open && (
                <section className="tt-g-panel" role="dialog" aria-label={t('g.title')} lang={locale}>
                    <header>
                        <div><b>{t('g.title')}</b><small>{t('g.subtitle')}</small></div>
                        <div className="tt-g-actions">
                            {(log.length > 0 || faq !== null) && <button type="button" onClick={reset}>{t('g.restart')}</button>}
                            <button type="button" onClick={() => setOpen(false)} aria-label={t('g.close')}>✕</button>
                        </div>
                    </header>

                    <div className="tt-g-log" ref={scroller} aria-live="polite">
                        <div className="tt-g-msg bot"><p>{t('g.hello')}</p></div>
                        {log.map((l, i) => <div key={i} className={`tt-g-msg ${l.from}`}><p>{l.text}</p></div>)}

                        {stage === 'start' && (
                            <>
                                {chips(['web', 'store', 'app', 'system', 'creative', 'unsure'], 'g.c.', chooseStart)}
                                <div className="tt-g-faq">
                                    <small>{t('g.q.faq')}</small>
                                    {[1, 2, 3].map((n) => (
                                        <div key={n}>
                                            <button type="button" aria-expanded={faq === n} onClick={() => setFaq(faq === n ? null : n)}>{t(`g.faq.${n}.q`)}</button>
                                            {faq === n && <p>{t(`g.faq.${n}.a`)}</p>}
                                        </div>
                                    ))}
                                </div>
                            </>
                        )}
                        {stage === 'web' && chips(WEB, 'g.t.', chooseType)}
                        {stage === 'creative' && chips(CREATIVE, 'g.t.', chooseType)}
                        {stage === 'system' && chips(['booking_system', 'custom_software'], 'g.t.', (k) => { say('me', t(`g.t.${k}`)); setType(k); setStage('done'); })}
                        {stage === 'app' && chips(['android', 'ios', 'android_ios'], 'g.o.', choosePlatform)}

                        {stage === 'extras' && type && (
                            <>
                                <div className="tt-g-chips multi">
                                    {EXTRAS[type].map((k) => <button key={k} type="button" aria-pressed={options.includes(k)} className={options.includes(k) ? 'on' : ''} onClick={() => toggle(k)}>{options.includes(k) ? '✓ ' : ''}{t(`g.o.${k}`)}</button>)}
                                </div>
                                <button type="button" className="tt-btn pri tt-g-go" onClick={finishExtras}>{options.length ? t('g.continue') : t('g.skip')}</button>
                            </>
                        )}

                        {stage === 'done' && type && (
                            <div className="tt-g-rec">
                                <b>{t('g.done.title')}</b>
                                <p className="tt-g-type">{t(`g.t.${type}`)}</p>
                                {options.length > 0 ? <ul>{options.map((o) => <li key={o}>{t(`g.o.${o}`)}</li>)}</ul> : <small>{t('g.done.none')}</small>}
                                <Link className="tt-btn pri" href={`${url('/start')}?${query}`}>{t('g.done.builder')} <span className="tt-arr" aria-hidden="true">→</span></Link>
                                {waDone && <a className="tt-btn out" href={waDone} target="_blank" rel="noopener noreferrer">{t('g.done.whatsapp')}</a>}
                                <small>{t('g.done.note')}</small>
                            </div>
                        )}

                        {stage === 'unsure' && (
                            <div className="tt-g-rec">
                                <Link className="tt-btn pri" href={url('/start')}>{t('g.done.builder')} <span className="tt-arr" aria-hidden="true">→</span></Link>
                                {waUnsure && <a className="tt-btn out" href={waUnsure} target="_blank" rel="noopener noreferrer">{t('g.done.whatsapp')}</a>}
                                <Link className="tt-btn txt" href={url('/contact')}>{t('g.done.contact')}</Link>
                            </div>
                        )}
                    </div>
                </section>
            )}
        </div>
    );
}
