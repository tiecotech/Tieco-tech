import AdminLayout from '@/components/admin/admin-layout';
import { Check, Txt } from '@/components/admin/fields';
import { useAdmin } from '@/lib/admin';
import { Link, router } from '@inertiajs/react';
import { useState } from 'react';

type L = Record<'en' | 'ar', string>;
type Pair = { title?: string; text?: string; q?: string; a?: string };
type Lists = Record<string, string[]>;
type Rows = Record<string, Pair[]>;
interface Service {
    id: number; slug: string; icon: string | null; name: L; summary: L | null; description: L | null;
    problems: Lists | null; deliverables: Lists | null; features: Lists | null; steps: Rows | null; faqs: Rows | null; is_visible: boolean; sort_order: number;
}

const LANGS = ['en', 'ar'] as const;
const lines = (v?: string[]) => (v ?? []).join('\n');
const toList = (s: string) => s.split('\n').map((x) => x.trim()).filter(Boolean);
const pairs = (v: Pair[] | undefined, a: 'title' | 'q', b: 'text' | 'a') => (v ?? []).map((r) => `${r[a] ?? ''} | ${r[b] ?? ''}`).join('\n');
const toPairs = (s: string, a: 'title' | 'q', b: 'text' | 'a') => s.split('\n').map((l) => { const [x, ...y] = l.split('|'); return { [a]: x.trim(), [b]: y.join('|').trim() }; }).filter((r) => r[a] && r[b]);

export default function ServiceForm({ service, icons }: { service: Service | null; icons: string[] }) {
    const { t, url } = useAdmin();
    const per = (f: (l: 'en' | 'ar') => string): L => ({ en: f('en'), ar: f('ar') });
    const [d, setD] = useState({
        slug: service?.slug ?? '', icon: service?.icon ?? '', is_visible: service?.is_visible ?? true, sort_order: service?.sort_order ?? 0,
        name: per((l) => service?.name?.[l] ?? ''), summary: per((l) => service?.summary?.[l] ?? ''), description: per((l) => service?.description?.[l] ?? ''),
        problems: per((l) => lines(service?.problems?.[l])), deliverables: per((l) => lines(service?.deliverables?.[l])), features: per((l) => lines(service?.features?.[l])),
        steps: per((l) => pairs(service?.steps?.[l], 'title', 'text')), faqs: per((l) => pairs(service?.faqs?.[l], 'q', 'a')),
    });
    const [errors, setErrors] = useState<Record<string, string>>({});
    const [busy, setBusy] = useState(false);
    const set = (k: 'name' | 'summary' | 'description' | 'problems' | 'deliverables' | 'features' | 'steps' | 'faqs', l: 'en' | 'ar', v: string) => setD({ ...d, [k]: { ...d[k], [l]: v } });

    const submit = () => {
        const map = (src: L, f: (s: string) => unknown) => ({ en: f(src.en), ar: f(src.ar) });
        const payload = {
            ...(service ? {} : { slug: d.slug }), icon: d.icon || null, is_visible: d.is_visible, sort_order: d.sort_order,
            name: d.name, summary: d.summary, description: d.description,
            problems: map(d.problems, toList), deliverables: map(d.deliverables, toList), features: map(d.features, toList),
            steps: map(d.steps, (s) => toPairs(s, 'title', 'text')), faqs: map(d.faqs, (s) => toPairs(s, 'q', 'a')),
        };
        const opts = { preserveScroll: true, onError: (e: Record<string, string>) => setErrors(e), onFinish: () => setBusy(false) };
        setBusy(true);
        if (service) router.put(url(`/content/services/${service.id}`), payload as never, opts);
        else router.post(url('/content/services'), payload as never, opts);
    };
    const del = () => { if (service && confirm(t('a.svc.confirm_delete'))) router.delete(url(`/content/services/${service.id}`)); };

    const two = (key: 'name' | 'summary' | 'description' | 'problems' | 'deliverables' | 'features' | 'steps' | 'faqs', label: string, area: number, hint?: string) => (
        <div className="ta-card" key={key}>
            <h2>{label}</h2>
            {hint && <p className="ta-muted" style={{ marginBottom: 10 }}>{hint}</p>}
            <div className="ta-row" style={{ flexWrap: 'wrap' }}>
                {LANGS.map((l) => <Txt key={l} label={l === 'en' ? 'English' : 'العربية'} dir={l === 'ar' ? 'rtl' : 'ltr'} area={area} value={d[key][l]} onChange={(v) => set(key, l, v)} error={errors[`${key}.${l}`]} />)}
            </div>
        </div>
    );

    return (
        <AdminLayout title={service ? t('a.svc.edit') : t('a.svc.new')}>
            <Link className="ta-back" href={url('/content/services')}>← {t('a.nav.services_c')}</Link>
            <form onSubmit={(e) => { e.preventDefault(); submit(); }} style={{ display: 'grid', gap: 18 }}>
                <div className="ta-card">
                    <div className="ta-row" style={{ flexWrap: 'wrap', alignItems: 'flex-end' }}>
                        {!service && <Txt label="URL (a-z, 0-9, -)" dir="ltr" value={d.slug} onChange={(v) => setD({ ...d, slug: v })} error={errors.slug} width={200} />}
                        <label className="ta-fld"><span>{t('a.svc.icon')}</span>
                            <select className="ta-in" value={d.icon} onChange={(e) => setD({ ...d, icon: e.target.value })}><option value="" />{icons.map((i) => <option key={i} value={i}>{i}</option>)}</select>
                        </label>
                        <Txt label={t('a.svc.order')} type="number" dir="ltr" value={String(d.sort_order)} onChange={(v) => setD({ ...d, sort_order: Number(v) || 0 })} error={errors.sort_order} width={90} />
                        <Check label={t('a.svc.visible')} checked={d.is_visible} onChange={(v) => setD({ ...d, is_visible: v })} />
                    </div>
                </div>
                {two('name', t('a.svc.name'), 0)}
                {two('summary', t('a.svc.summary'), 2)}
                {two('description', t('a.svc.description'), 6, t('a.svc.para_hint'))}
                {two('problems', t('a.svc.problems'), 5, t('a.svc.per_line'))}
                {two('deliverables', t('a.svc.deliverables'), 5, t('a.svc.per_line'))}
                {two('features', t('a.svc.features'), 5, t('a.svc.per_line'))}
                {two('steps', t('a.svc.steps'), 5, t('a.svc.pipe_title'))}
                {two('faqs', t('a.svc.faqs'), 5, t('a.svc.pipe_q'))}
                <div className="ta-row">
                    <button className="ta-btn pri" type="submit" disabled={busy}>{busy ? '…' : t('a.save')}</button>
                    {service && <button className="ta-btn" type="button" onClick={del}>{t('a.svc.delete')}</button>}
                </div>
                {Object.keys(errors).length > 0 && <p className="ta-warn">{t('a.err.generic')}</p>}
            </form>
        </AdminLayout>
    );
}
