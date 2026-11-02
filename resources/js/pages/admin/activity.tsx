import AdminLayout from '@/components/admin/admin-layout';
import { useAdmin } from '@/lib/admin';
import { Link, router } from '@inertiajs/react';
import { useState, type FormEvent } from 'react';

interface Log { id: number; action: string; user: string | null; ip: string | null; subject: string | null; details: string | null; at: string | null }
interface Props { logs: { data: Log[]; links: { url: string | null; label: string; active: boolean }[]; total: number }; filters: { q: string } }

export default function Activity({ logs, filters }: Props) {
    const { t, url } = useAdmin();
    const [q, setQ] = useState(filters.q);
    const go = (e: FormEvent) => { e.preventDefault(); router.get(url('/activity'), q ? { q } : {}, { preserveState: true }); };

    return (
        <AdminLayout title={t('a.nav.activity')}>
            <p className="ta-lead">{t('a.act.lead')}</p>
            <form className="ta-filters" onSubmit={go}>
                <input className="ta-in grow" type="search" placeholder={t('a.act.search')} value={q} onChange={(e) => setQ(e.target.value)} />
                <button className="ta-btn pri" type="submit">{t('a.req.filter')}</button>
            </form>
            <div className="ta-tablewrap">
                <table className="ta-table">
                    <thead><tr><th>{t('a.req.date')}</th><th>{t('a.act.action')}</th><th>{t('a.act.who')}</th><th>{t('a.act.on')}</th><th>{t('a.act.details')}</th></tr></thead>
                    <tbody>
                        {logs.data.map((l) => (
                            <tr key={l.id}>
                                <td>{l.at}</td><td dir="ltr" style={{ textAlign: 'start' }}><b>{l.action}</b></td>
                                <td>{l.user ?? '-'}<small className="ta-muted" dir="ltr" style={{ display: 'block' }}>{l.ip}</small></td>
                                <td dir="ltr" style={{ textAlign: 'start' }}>{l.subject ?? '-'}</td>
                                <td dir="ltr" style={{ textAlign: 'start', maxWidth: 360, overflowWrap: 'anywhere', fontSize: 12.5 }}>{l.details ?? ''}</td>
                            </tr>
                        ))}
                    </tbody>
                </table>
            </div>
            <nav className="ta-pager" aria-label="Pages">
                {logs.links.map((l, i) => l.url ? <Link key={i} href={l.url} className={l.active ? 'is-active' : ''} preserveState>{l.label}</Link> : <span key={i}>{l.label}</span>)}
            </nav>
        </AdminLayout>
    );
}
