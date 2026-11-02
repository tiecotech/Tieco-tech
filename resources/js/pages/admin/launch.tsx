import AdminLayout from '@/components/admin/admin-layout';
import { Txt } from '@/components/admin/fields';
import { useAdmin } from '@/lib/admin';
import { router } from '@inertiajs/react';
import { useState } from 'react';

interface Props { live: boolean; forced: boolean; date: string; previewUrl: string }

export default function Launch({ live, forced, date, previewUrl }: Props) {
    const { t, url } = useAdmin();
    const [d, setD] = useState(date);
    const [errors, setErrors] = useState<Record<string, string>>({});
    const [saved, setSaved] = useState(false);
    const [copied, setCopied] = useState(false);

    const save = (nextLive: boolean) => router.put(url('/launch'), { live: nextLive, date: d || null }, {
        preserveScroll: true, onSuccess: () => { setErrors({}); setSaved(true); setTimeout(() => setSaved(false), 1800); }, onError: (e) => setErrors(e as Record<string, string>),
    });
    const toggle = () => {
        if (window.confirm(t(live ? 'a.launch.confirm_back' : 'a.launch.confirm_go'))) save(!live);
    };
    const rotate = () => {
        if (window.confirm(t('a.launch.confirm_rotate'))) router.post(url('/launch/preview-link'), {}, { preserveScroll: true });
    };
    const copy = async () => {
        try { await navigator.clipboard.writeText(previewUrl); setCopied(true); setTimeout(() => setCopied(false), 1800); } catch { /* clipboard blocked: the link is still selectable */ }
    };

    return (
        <AdminLayout title={t('a.nav.launch')}>
            <p className="ta-lead">{t('a.launch.lead')}</p>

            <section className="ta-card" style={{ display: 'grid', gap: 16, maxWidth: 640 }}>
                <div style={{ display: 'flex', alignItems: 'center', gap: 10, flexWrap: 'wrap' }}>
                    <span className="ta-muted">{t('a.launch.status')}</span>
                    <i className="ta-badge" style={{ background: live || forced ? '#16a34a' : '#d97706' }}>{t(live || forced ? 'a.launch.live' : 'a.launch.soon')}</i>
                </div>
                {forced && <p className="ta-warn">{t('a.launch.forced')}</p>}
                <div className="ta-row" style={{ flexWrap: 'wrap' }}>
                    <button className={`ta-btn${live ? '' : ' pri'}`} type="button" onClick={toggle}>{t(live ? 'a.launch.back' : 'a.launch.go')}</button>
                    <a className="ta-btn" href="/?coming-soon" target="_blank" rel="noreferrer">{t('a.launch.view')} ↗</a>
                </div>
            </section>

            <form className="ta-card" onSubmit={(e) => { e.preventDefault(); save(live); }} style={{ display: 'grid', gap: 14, maxWidth: 640, marginTop: 16 }}>
                <Txt label={t('a.launch.date')} type="date" dir="ltr" value={d} onChange={setD} error={errors.date} />
                <p className="ta-muted">{t('a.launch.date_hint')}</p>
                <div><button className="ta-btn pri" type="submit">{saved ? `✓ ${t('a.saved')}` : t('a.save')}</button></div>
            </form>

            <section className="ta-card" style={{ display: 'grid', gap: 12, maxWidth: 640, marginTop: 16 }}>
                <h2 style={{ margin: 0 }}>{t('a.launch.preview')}</h2>
                <p className="ta-muted">{t('a.launch.preview_lead')}</p>
                <input className="ta-in" dir="ltr" readOnly value={previewUrl} onFocus={(e) => e.currentTarget.select()} />
                <div className="ta-row" style={{ flexWrap: 'wrap' }}>
                    <button className="ta-btn" type="button" onClick={copy}>{copied ? `✓ ${t('a.launch.copied')}` : t('a.launch.copy')}</button>
                    <button className="ta-btn" type="button" onClick={rotate}>{t('a.launch.rotate')}</button>
                </div>
            </section>
        </AdminLayout>
    );
}
