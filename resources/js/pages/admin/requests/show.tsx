import AdminLayout from '@/components/admin/admin-layout';
import { names, useAdmin } from '@/lib/admin';
import { formatMoney } from '@/lib/money';
import { Link, router } from '@inertiajs/react';
import { useState, type ReactNode } from 'react';

interface Item { id: number; kind: string; name: Record<string, string>; quantity: number; unit_price_minor: number | null; line_total_minor: number }
interface Req {
    id: number; number: string; locale: string; submitted_at: string | null; type: Record<string, string>; pages: number | null;
    status_id: number; assigned_to: number | null; customer_name: string; phone: string | null; email: string | null; country: string | null;
    preferred_contact: string | null; whatsapp_opt_in: boolean; description: string | null; goal: string | null; audience: string | null;
    expected_date: string | null; budget_minor: number | null; currency: string; subtotal_minor: number; discount_minor: number; total_minor: number;
    has_unpriced_items: boolean; items: Item[];
    source: 'website' | 'manual'; sold_at: string | null; final_price_minor: number | null; seller: string | null;
    received_minor: number; quote_total_minor: number | null; payments: Payment[];
    attachments: { id: number; name: string; size: number; mime: string }[];
    notes: { id: number; type: string; channel: string | null; body: string; author: string | null; at: string | null }[];
}
interface Props {
    request: Req;
    statuses: { id: number; key: string; name: string; color: string }[];
    staff: { id: number; name: string }[];
}

interface Payment { id: number; amount_minor: number; paid_at: string | null; method: string; note: string | null; author: string | null }

function Row({ label, children }: { label: string; children: ReactNode }) {
    return children ? <div className="ta-kv"><dt>{label}</dt><dd>{children}</dd></div> : null;
}

