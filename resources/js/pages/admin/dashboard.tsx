import AdminLayout from '@/components/admin/admin-layout';
import { formatMoney } from '@/lib/money';
import { names, useAdmin } from '@/lib/admin';
import { Link } from '@inertiajs/react';

interface Row { id: number; number: string; customer: string; type: Record<string, string>; total_minor: number; status: { name: string; color: string }; at: string | null }
interface Sales {
    deals_total: number; sold_total_minor: number; received_total_minor: number; expected_minor: number;
    month_deals: number; month_sold_minor: number; month_received_minor: number;
    months: { month: string; sold_minor: number; received_minor: number; deals: number }[];
    due: { id: number; number: string; customer: string; sold_at: string | null; final_minor: number; received_minor: number; remaining_minor: number }[];
}
interface Props {
    kpis: { total: number; new: number; in_review: number; quote_sent: number; accepted: number; rejected: number };
    statuses: { key: string; name: string; color: string; count: number }[];
    series: { date: string; count: number }[];
    latest: Row[];
    sales: Sales;
    activity: { id: number; action: string; user: string | null; at: string | null }[];
}

export default function Dashboard({ kpis, statuses, series, latest, activity, sales }: Props) {
    const { t, url, locale, currency, can } = useAdmin();
    const m = (n: number) => formatMoney(n, currency, locale);
    const maxMonth = Math.max(1, ...sales.months.map((x) => Math.max(x.sold_minor, x.received_minor)));
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

            <h2 style={{ margin: '22px 0 10px' }}>{t('s.d.title')}</h2>
            <div className="ta-kpis">
                <Link href={url('/requests?deal=sold')} className="ta-kpi ta-c-green"><span>{t('s.d.sold')} · {t('s.d.month')}</span><b><bdi>{m(sales.month_sold_minor)}</bdi></b><small>{sales.month_deals} {t('s.d.deals')}</small></Link>
                <div className="ta-kpi ta-c-blue"><span>{t('s.d.received')} · {t('s.d.month')}</span><b><bdi>{m(sales.month_received_minor)}</bdi></b></div>
                <Link href={url('/requests?deal=due')} className="ta-kpi ta-c-amber"><span>{t('s.d.expected')}</span><b><bdi>{m(sales.expected_minor)}</bdi></b><small>{sales.due.length} {t('s.d.deals')}</small></Link>
                <div className="ta-kpi ta-c-all"><span>{t('s.d.sold')} · {t('s.d.all_time')}</span><b><bdi>{m(sales.sold_total_minor)}</bdi></b><small>{sales.deals_total} {t('s.d.deals')}</small></div>
                <div className="ta-kpi ta-c-indigo"><span>{t('s.d.received')} · {t('s.d.all_time')}</span><b><bdi>{m(sales.received_total_minor)}</bdi></b></div>
            </div>

            <div className="ta-grid2">
                <section className="ta-card">
                    <h2>{t('s.d.months')}</h2>
                    <ul className="ta-bars">
                        {sales.months.map((x) => (
                            <li key={x.month} title={`${x.deals} ${t('s.d.deals')}`} style={{ gridTemplateColumns: '70px 1fr auto' }}>
                                <span dir="ltr">{x.month}</span>
                                <div style={{ display: 'grid', gap: 3, height: 'auto', background: 'none' }}>
                                    <i className="ta-bg-green" style={{ width: `${(x.sold_minor / maxMonth) * 100}%`, height: 7 }} />
                                    <i className="ta-bg-blue" style={{ width: `${(x.received_minor / maxMonth) * 100}%`, height: 7 }} />
                                </div>
                                <b><bdi>{m(x.sold_minor)}</bdi><small style={{ display: 'block' }}><bdi>{m(x.received_minor)}</bdi></small></b>
                            </li>
                        ))}
                    </ul>
                    <p className="ta-muted"><i className="ta-badge ta-bg-green">{t('s.d.sold')}</i> <i className="ta-badge ta-bg-blue">{t('s.d.received')}</i></p>
                </section>
                <section className="ta-card">
                    <div className="ta-card-head"><h2>{t('s.d.due')}</h2><Link href={url('/requests?deal=due')}>{t('a.dash.view_all')}</Link></div>
                    {sales.due.length === 0 ? <p className="ta-muted">{t('s.d.no_due')}</p> : (
                        <ul className="ta-list">
                            {sales.due.map((d) => (
                                <li key={d.id}>
                                    <Link href={url(`/requests/${d.id}`)}>
                                        <span><b>{d.customer}</b><small dir="ltr">{d.number} · {d.sold_at}</small></span>
                                        <span className="ta-end"><bdi>{m(d.remaining_minor)}</bdi><small>/ <bdi>{m(d.final_minor)}</bdi></small></span>
                                    </Link>
                                </li>
                            ))}
                        </ul>
                    )}
                </section>
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
