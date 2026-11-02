import AdminLayout from '@/components/admin/admin-layout';
import { Check, Txt } from '@/components/admin/fields';
import { useAdmin } from '@/lib/admin';
import { router } from '@inertiajs/react';
import { useState } from 'react';

type L = Record<string, string>;
interface P { id: number; slug: string; title: L; body: L | null; is_published: boolean }

function Editor({ page }: { page: P }) {
    const { t, url } = useAdmin();
    const [d, setD] = useState({ title: { en: page.title.en ?? '', ar: page.title.ar ?? '' }, body: { en: page.body?.en ?? '', ar: page.body?.ar ?? '' }, is_published: page.is_published });
    const [errors, setErrors] = useState<Record<string, string>>({});
    const [saved, setSaved] = useState(false);
    const save = () => router.put(url(`/content/pages/${page.id}`), d as never, {
        preserveScroll: true, onSuccess: () => { setErrors({}); setSaved(true); setTimeout(() => setSaved(false), 1800); }, onError: (e) => setErrors(e as Record<string, string>),
    });
    const legal = page.slug !== 'about';

    return (
        <form className="ta-card" onSubmit={(e) => { e.preventDefault(); save(); }} style={{ display: 'grid', gap: 12 }}>
            <div className="ta-card-head"><h2>{page.slug}</h2><i className={`ta-badge ${d.is_published ? 'ta-bg-green' : 'ta-bg-slate'}`}>{d.is_published ? t('a.svc.shown') : t('a.svc.hidden')}</i></div>
            {legal && <p className="ta-warn">{t('a.pg.legal_warning')}</p>}
            <div className="ta-row" style={{ flexWrap: 'wrap' }}>
                <Txt label="Title (English)" value={d.title.en} onChange={(v) => setD({ ...d, title: { ...d.title, en: v } })} error={errors['title.en']} />
                <Txt label="العنوان (العربية)" dir="rtl" value={d.title.ar} onChange={(v) => setD({ ...d, title: { ...d.title, ar: v } })} error={errors['title.ar']} />
            </div>
            <div className="ta-row" style={{ flexWrap: 'wrap' }}>
                <Txt label="English" area={9} value={d.body.en} onChange={(v) => setD({ ...d, body: { ...d.body, en: v } })} error={errors['body.en']} />
                <Txt label="العربية" dir="rtl" area={9} value={d.body.ar} onChange={(v) => setD({ ...d, body: { ...d.body, ar: v } })} error={errors['body.ar']} />
            </div>
            <p className="ta-muted">{t('a.svc.para_hint')}</p>
            <div className="ta-row" style={{ alignItems: 'center' }}>
                <Check label={t('a.pg.published')} checked={d.is_published} onChange={(v) => setD({ ...d, is_published: v })} />
                <button className="ta-btn pri" type="submit">{saved ? `✓ ${t('a.saved')}` : t('a.save')}</button>
            </div>
        </form>
    );
}

export default function Pages({ pages }: { pages: P[] }) {
    const { t } = useAdmin();

    return (
        <AdminLayout title={t('a.nav.pages')}>
            <p className="ta-lead">{t('a.pg.lead')}</p>
            {pages.map((p) => <Editor key={p.id} page={p} />)}
        </AdminLayout>
    );
}