/** The closed deal: sale date, final price, payments received and what is still expected. */
function SaleCard({ r, manage }: { r: Req; manage: boolean }) {
    const { t, url, locale, currency } = useAdmin();
    const money = (n: number) => formatMoney(n, currency, locale);
    const factor = Math.pow(10, currency.decimals);
    const today = new Date().toISOString().slice(0, 10);
    const suggested = r.final_price_minor ?? r.quote_total_minor ?? (r.total_minor || null);
    const [sale, setSale] = useState({ sold_at: r.sold_at ?? today, final_price: suggested !== null ? String(suggested / factor) : '' });
    const [pay, setPay] = useState({ amount: '', paid_at: today, method: 'cash', note: '' });
    const [errors, setErrors] = useState<Record<string, string>>({});
    const remaining = r.final_price_minor !== null ? r.final_price_minor - r.received_minor : null;
    const opts = { preserveScroll: true, onError: (e: Record<string, string>) => setErrors(e), onSuccess: () => setErrors({}) };

    return (
        <section className="ta-card">
            <h2>{t('s.sale')}</h2>
            {r.sold_at ? (
                <dl className="ta-totals">
                    <div><dt>{t('s.sold_at')}</dt><dd dir="ltr">{r.sold_at}</dd></div>
                    <div><dt>{t('s.final_price')}</dt><dd><bdi>{money(r.final_price_minor ?? 0)}</bdi></dd></div>
                    <div><dt>{t('s.received')}</dt><dd><bdi>{money(r.received_minor)}</bdi></dd></div>
                    <div className="big"><dt>{t('s.remaining')}</dt><dd><bdi>{money(Math.max(0, remaining ?? 0))}</bdi></dd></div>
                    {r.seller && <div><dt>{t('s.seller')}</dt><dd>{r.seller}</dd></div>}
                </dl>
            ) : <p className="ta-muted">{t('s.not_sold')}</p>}
            {remaining !== null && remaining === 0 && <p className="ta-muted">✓ {t('s.paid_full')}</p>}
            {remaining !== null && remaining < 0 && <p className="ta-warn">{t('s.overpaid')}</p>}

            {manage && (
                <form style={{ display: 'grid', gap: 8 }} onSubmit={(e) => { e.preventDefault(); router.post(url(`/requests/${r.id}/sale`), sale, opts); }}>
                    <div className="ta-row">
                        <label className="ta-date"><span>{t('s.sold_at')}</span><input className="ta-in" type="date" value={sale.sold_at} onChange={(e) => setSale({ ...sale, sold_at: e.target.value })} /></label>
                        <label className="ta-date"><span>{t('s.final_price')} ({currency.code})</span><input className="ta-in" type="number" min="0" step="any" dir="ltr" value={sale.final_price} onChange={(e) => setSale({ ...sale, final_price: e.target.value })} /></label>
                    </div>
                    {(errors.sold_at || errors.final_price) && <p className="ta-warn">{errors.sold_at ?? errors.final_price}</p>}
                    <p className="ta-muted">{t('s.estimate')}: <bdi>{money(r.total_minor)}</bdi>{r.quote_total_minor !== null && <> · {t('s.quote')}: <bdi>{money(r.quote_total_minor)}</bdi></>}</p>
                    <div className="ta-row">
                        <button className="ta-btn pri" type="submit" disabled={!sale.final_price || !sale.sold_at}>{r.sold_at ? t('s.update_sale') : t('s.mark_sold')}</button>
                        {r.sold_at && (
                            <button className="ta-btn" type="button" onClick={() => confirm(t('s.clear_confirm')) && router.post(url(`/requests/${r.id}/sale`), { sold_at: null, final_price: null }, opts)}>
                                {t('s.clear_sale')}
                            </button>
                        )}
                    </div>
                </form>
            )}

            <h3>{t('s.payments')}</h3>
            {r.payments.length === 0 ? <p className="ta-muted">{t('s.no_payments')}</p> : (
                <ul className="ta-list plain">
                    {r.payments.map((p) => (
                        <li key={p.id}>
                            <span>
                                <b><bdi>{money(p.amount_minor)}</bdi></b>
                                <small><span dir="ltr">{p.paid_at}</span> · {t(`s.m.${p.method}`)}{p.author ? ` · ${p.author}` : ''}{p.note ? ` · ${p.note}` : ''}</small>
                            </span>
                            {manage && (
                                <button className="ta-btn" type="button" onClick={() => confirm(t('s.delete_confirm')) && router.delete(url(`/requests/${r.id}/payments/${p.id}`), { preserveScroll: true })}>
                                    {t('s.delete')}
                                </button>
                            )}
                        </li>
                    ))}
                </ul>
            )}

            {manage && (
                <form style={{ display: 'grid', gap: 8 }} onSubmit={(e) => {
                    e.preventDefault();
                    router.post(url(`/requests/${r.id}/payments`), pay, { ...opts, onSuccess: () => { setErrors({}); setPay({ ...pay, amount: '', note: '' }); } });
                }}>
                    <div className="ta-row">
                        <label className="ta-date"><span>{t('s.amount')} ({currency.code})</span><input className="ta-in" type="number" min="0" step="any" dir="ltr" value={pay.amount}
                            placeholder={remaining && remaining > 0 ? String(remaining / factor) : ''} onChange={(e) => setPay({ ...pay, amount: e.target.value })} /></label>
                        <label className="ta-date"><span>{t('s.paid_at')}</span><input className="ta-in" type="date" value={pay.paid_at} onChange={(e) => setPay({ ...pay, paid_at: e.target.value })} /></label>
                        <label className="ta-date"><span>{t('s.method')}</span>
                            <select className="ta-in" value={pay.method} onChange={(e) => setPay({ ...pay, method: e.target.value })}>
                                {['cash', 'transfer', 'card', 'other'].map((m) => <option key={m} value={m}>{t(`s.m.${m}`)}</option>)}
                            </select>
                        </label>
                    </div>
                    <input className="ta-in" placeholder={t('s.pay_note')} value={pay.note} onChange={(e) => setPay({ ...pay, note: e.target.value })} />
                    {(errors.amount || errors.paid_at) && <p className="ta-warn">{errors.amount ?? errors.paid_at}</p>}
                    <div><button className="ta-btn pri" type="submit" disabled={!pay.amount}>{t('s.add_payment')}</button></div>
                </form>
            )}
        </section>
    );
}

