import AdminLayout from '@/components/admin/admin-layout';
import { Check, Txt } from '@/components/admin/fields';
import { useAdmin } from '@/lib/admin';
import { router } from '@inertiajs/react';
import { useState } from 'react';

interface C { email: string; phone: string; whatsapp: string; address: string }
interface A { email: string; enabled: boolean; mail_ready: boolean; mailer: string }

export default function Settings({ contact, alerts }: { contact: C; alerts: A }) {
    const { t, url } = useAdmin();
    const [d, setD] = useState(contact);
    const [errors, setErrors] = useState<Record<string, string>>({});
    const [saved, setSaved] = useState(false);
    const save = () => router.put(url('/content/settings'), d as never, {
        preserveScroll: true, onSuccess: () => { setErrors({}); setSaved(true); setTimeout(() => setSaved(false), 1800); }, onError: (e) => setErrors(e as Record<string, string>),
    });

    const [al, setAl] = useState({ email: alerts.email, enabled: alerts.enabled });
    const [alErrors, setAlErrors] = useState<Record<string, string>>({});
    const [alSaved, setAlSaved] = useState(false);
    const [testMsg, setTestMsg] = useState<{ ok: boolean; text: string } | null>(null);
    const [testing, setTesting] = useState(false);
    const saveAlerts = () => router.put(url('/content/settings/alerts'), al as never, {
        preserveScroll: true, onSuccess: () => { setAlErrors({}); setAlSaved(true); setTimeout(() => setAlSaved(false), 1800); }, onError: (e) => setAlErrors(e as Record<string, string>),
    });
    const test = () => {
        setTesting(true);
        setTestMsg(null);
        router.post(url('/content/settings/alerts/test'), {}, {
            preserveScroll: true,
            onSuccess: () => setTestMsg({ ok: true, text: t('s.alert_sent') }),
            onError: (e) => setTestMsg({ ok: false, text: (e as Record<string, string>).test ?? 'Error' }),
            onFinish: () => setTesting(false),
        });
    };

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

            <form className="ta-card" onSubmit={(e) => { e.preventDefault(); saveAlerts(); }} style={{ display: 'grid', gap: 14, maxWidth: 640, marginTop: 18 }}>
                <h2>{t('s.alert_title')}</h2>
                <p className="ta-muted">{t('s.alert_lead')}</p>
                {!alerts.mail_ready && <p className="ta-warn">{t('s.alert_mailer_off').replace(':mailer', alerts.mailer)}</p>}
                <Txt label={t('s.alert_email')} type="email" dir="ltr" value={al.email} onChange={(v) => setAl({ ...al, email: v })} error={alErrors.email} hint="you@gmail.com" />
                <Check label={t('s.alert_enabled')} checked={al.enabled} onChange={(v) => setAl({ ...al, enabled: v })} />
                <div className="ta-row" style={{ gap: 8, flexWrap: 'wrap' }}>
                    <button className="ta-btn pri" type="submit">{alSaved ? `✓ ${t('a.saved')}` : t('a.save')}</button>
                    <button className="ta-btn" type="button" disabled={testing} onClick={test}>{t('s.alert_test')}</button>
                </div>
                {testMsg && <p className={testMsg.ok ? 'ta-muted' : 'ta-warn'}>{testMsg.text}</p>}
            </form>
        </AdminLayout>
    );
}
