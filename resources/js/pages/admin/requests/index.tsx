import AdminLayout from '@/components/admin/admin-layout';
import { names, useAdmin } from '@/lib/admin';
import { formatMoney } from '@/lib/money';
import { Link, router } from '@inertiajs/react';
import { useState, type FormEvent } from 'react';

interface Row {
    id: number; number: string; date: string | null; customer: string; phone: string | null; type: Record<string, string>;
    total_minor: number; has_unpriced_items: boolean; status: { name: string; color: string }; assignee: string | null;
}
interface Props {
    requests: { data: Row[]; links: { url: string | null; label: string; active: boolean }[]; total: number };
    filters: Record<string, string | undefined>;
    statuses: { key: string; name: string }[];
    types: { id: number; name: Record<string, string> }[];
}

export default function Requests({ requests, filters, statuses, types }: Props) {
    const { t, url, locale, currency, can } = useAdmin();
    const [f, setF] = useState({ q: filters.q ?? '', status: filters.status ?? '', type: filters.type ?? '', from: filters.from ?? '', to: filters.to ?? '' });
    const clean = (o: Record<string, string>) => Object.fromEntries(Object.entries(o).filter(([, v]) => v !== ''));

    const submit = (e: FormEvent) => {
        e.preventDefault();
        router.get(url('/requests'), clean(f), { preserveState: true });
    };
    const reset = () => {
        setF({ q: '', status: '', type: '', from: '', to: '' });
        router.get(url('/requests'));
    };
    const exportUrl = `${url('/requests/export')}?${new URLSearchParams(clean(f)).toString()}`;

    return (
        <AdminLayout title={t('a.req.title')}>
            <form className="ta-filters" onSubmit={submit}>
                <input className="ta-in grow" type="search" placeholder={t('a.req.search')} value={f.q} onChange={(e) => setF({ ...f, q: e.target.value })} />
                <select className="ta-in" value={f.status} onChange={(e) => setF({ ...f, status: e.target.value })} aria-label={t('a.req.status')}>
                    <option value="">{t('a.req.all_statuses')}</option>
                    {statuses.map((s) => <option key={s.key} value={s.key}>{s.name}</option>)}
                </select>
                <select className="ta-in" value={f.type} onChange={(e) => setF({ ...f, type: e.target.value })} aria-label={t('a.req.type')}>
                    <option value="">{t('a.req.all_types')}</option>
                    {types.map((x) => <option key={x.id} value={x.id}>{names(x.name, locale)}</option>)}
                </select>
                <label className="ta-date"><span>{t('a.req.from')}</span><input className="ta-in" type="date" value={f.from} onChange={(e) => setF({ ...f, from: e.target.value })} /></label>
                <label className="ta-date"><span>{t('a.req.to')}</span><input className="ta-in" type="date" value={f.to} onChange={(e) => setF({ ...f, to: e.target.value })} /></label>
                <button className="ta-btn pri" type="submit">{t('a.req.filter')}</button>
                <button className="ta-btn" type="button" onClick={reset}>{t('a.req.reset')}</button>
                {can('requests.export') && <a className="ta-btn" href={exportUrl}>{t('a.req.export')}</a>}
            </form>

            <p className="ta-muted">{requests.total} {t('a.req.found')}</p>

            <div className="ta-tablewrap">
                <table className="ta-table">
                    <thead>
                        <tr>
                            <th>{t('a.req.number')}</th><th>{t('a.req.customer')}</th><th>{t('a.req.type')}</th>
                            <th>{t('a.req.total')}</th><th>{t('a.req.status')}</th><th>{t('a.req.assignee')}</th><th>{t('a.req.date')}</th>
                        </tr>
                    </thead>
                    <tbody>
                        {requests.data.length === 0 && <tr><td colSpan={7} className="ta-muted">{t('a.req.none')}</td></tr>}
                        {requests.data.map((r) => (
                            <tr key={r.id} onClick={() => router.visit(url(`/requests/${r.id}`))}>
                                <td><Link href={url(`/requests/${r.id}`)} dir="ltr">{r.number}</Link></td>
                                <td><b>{r.customer}</b><small dir="ltr">{r.phone}</small></td>
                                <td>{names(r.type, locale)}</td>
                                <td><bdi>{formatMoney(r.total_minor, currency, locale)}</bdi>{r.has_unpriced_items && <abbr title={t('a.show.unpriced')}> *</abbr>}</td>
                                <td><em className={`ta-badge ta-bg-${r.status.color}`}>{r.status.name}</em></td>
                                <td>{r.assignee ?? '-'}</td>
                                <td dir="ltr">{r.date}</td>
                            </tr>
                        ))}
                    </tbody>
                </table>
            </div>

            <nav className="ta-pager" aria-label="Pages">
                {requests.links.map((l, i) => (
                    l.url
                        ? <Link key={i} href={l.url} className={l.active ? 'is-active' : ''} preserveState>{l.label}</Link>
                        : <span key={i}>{l.label}</span>
                ))}
            </nav>
        </AdminLayout>
    );
}
