import AdminLayout from '@/components/admin/admin-layout';
import { useAdmin } from '@/lib/admin';
import { Link, router } from '@inertiajs/react';

interface Msg { id: number; name: string; email: string | null; phone: string | null; subject: string; body: string; preferred_contact: string | null; locale: string; status: string; date: string | null }

export default function Show({ message: m }: { message: Msg }) {
    const { t, url, can } = useAdmin();
    const set = (status: string) => router.post(url(`/messages/${m.id}/status`), { status }, { preserveScroll: true });
    const del = () => { if (confirm(t('a.msg.confirm_delete'))) router.delete(url(`/messages/${m.id}`)); };

    return (
        <AdminLayout title={m.subject}>
            <Link className="ta-back" href={url('/messages')}>← {t('a.msg.title')}</Link>
            <div className="ta-show">
                <div className="ta-card">
                    <h2>{m.subject}</h2>
                    <p className="ta-pre">{m.body}</p>
                </div>
                <div className="ta-col">
                    <div className="ta-card">
                        <h2>{t('a.show.customer')}</h2>
                        <dl className="ta-dl">
                            <div className="ta-kv"><dt>{t('a.msg.from')}</dt><dd>{m.name}</dd></div>
                            {m.email && <div className="ta-kv"><dt>{t('a.msg.email')}</dt><dd dir="ltr"><a href={`mailto:${m.email}`}>{m.email}</a></dd></div>}
                            {m.phone && (
                                <div className="ta-kv"><dt>{t('a.msg.phone')}</dt><dd dir="ltr">
                                    <a href={`tel:${m.phone}`}>{m.phone}</a>
                                    {' · '}<a href={`https://wa.me/${m.phone.replace(/\D/g, '')}`} target="_blank" rel="noopener noreferrer">WhatsApp</a>
                                </dd></div>
                            )}
                            {m.preferred_contact && <div className="ta-kv"><dt>{t('a.show.preferred')}</dt><dd>{t(`a.ch.${m.preferred_contact}`)}</dd></div>}
                            <div className="ta-kv"><dt>{t('a.req.date')}</dt><dd>{m.date}</dd></div>
                            <div className="ta-kv"><dt>{t('a.msg.language')}</dt><dd>{m.locale === 'ar' ? 'العربية' : 'English'}</dd></div>
                        </dl>
                    </div>
                    {can('messages.manage') && (
                        <div className="ta-card">
                            <h2>{t('a.req.status')}: {t(`a.msg.s.${m.status}`)}</h2>
                            <div className="ta-row" style={{ flexWrap: 'wrap' }}>
                                {['read', 'replied', 'archived', 'new'].filter((s) => s !== m.status).map((s) => <button key={s} type="button" className="ta-btn" onClick={() => set(s)}>{t(`a.msg.mark.${s}`)}</button>)}
                            </div>
                            <p style={{ marginTop: 16 }}><button type="button" className="ta-btn" onClick={del}>{t('a.msg.delete')}</button></p>
                        </div>
                    )}
                </div>
            </div>
        </AdminLayout>
    );
}
