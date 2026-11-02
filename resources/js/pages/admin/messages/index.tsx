import AdminLayout from '@/components/admin/admin-layout';
import { useAdmin } from '@/lib/admin';
import { Link, router } from '@inertiajs/react';
import { useState, type FormEvent } from 'react';

interface Row { id: number; name: string; subject: string; contact: string | null; status: string; date: string | null }
interface Props {
    messages: { data: Row[]; links: { url: string | null; label: string; active: boolean }[]; total: number };
    filters: { q: string; status: string };
    counts: Record<string, number>;
}
const COLOR: Record<string, string> = { new: 'ta-bg-blue', read: 'ta-bg-slate', replied: 'ta-bg-green', archived: 'ta-bg-amber' };

export default function Messages({ messages, filters, counts }: Props) {
    const { t, url } = useAdmin();
    const [q, setQ] = useState(filters.q);
    const [status, setStatus] = useState(filters.status);
    const go = (e?: FormEvent) => { e?.preventDefault(); router.get(url('/messages'), Object.fromEntries(Object.entries({ q, status }).filter(([, v]) => v)), { preserveState: true }); };

    return (
        <AdminLayout title={t('a.msg.title')}>
            <form className="ta-filters" onSubmit={go}>
                <input className="ta-in grow" type="search" placeholder={t('a.msg.search')} value={q} onChange={(e) => setQ(e.target.value)} />
                <select className="ta-in" value={status} onChange={(e) => setStatus(e.target.value)} aria-label={t('a.req.status')}>
                    <option value="">{t('a.req.all_statuses')}</option>
                    {['new', 'read', 'replied', 'archived'].map((s) => <option key={s} value={s}>{t(`a.msg.s.${s}`)} ({counts[s] ?? 0})</option>)}
                </select>
                <button className="ta-btn pri" type="submit">{t('a.req.filter')}</button>
            </form>
            <p className="ta-muted">{messages.total} {t('a.req.found')}</p>
            <div className="ta-tablewrap">
                <table className="ta-table">
                    <thead><tr><th>{t('a.msg.subject')}</th><th>{t('a.msg.from')}</th><th>{t('a.msg.contact')}</th><th>{t('a.req.status')}</th><th>{t('a.req.date')}</th></tr></thead>
                    <tbody>
                        {messages.data.length === 0 && <tr><td colSpan={5} className="ta-muted">{t('a.msg.none')}</td></tr>}
                        {messages.data.map((m) => (
                            <tr key={m.id}>
                                <td><Link href={url(`/messages/${m.id}`)} style={{ fontWeight: m.status === 'new' ? 700 : 400 }}>{m.subject}</Link></td>
                                <td>{m.name}</td>
                                <td dir="ltr" style={{ textAlign: 'start' }}>{m.contact ?? '-'}</td>
                                <td><i className={`ta-badge ${COLOR[m.status] ?? ''}`}>{t(`a.msg.s.${m.status}`)}</i></td>
                                <td>{m.date}</td>
                            </tr>
                        ))}
                    </tbody>
                </table>
            </div>
            <nav className="ta-pager" aria-label="Pages">
                {messages.links.map((l, i) => l.url ? <Link key={i} href={l.url} className={l.active ? 'is-active' : ''} preserveState>{l.label}</Link> : <span key={i}>{l.label}</span>)}
            </nav>
        </AdminLayout>
    );
}
