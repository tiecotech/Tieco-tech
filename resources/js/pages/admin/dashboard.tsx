import AdminLayout from '@/components/admin/admin-layout';
import { formatMoney } from '@/lib/money';
import { names, useAdmin } from '@/lib/admin';
import { Link } from '@inertiajs/react';

interface Row { id: number; number: string; customer: string; type: Record<string, string>; total_minor: number; status: { name: string; color: string }; at: string | null }
interface Props {
    kpis: { total: number; new: number; in_review: number; quote_sent: number; accepted: number; rejected: number };
    statuses: { key: string; name: string; color: string; count: number }[];
    series: { date: string; count: number }[];
    latest: Row[];
    activity: { id: number; action: string; user: string | null; at: string | null }[];
}

export default function Dashboard({ kpis, statuses, series, latest, activity }: Props) {
    const { t, url, locale, currency, can } = useAdmin();
    const max = Math.max(1, ...series.map((s) => s.count));
    const maxStatus = Math.max(1, ...statuses.map((s) => s.count));
    const cards: [string, number, string][] = [
        ['a.dash.total', kpis.total, 'all'], ['a.dash.new', kpis.new, 'blue'], ['a.dash.in_review', kpis.in_review, 'amber'],
        ['a.dash.quote_sent', kpis.quote_sent, 'indigo'], ['a.dash.accepted', kpis.accepted, 'green'], ['a.dash.rejected', kpis.rejected, 'red'],
    ];

    return (
        <AdminLayout title={t('a.dash.title')}>
            <div className="ta-kpis">
                {cards.map(([key, value, color]) => (
                    <Link key={key} href={url('/requests')} className={`ta-kpi ta-c-${color}`}>
                        <span>{t(key)}</span><b>{value}</b>
                    </Link>
                ))}
            </div>

            <div className="ta-grid2">
                <section className="ta-card">
                    <h2>{t('a.dash.last14')}</h2>
                    <svg className="ta-chart" viewBox={`0 0 ${series.length * 24} 120`} role="img" aria-label={t('a.dash.last14')}>
                        {series.map((s, i) => {
                            const h = (s.count / max) * 90;

                            return (
                                <g key={s.date}>
                                    <rect x={i * 24 + 4} y={100 - h} width="16" height={Math.max(h, 2)} rx="4" className={s.count ? 'on' : ''}><title>{`${s.date}: ${s.count}`}</title></rect>
                                    {(i % 3 === 0 || i === series.length - 1) && <text x={i * 24 + 12} y="116" textAnchor="middle">{s.date.slice(5)}</text>}
                                </g>
                            );
                        })}
                    </svg>
                </section>
                <section className="ta-card">
                    <h2>{t('a.dash.by_status')}</h2>
                    <ul className="ta-bars">
                        {statuses.map((s) => (
                            <li key={s.key}>
                                <span>{s.name}</span>
                                <div><i className={`ta-bg-${s.color}`} style={{ width: `${(s.count / maxStatus) * 100}%` }} /></div>
                                <b>{s.count}</b>
                            </li>
                        ))}
                    </ul>
                </section>
            </div>

            <div className="ta-grid2">
                <section className="ta-card">
                    <div className="ta-card-head"><h2>{t('a.dash.latest')}</h2><Link href={url('/requests')}>{t('a.dash.view_all')}</Link></div>
                    {latest.length === 0 ? <p className="ta-muted">{t('a.dash.empty')}</p> : (
                        <ul className="ta-list">
                            {latest.map((r) => (
                                <li key={r.id}>
                                    <Link href={url(`/requests/${r.id}`)}>
                                        <span><b>{r.customer}</b><small dir="ltr">{r.number} · {names(r.type, locale)}</small></span>
                                        <span className="ta-end"><bdi>{formatMoney(r.total_minor, currency, locale)}</bdi><em className={`ta-badge ta-bg-${r.status.color}`}>{r.status.name}</em></span>
                                    </Link>
                                </li>
                            ))}
                        </ul>
                    )}
                </section>
                {can('activity.view') && (
                    <section className="ta-card">
                        <h2>{t('a.dash.activity')}</h2>
                        <ul className="ta-list plain">
                            {activity.map((a) => <li key={a.id}><span><b dir="ltr">{a.action}</b><small>{a.user ?? '-'} · {a.at}</small></span></li>)}
                        </ul>
                    </section>
                )}
            </div>
        </AdminLayout>
    );
}