export default function Show({ request: r, statuses, staff }: Props) {
    const { t, url, locale, currency, can } = useAdmin();
    const money = (n: number) => formatMoney(n, currency, locale);
    const [statusId, setStatusId] = useState(String(r.status_id));
    const [statusNote, setStatusNote] = useState('');
    const [note, setNote] = useState({ type: 'note', channel: 'phone', body: '' });
    const manage = can('requests.manage');
    const current = statuses.find((s) => s.id === r.status_id);

    return (
        <AdminLayout title={`${t('a.nav.requests')} · ${r.number}`}>
            <Link className="ta-back" href={url('/requests')}>← {t('a.show.back')}</Link>

            <div className="ta-show">
                <div className="ta-col">
                    <section className="ta-card">
                        <div className="ta-card-head">
                            <h2 dir="ltr">{r.number}</h2>
                            <span className="ta-end">
                                {r.source === 'manual' && <em className="ta-badge ta-bg-slate">{t('s.src.manual')}</em>}
                                {r.sold_at && <em className="ta-badge ta-bg-green">{t('s.f.sold')}</em>}
                                {current && <em className={`ta-badge ta-bg-${current.color}`}>{current.name}</em>}
                            </span>
                        </div>
                        <dl className="ta-dl">
                            <Row label={t('a.show.project')}>{names(r.type, locale)}{r.pages ? ` · ${r.pages} ${t('a.show.pages')}` : ''}</Row>
                            <Row label={t('a.show.submitted')}><span dir="ltr">{r.submitted_at}</span></Row>
                            <Row label={t('a.show.expected')}><span dir="ltr">{r.expected_date}</span></Row>
                            <Row label={t('a.show.budget')}>{r.budget_minor !== null ? <bdi>{money(r.budget_minor)}</bdi> : null}</Row>
                        </dl>
                    </section>

                    <section className="ta-card">
                        <h2>{t('a.show.customer')}</h2>
                        <dl className="ta-dl">
                            <Row label={t('a.req.customer')}>{r.customer_name}</Row>
                            <Row label="Phone">{r.phone ? <a href={`tel:${r.phone}`} dir="ltr">{r.phone}</a> : null}</Row>
                            <Row label="WhatsApp">{r.phone && r.whatsapp_opt_in ? <a href={`https://wa.me/${r.phone.replace(/\D/g, '')}`} target="_blank" rel="noreferrer">{t('a.show.whatsapp_yes')} ↗</a> : null}</Row>
                            <Row label="Email">{r.email ? <a href={`mailto:${r.email}`} dir="ltr">{r.email}</a> : null}</Row>
                            <Row label={t('a.show.preferred')}>{r.preferred_contact ? t(`a.ch.${r.preferred_contact}`) : null}</Row>
                            <Row label="Country">{r.country}</Row>
                        </dl>
                        {(r.description || r.goal || r.audience) && (
                            <dl className="ta-dl block">
                                <Row label={t('a.show.description')}><p className="ta-pre">{r.description}</p></Row>
                                <Row label={t('a.show.goal')}><p className="ta-pre">{r.goal}</p></Row>
                                <Row label={t('a.show.audience')}><p className="ta-pre">{r.audience}</p></Row>
                            </dl>
                        )}
                    </section>

                    <section className="ta-card">
                        <h2>{t('a.show.items')}</h2>
                        <p className="ta-muted">{t('a.show.snapshot')}</p>
                        <div className="ta-tablewrap">
                            <table className="ta-table static">
                                <thead><tr><th>{t('a.show.item')}</th><th>{t('a.show.qty')}</th><th>{t('a.show.unit')}</th><th>{t('a.show.line')}</th></tr></thead>
                                <tbody>
                                    {r.items.map((i) => (
                                        <tr key={i.id} className={i.kind === 'discount' ? 'ta-discount' : ''}>
                                            <td>{names(i.name, locale)}</td><td>{i.quantity}</td>
                                            <td>{i.unit_price_minor === null ? t('a.show.on_request') : <bdi>{money(i.unit_price_minor)}</bdi>}</td>
                                            <td>{i.unit_price_minor === null ? '-' : <bdi>{money(i.line_total_minor)}</bdi>}</td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                        <dl className="ta-totals">
                            <div><dt>{t('a.show.subtotal')}</dt><dd><bdi>{money(r.subtotal_minor)}</bdi></dd></div>
                            <div><dt>{t('a.show.discount')}</dt><dd><bdi>−{money(r.discount_minor)}</bdi></dd></div>
                            <div className="big"><dt>{t('a.show.total')}</dt><dd><bdi>{money(r.total_minor)}</bdi></dd></div>
                        </dl>
                        {r.has_unpriced_items && <p className="ta-warn">{t('a.show.unpriced')}</p>}
                    </section>

                    <section className="ta-card">
                        <h2>{t('a.show.attachments')}</h2>
                        {r.attachments.length === 0 ? <p className="ta-muted">{t('a.show.no_files')}</p> : (
                            <ul className="ta-list plain">
                                {r.attachments.map((a) => (
                                    <li key={a.id}>
                                        <span><b dir="ltr">{a.name}</b><small>{Math.max(1, Math.round(a.size / 1024))} KB</small></span>
                                        <a className="ta-btn" href={url(`/requests/${r.id}/attachments/${a.id}`)}>{t('a.show.download')}</a>
                                    </li>
                                ))}
                            </ul>
                        )}
                    </section>
                </div>

                <div className="ta-col">
                    <SaleCard r={r} manage={manage} />

                    {manage && (
                        <section className="ta-card">
                            <h2>{t('a.show.status_title')}</h2>
                            <select className="ta-in" value={statusId} onChange={(e) => setStatusId(e.target.value)}>
                                {statuses.map((s) => <option key={s.id} value={s.id}>{s.name}</option>)}
                            </select>
                            <textarea className="ta-in" rows={2} placeholder={t('a.show.status_note_ph')} value={statusNote} onChange={(e) => setStatusNote(e.target.value)} />
                            <button className="ta-btn pri" type="button" disabled={Number(statusId) === r.status_id}
                                onClick={() => router.post(url(`/requests/${r.id}/status`), { request_status_id: statusId, note: statusNote }, { preserveScroll: true, onSuccess: () => setStatusNote('') })}>
                                {t('a.show.change_status')}
                            </button>
                            <h3>{t('a.show.assign')}</h3>
                            <select className="ta-in" value={r.assigned_to ?? ''}
                                onChange={(e) => router.post(url(`/requests/${r.id}/assign`), { assigned_to: e.target.value || null }, { preserveScroll: true })}>
                                <option value="">{t('a.show.unassigned')}</option>
                                {staff.map((u) => <option key={u.id} value={u.id}>{u.name}</option>)}
                            </select>
                        </section>
                    )}

                    <section className="ta-card">
                        <h2>{t('a.show.timeline')}</h2>
                        {manage && (
                            <form className="ta-noteform" onSubmit={(e) => {
                                e.preventDefault();
                                router.post(url(`/requests/${r.id}/notes`), { type: note.type, channel: note.type === 'contact' ? note.channel : null, body: note.body },
                                    { preserveScroll: true, onSuccess: () => setNote({ ...note, body: '' }) });
                            }}>
                                <div className="ta-row">
                                    <select className="ta-in" value={note.type} onChange={(e) => setNote({ ...note, type: e.target.value })}>
                                        <option value="note">{t('a.show.type_note')}</option>
                                        <option value="contact">{t('a.show.type_contact')}</option>
                                    </select>
                                    {note.type === 'contact' && (
                                        <select className="ta-in" value={note.channel} aria-label={t('a.show.channel')} onChange={(e) => setNote({ ...note, channel: e.target.value })}>
                                            {['phone', 'whatsapp', 'email', 'meeting'].map((c) => <option key={c} value={c}>{t(`a.ch.${c}`)}</option>)}
                                        </select>
                                    )}
                                </div>
                                <textarea className="ta-in" rows={3} placeholder={t('a.show.note_ph')} value={note.body} onChange={(e) => setNote({ ...note, body: e.target.value })} />
                                <button className="ta-btn pri" type="submit" disabled={!note.body.trim()}>{t('a.show.add_note')}</button>
                            </form>
                        )}
                        {r.notes.length === 0 && <p className="ta-muted">{t('a.show.empty_timeline')}</p>}
                        <ol className="ta-timeline">
                            {r.notes.map((n) => (
                                <li key={n.id} className={`is-${n.type}`}>
                                    <small>{n.author ?? '-'} · <span dir="ltr">{n.at}</span>{n.channel ? ` · ${t(`a.ch.${n.channel}`)}` : ''}</small>
                                    <p className="ta-pre">{n.body}</p>
                                </li>
                            ))}
                        </ol>
                    </section>
                </div>
            </div>
        </AdminLayout>
    );
}
