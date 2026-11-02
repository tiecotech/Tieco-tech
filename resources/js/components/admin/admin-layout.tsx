import '../../../css/admin.css';

import { Logo } from '@/components/brand/logo';
import { useAdmin } from '@/lib/admin';
import { Head, Link, router, usePage } from '@inertiajs/react';
import { useState, type PropsWithChildren } from 'react';

export default function AdminLayout({ title, children }: PropsWithChildren<{ title: string }>) {
    const { t, url, can, admin, locale, dir } = useAdmin();
    const current = usePage().url;
    const [bell, setBell] = useState(false);
    const [menu, setMenu] = useState(false);

    const nav = [
        { href: url(), label: t('a.nav.dashboard'), show: can('requests.view'), match: (u: string) => u === '/admin' || u.startsWith('/admin?') },
        { href: url('/requests'), label: t('a.nav.requests'), show: can('requests.view'), match: (u: string) => u.startsWith('/admin/requests') },
        { href: url('/messages'), label: t('a.nav.messages'), show: can('messages.view'), match: (u: string) => u.startsWith('/admin/messages') },
        { href: url('/pricing'), label: t('a.nav.pricing'), show: can('pricing.manage'), match: (u: string) => u.startsWith('/admin/pricing') },
        { href: url('/content/services'), label: t('a.nav.services_c'), show: can('content.manage'), match: (u: string) => u.startsWith('/admin/content/services') },
        { href: url('/content/pages'), label: t('a.nav.pages'), show: can('content.manage'), match: (u: string) => u.startsWith('/admin/content/pages') },
        { href: url('/content/team'), label: t('a.nav.team'), show: can('content.manage'), match: (u: string) => u.startsWith('/admin/content/team') },
        { href: url('/content/settings'), label: t('a.nav.settings'), show: can('content.manage'), match: (u: string) => u.startsWith('/admin/content/settings') },
        { href: url('/users'), label: t('a.nav.users'), show: can('users.manage'), match: (u: string) => u.startsWith('/admin/users') },
        { href: url('/launch'), label: t('a.nav.launch'), show: can('site.launch'), match: (u: string) => u.startsWith('/admin/launch') },
        { href: url('/activity'), label: t('a.nav.activity'), show: can('activity.view'), match: (u: string) => u.startsWith('/admin/activity') },
    ].filter((n) => n.show);

    const switchLang = () => router.post(url('/locale'), { locale: locale === 'ar' ? 'en' : 'ar' }, { preserveScroll: true });
    const logout = () => router.post('/logout');

    return (
        <div className="ta" dir={dir}>
            <Head title={title} />
            <aside className={`ta-side${menu ? ' is-open' : ''}`}>
                <div className="ta-brand"><Logo href={url()} /></div>
                <nav aria-label={t('a.menu')}>
                    {nav.map((n) => (
                        <Link key={n.href} href={n.href} className={n.match(current) ? 'is-active' : ''} onClick={() => setMenu(false)}>{n.label}</Link>
                    ))}
                </nav>
                <a className="ta-site" href={`/${locale}`} target="_blank" rel="noreferrer">{t('a.nav.site')} ↗</a>
            </aside>

            <div className="ta-main">
                <header className="ta-top">
                    <button type="button" className="ta-iconbtn ta-burger" aria-label={t('a.menu')} aria-expanded={menu} onClick={() => setMenu(!menu)}>≡</button>
                    <h1>{title}</h1>
                    <div className="ta-top-actions">
                        <div className="ta-bell">
                            <button type="button" className="ta-iconbtn" aria-label={t('a.notifications')} aria-expanded={bell} onClick={() => setBell(!bell)}>
                                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M6 9a6 6 0 0 1 12 0c0 5 2 6 2 6H4s2-1 2-6zM10 19a2 2 0 0 0 4 0" /></svg>
                                {admin.unread > 0 && <i>{admin.unread > 9 ? '9+' : admin.unread}</i>}
                            </button>
                            {bell && (
                                <div className="ta-pop" role="dialog" aria-label={t('a.notifications')}>
                                    {admin.notifications.length === 0 && <p className="ta-muted">{t('a.no_notifications')}</p>}
                                    {admin.notifications.map((n) => (
                                        n.data.type === 'contact_message.new' ? (
                                            <Link key={n.id} href={url(`/messages/${n.data.message_id}`)} onClick={() => setBell(false)}>
                                                <b>{t('a.new_message')}</b>
                                                <span>{String(n.data.from ?? '')}: {String(n.data.subject ?? '')} · {n.at}</span>
                                            </Link>
                                        ) : (
                                            <Link key={n.id} href={url(`/requests?q=${encodeURIComponent(String(n.data.number ?? ''))}`)} onClick={() => setBell(false)}>
                                                <b>{t('a.new_request')} {String(n.data.number ?? '')}</b>
                                                <span>{String(n.data.customer ?? '')} · {n.at}</span>
                                            </Link>
                                        )
                                    ))}
                                    {admin.unread > 0 && (
                                        <button type="button" onClick={() => router.post(url('/notifications/read'), {}, { preserveScroll: true, onSuccess: () => setBell(false) })}>{t('a.mark_read')}</button>
                                    )}
                                </div>
                            )}
                        </div>
                        <button type="button" className="ta-pill" onClick={switchLang}>{t('a.lang')}</button>
                        <div className="ta-user">
                            <span><b>{admin.user.name}</b><small>{admin.user.role ? t(`a.role.${admin.user.role}`) : ''}</small></span>
                            <button type="button" className="ta-pill" onClick={logout}>{t('a.logout')}</button>
                        </div>
                    </div>
                </header>
                <main className="ta-content">{children}</main>
            </div>
        </div>
    );
}
