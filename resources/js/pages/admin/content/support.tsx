import AdminLayout from '@/components/admin/admin-layout';
import { Check, Txt } from '@/components/admin/fields';
import { useAdmin } from '@/lib/admin';
import { router } from '@inertiajs/react';
import { useRef, useState } from 'react';

interface Bank { enabled: boolean; bank_name: string; holder: string; account: string; branch: string }
interface Sham { enabled: boolean; holder: string; account: string; qr: string | null; qr_url: string | null }
interface S { enabled: boolean; bank: Bank; shamcash: Sham }

export default function SupportSettings({ support }: { support: S }) {
    const { t, url, locale } = useAdmin();
    const [d, setD] = useState({
        enabled: support.enabled,
        bank: { ...support.bank },
        shamcash: { enabled: support.shamcash.enabled, holder: support.shamcash.holder, account: support.shamcash.account },
    });
    const [errors, setErrors] = useState<Record<string, string>>({});
    const [saved, setSaved] = useState(false);
    const file = useRef<HTMLInputElement>(null);

    const save = () => router.put(url('/content/support'), d as never, {
        preserveScroll: true,
        onSuccess: () => { setErrors({}); setSaved(true); setTimeout(() => setSaved(false), 1800); },
        onError: (e) => setErrors(e as Record<string, string>),
    });
    const upload = (f: File | undefined) => f && router.post(url('/content/support/qr'), { qr: f }, {
        preserveScroll: true, forceFormData: true, onError: (e) => setErrors(e as Record<string, string>),
    });
    const bank = (k: keyof Bank) => (v: string | boolean) => setD({ ...d, bank: { ...d.bank, [k]: v } });
    const sham = (k: 'enabled' | 'holder' | 'account') => (v: string | boolean) => setD({ ...d, shamcash: { ...d.shamcash, [k]: v } });

    return (
        <AdminLayout title={t('sp.title')}>
            <p className="ta-lead">{t('sp.lead')}</p>

            <form onSubmit={(e) => { e.preventDefault(); save(); }} style={{ display: 'grid', gap: 18, maxWidth: 720 }}>
                <section className="ta-card" style={{ display: 'grid', gap: 10 }}>
                    <Check label={t('sp.enabled')} checked={d.enabled} onChange={(v) => setD({ ...d, enabled: v })} />
                    <p className="ta-muted">{t('sp.hidden')}</p>
                    {support.enabled && <a className="ta-btn" style={{ width: 'fit-content' }} href={`/${locale}/support`} target="_blank" rel="noreferrer">{t('sp.view')}</a>}
                </section>

                <section className="ta-card" style={{ display: 'grid', gap: 12 }}>
                    <h2>{t('sp.bank')}</h2>
                    <Check label={t('sp.bank_on')} checked={d.bank.enabled} onChange={bank('enabled')} />
                    <div className="ta-row" style={{ flexWrap: 'wrap', gap: 12 }}>
                        <Txt label={t('sp.bank_name')} value={d.bank.bank_name} onChange={bank('bank_name')} hint={t('sp.bank_name_hint')} error={errors['bank.bank_name']} />
                        <Txt label={t('sp.holder')} value={d.bank.holder} onChange={bank('holder')} error={errors['bank.holder']} />
                    </div>
                    <div className="ta-row" style={{ flexWrap: 'wrap', gap: 12 }}>
                        <Txt label={t('sp.account')} dir="ltr" value={d.bank.account} onChange={bank('account')} error={errors['bank.account']} />
                        <Txt label={t('sp.branch')} value={d.bank.branch} onChange={bank('branch')} error={errors['bank.branch']} />
                    </div>
                </section>

                <section className="ta-card" style={{ display: 'grid', gap: 12 }}>
                    <h2>{t('sp.sham')}</h2>
                    <Check label={t('sp.sham_on')} checked={d.shamcash.enabled} onChange={sham('enabled')} />
                    <div className="ta-row" style={{ flexWrap: 'wrap', gap: 12 }}>
                        <Txt label={t('sp.holder')} value={d.shamcash.holder} onChange={sham('holder')} error={errors['shamcash.holder']} />
                        <Txt label={t('sp.sham_account')} dir="ltr" value={d.shamcash.account} onChange={sham('account')} error={errors['shamcash.account']} />
                    </div>
                    <div style={{ display: 'flex', gap: 16, alignItems: 'center', flexWrap: 'wrap' }}>
                        <div style={{ width: 132, height: 132, borderRadius: 14, border: '1px dashed var(--line)', display: 'grid', placeItems: 'center', overflow: 'hidden', background: '#fff' }}>
                            {support.shamcash.qr_url
                                ? <img src={support.shamcash.qr_url} alt="QR" style={{ width: '100%', height: '100%', objectFit: 'contain' }} />
                                : <span className="ta-muted">{t('sp.qr')}</span>}
                        </div>
                        <div style={{ display: 'grid', gap: 8 }}>
                            <input ref={file} type="file" accept="image/png,image/jpeg,image/webp" hidden onChange={(e) => upload(e.target.files?.[0])} />
                            <button className="ta-btn" type="button" onClick={() => file.current?.click()}>{support.shamcash.qr_url ? t('sp.qr_replace') : t('sp.qr_upload')}</button>
                            {support.shamcash.qr_url && <button className="ta-btn" type="button" onClick={() => router.delete(url('/content/support/qr'), { preserveScroll: true })}>{t('sp.qr_remove')}</button>}
                            <small className="ta-muted" style={{ maxWidth: 320 }}>{t('sp.qr_hint')}</small>
                            {errors.qr && <p className="ta-warn">{errors.qr}</p>}
                        </div>
                    </div>
                </section>

                <div><button className="ta-btn pri" type="submit">{saved ? `✓ ${t('a.saved')}` : t('a.save')}</button></div>
            </form>
        </AdminLayout>
    );
}
