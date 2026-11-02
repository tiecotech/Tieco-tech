import AdminLayout from '@/components/admin/admin-layout';
import { Check, Txt } from '@/components/admin/fields';
import { useAdmin } from '@/lib/admin';
import { router } from '@inertiajs/react';
import { useState } from 'react';

type L = Record<string, string>;
interface M { id: number; name: L; role: L; is_visible: boolean; sort_order: number }
const blank = { name: { en: '', ar: '' }, role: { en: '', ar: '' }, is_visible: true, sort_order: 0 };

function Form({ member }: { member?: M }) {
    const { t, url } = useAdmin();
    const start = member ? { name: { en: member.name.en ?? '', ar: member.name.ar ?? '' }, role: { en: member.role.en ?? '', ar: member.role.ar ?? '' }, is_visible: member.is_visible, sort_order: member.sort_order } : blank;
    const [d, setD] = useState(start);
    const [errors, setErrors] = useState<Record<string, string>>({});
    const [saved, setSaved] = useState(false);
    const ok = () => { setErrors({}); setSaved(true); setTimeout(() => setSaved(false), 1800); if (!member) setD(blank); };
    const opts = { preserveScroll: true, onSuccess: ok, onError: (e: Record<string, string>) => setErrors(e) };
    const save = () => (member ? router.put(url(`/content/team/${member.id}`), d as never, opts) : router.post(url('/content/team'), d as never, opts));
    const del = () => { if (member && confirm(t('a.team.confirm_delete'))) router.delete(url(`/content/team/${member.id}`), { preserveScroll: true }); };

    return (
        <form className={`ta-rowform${member ? '' : ' new'}`} onSubmit={(e) => { e.preventDefault(); save(); }}>
            <Txt label="Name (EN)" value={d.name.en} onChange={(v) => setD({ ...d, name: { ...d.name, en: v } })} error={errors['name.en']} width={150} />
            <Txt label="الاسم (AR)" dir="rtl" value={d.name.ar} onChange={(v) => setD({ ...d, name: { ...d.name, ar: v } })} error={errors['name.ar']} width={150} />
            <Txt label="Role (EN)" value={d.role.en} onChange={(v) => setD({ ...d, role: { ...d.role, en: v } })} error={errors['role.en']} width={150} />
            <Txt label="المسمى (AR)" dir="rtl" value={d.role.ar} onChange={(v) => setD({ ...d, role: { ...d.role, ar: v } })} error={errors['role.ar']} width={150} />
            <Txt label="#" type="number" dir="ltr" value={String(d.sort_order)} onChange={(v) => setD({ ...d, sort_order: Number(v) || 0 })} width={70} />
            <Check label={t('a.svc.visible')} checked={d.is_visible} onChange={(v) => setD({ ...d, is_visible: v })} />
            <button className="ta-btn pri" type="submit">{saved ? `✓ ${t('a.saved')}` : member ? t('a.save') : t('a.team.add')}</button>
            {member && <button className="ta-btn" type="button" onClick={del}>{t('a.svc.delete')}</button>}
        </form>
    );
}

export default function Team({ members }: { members: M[] }) {
    const { t } = useAdmin();

    return (
        <AdminLayout title={t('a.nav.team')}>
            <p className="ta-lead">{t('a.team.lead')}</p>
            <div className="ta-card">
                {members.length === 0 && <p className="ta-muted">{t('a.team.none')}</p>}
                {members.map((m) => <Form key={m.id} member={m} />)}
                <h3>{t('a.team.add')}</h3>
                <Form />
            </div>
        </AdminLayout>
    );
}
