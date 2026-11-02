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
    attachments: { id: number; name: string; size: number; mime: string }[];
    notes: { id: number; type: string; channel: string | null; body: string; author: string | null; at: string | null }[];
}
interface Props {
    request: Req;
    statuses: { id: number; key: string; name: string; color: string }[];
    staff: { id: number; name: string }[];
}

function Row({ label, children }: { label: string; children: ReactNode }) {
    return children ? <div className="ta-kv"><dt>{label}</dt><dd>{children}</dd></div> : null;
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
                            {current && <em className={`ta-badge ta-bg-${current.color}`}>{current.name}</em>}
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
