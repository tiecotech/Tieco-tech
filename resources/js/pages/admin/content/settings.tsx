import AdminLayout from '@/components/admin/admin-layout';
import { Txt } from '@/components/admin/fields';
import { useAdmin } from '@/lib/admin';
import { router } from '@inertiajs/react';
import { useState } from 'react';

interface C { email: string; phone: string; whatsapp: string; address: string }

export default function Settings({ contact }: { contact: C }) {
    const { t, url } = useAdmin();
    const [d, setD] = useState(contact);
    const [errors, setErrors] = useState<Record<string, string>>({});
    const [saved, setSaved] = useState(false);
    const save = () => router.put(url('/content/settings'), d as never, {
        preserveScroll: true, onSuccess: () => { setErrors({}); setSaved(true); setTimeout(() => setSaved(false), 1800); }, onError: (e) => setErrors(e as Record<string, string>),
    });

    return (
        <AdminLayout title={t('a.nav.settings')}>
            <p className="ta-lead">{t('a.set.lead')}</p>
            <form className="ta-card" onSubmit={(e) => { e.preventDefault(); save(); }} style={{ display: 'grid', gap: 14, maxWidth: 640 }}>
                <Txt label="Email" type="email" dir="ltr" value={d.email} onChange={(v) => setD({ ...d, email: v })} error={errors.email} />
                <Txt label={t('a.set.phone')} dir="ltr" value={d.phone} onChange={(v) => setD({ ...d, phone: v })} error={errors.phone} hint="+963..." />
                <Txt label="WhatsApp" dir="ltr" value={d.whatsapp} onChange={(v) => setD({ ...d, whatsapp: v })} error={errors.whatsapp} hint="+963992355844" />
                <Txt label={t('a.set.address')} value={d.address} onChange={(v) => setD({ ...d, address: v })} error={errors.address} />
                <div><button className="ta-btn pri" type="submit">{saved ? `✓ ${t('a.saved')}` : t('a.save')}</button></div>
            </form>
        </AdminLayout>
    );
}
