import AdminLayout from '@/components/admin/admin-layout';
import { Check, Txt } from '@/components/admin/fields';
import { useAdmin } from '@/lib/admin';
import { router } from '@inertiajs/react';
import { useState } from 'react';

interface U { id: number; name: string; email: string; role: string; is_active: boolean; locale: string; is_me: boolean }
type Errors = Record<string, string>;

function Row({ user, roles }: { user: U; roles: string[] }) {
    const { t, url } = useAdmin();
    const [d, setD] = useState({ name: user.name, role: user.role, is_active: user.is_active, password: '' });
    const [errors, setErrors] = useState<Errors>({});
    const [saved, setSaved] = useState(false);

    const save = () => router.put(url(`/users/${user.id}`), d, {
        preserveScroll: true,
        onSuccess: () => { setErrors({}); setD((x) => ({ ...x, password: '' })); setSaved(true); setTimeout(() => setSaved(false), 1800); },
        onError: (e) => setErrors(e as Errors),
    });

    return (
        <form className="ta-rowform" onSubmit={(e) => { e.preventDefault(); save(); }}>
            <div style={{ minWidth: 190 }}><b>{user.email}</b>{user.is_me && <small className="ta-muted"> ({t('a.users.you')})</small>}</div>
            <Txt label={t('a.users.name')} value={d.name} onChange={(v) => setD({ ...d, name: v })} error={errors.name} width={170} />
            <label className={`ta-fld${errors.role ? ' has-error' : ''}`}>
                <span>{t('a.users.role')}</span>
                <select className="ta-in" value={d.role} onChange={(e) => setD({ ...d, role: e.target.value })}>
                    {roles.map((r) => <option key={r} value={r}>{t(`a.role.${r}`)}</option>)}
                </select>
                {errors.role && <small>{errors.role}</small>}
            </label>
            <Check label={t('a.users.active')} checked={d.is_active} onChange={(v) => setD({ ...d, is_active: v })} />
            <Txt label={t('a.users.new_password')} type="password" dir="ltr" value={d.password} onChange={(v) => setD({ ...d, password: v })} error={errors.password} width={190} hint={t('a.users.keep')} />
            <button className="ta-btn pri" type="submit">{saved ? `✓ ${t('a.saved')}` : t('a.save')}</button>
        </form>
    );
}

export default function Users({ users, roles }: { users: U[]; roles: string[] }) {
    const { t, url } = useAdmin();
    const blank = { name: '', email: '', role: 'viewer', locale: 'en', password: '' };
    const [n, setN] = useState(blank);
    const [errors, setErrors] = useState<Errors>({});

    const create = () => router.post(url('/users'), n, { preserveScroll: true, onSuccess: () => { setN(blank); setErrors({}); }, onError: (e) => setErrors(e as Errors) });

    return (
        <AdminLayout title={t('a.nav.users')}>
            <p className="ta-lead">{t('a.users.lead')}</p>
            <div className="ta-card">
                <h2>{t('a.nav.users')}</h2>
                {users.map((u) => <Row key={`${u.id}-${u.role}-${u.is_active}`} user={u} roles={roles} />)}
            </div>
            <div className="ta-card">
                <h2>{t('a.users.add')}</h2>
                <form className="ta-rowform new" onSubmit={(e) => { e.preventDefault(); create(); }}>
                    <Txt label={t('a.users.name')} value={n.name} onChange={(v) => setN({ ...n, name: v })} error={errors.name} />
                    <Txt label="Email" type="email" dir="ltr" value={n.email} onChange={(v) => setN({ ...n, email: v })} error={errors.email} />
                    <label className="ta-fld"><span>{t('a.users.role')}</span>
                        <select className="ta-in" value={n.role} onChange={(e) => setN({ ...n, role: e.target.value })}>{roles.map((r) => <option key={r} value={r}>{t(`a.role.${r}`)}</option>)}</select>
                    </label>
                    <label className="ta-fld"><span>{t('a.users.language')}</span>
                        <select className="ta-in" value={n.locale} onChange={(e) => setN({ ...n, locale: e.target.value })}><option value="en">English</option><option value="ar">العربية</option></select>
                    </label>
                    <Txt label={t('a.users.password')} type="password" dir="ltr" value={n.password} onChange={(v) => setN({ ...n, password: v })} error={errors.password} hint={t('a.users.password_hint')} />
                    <button className="ta-btn pri" type="submit">{t('a.users.create')}</button>
                </form>
                <p className="ta-warn">{t('a.users.share_note')}</p>
            </div>
        </AdminLayout>
    );
}
