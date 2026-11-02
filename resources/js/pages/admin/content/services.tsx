import AdminLayout from '@/components/admin/admin-layout';
import { useAdmin } from '@/lib/admin';
import { Link } from '@inertiajs/react';

interface S { id: number; slug: string; name: string; is_visible: boolean; sort_order: number }

export default function Services({ services }: { services: S[] }) {
    const { t, url } = useAdmin();

    return (
        <AdminLayout title={t('a.nav.services_c')}>
            <div className="ta-row" style={{ justifyContent: 'space-between' }}>
                <p className="ta-lead">{t('a.svc.lead')}</p>
                <Link className="ta-btn pri" href={url('/content/services/new')}>{t('a.svc.new')}</Link>
            </div>
            <div className="ta-tablewrap">
                <table className="ta-table">
                    <thead><tr><th>#</th><th>{t('a.svc.name')}</th><th>URL</th><th>{t('a.svc.visible')}</th><th /></tr></thead>
                    <tbody>
                        {services.map((s) => (
                            <tr key={s.id}>
                                <td>{s.sort_order}</td><td><b>{s.name}</b></td><td dir="ltr" style={{ textAlign: 'start' }}>/services/{s.slug}</td>
                                <td><i className={`ta-badge ${s.is_visible ? 'ta-bg-green' : 'ta-bg-slate'}`}>{s.is_visible ? t('a.svc.shown') : t('a.svc.hidden')}</i></td>
                                <td><Link className="ta-btn" href={url(`/content/services/${s.id}/edit`)}>{t('a.svc.edit')}</Link></td>
                            </tr>
                        ))}
                    </tbody>
                </table>
            </div>
        </AdminLayout>
    );
}
