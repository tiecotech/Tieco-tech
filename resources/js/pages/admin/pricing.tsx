import AdminLayout from '@/components/admin/admin-layout';
import { names, useAdmin } from '@/lib/admin';
import { router } from '@inertiajs/react';
import { useState } from 'react';

interface TypeRow { id: number; key: string; name: string; supports_pages: boolean; base_price: string; extra_page_price: string; included_pages: number; min_pages: number; max_pages: number; is_active: boolean }
interface OptionRow { id: number; code: string; group: string; name: string; mode: string; amount: string; max_quantity: number; is_active: boolean }
interface DiscountRow { id: number; code: string; name: string; type: string; value: string; condition_type: string; condition_value: string; max_discount: string; stackable: boolean; priority: number; is_active: boolean }
interface Props { placeholder: boolean; types: TypeRow[]; options: OptionRow[]; discounts: DiscountRow[] }

type Errors = Record<string, string>;

/** Saves one row with PUT and shows a short "saved" confirmation or the server's field errors. */
function useSave(endpoint: string) {
    const { t } = useAdmin();
    const [errors, setErrors] = useState<Errors>({});
    const [saved, setSaved] = useState(false);
    const [busy, setBusy] = useState(false);
    const save = (data: Record<string, unknown>, method: 'put' | 'post' = 'put', onDone?: () => void) => {
        setBusy(true);
        const options = {
            preserveScroll: true,
            onSuccess: () => { setErrors({}); setSaved(true); setTimeout(() => setSaved(false), 1800); onDone?.(); },
            onError: (e: Record<string, string>) => setErrors(e),
            onFinish: () => setBusy(false),
        };
        if (method === 'put') router.put(endpoint, data as never, options);
        else router.post(endpoint, data as never, options);
    };
    const flag = busy ? '…' : saved ? `✓ ${t('a.saved')}` : t('a.save');

    return { errors, save, flag, busy };
}

function Num({ label, value, onChange, error, hint, width = 110 }: { label: string; value: string | number; onChange: (v: string) => void; error?: string; hint?: string; width?: number }) {
    return (
        <label className={`ta-fld${error ? ' has-error' : ''}`} style={{ width }}>
            <span>{label}</span>
            <input className="ta-in" dir="ltr" inputMode="decimal" value={value} placeholder={hint} onChange={(e) => onChange(e.target.value)} />
            {error && <small>{error}</small>}
        </label>
    );
}

function Check({ label, checked, onChange }: { label: string; checked: boolean; onChange: (v: boolean) => void }) {
    return <label className="ta-chk"><input type="checkbox" checked={checked} onChange={(e) => onChange(e.target.checked)} />{label}</label>;
}

function TypeEditor({ row }: { row: TypeRow }) {
    const { t, url } = useAdmin();
    const [d, setD] = useState(row);
    const { errors, save, flag, busy } = useSave(url(`/pricing/types/${row.id}`));

    return (
        <form className="ta-rowform" onSubmit={(e) => { e.preventDefault(); save({ base_price: d.base_price, extra_page_price: d.supports_pages ? d.extra_page_price : '', min_pages: d.min_pages, max_pages: d.max_pages, included_pages: d.included_pages, is_active: d.is_active }); }}>
            <b className="ta-rowname">{row.name}</b>
            <Num label={t('a.pr.base')} value={d.base_price} onChange={(v) => setD({ ...d, base_price: v })} error={errors.base_price} hint={t('a.pr.empty_hint')} />
            {row.supports_pages && (
                <>
                    <Num label={t('a.pr.included')} value={d.included_pages} onChange={(v) => setD({ ...d, included_pages: Number(v) || 0 })} error={errors.included_pages} width={90} />
                    <Num label={t('a.pr.min')} value={d.min_pages} onChange={(v) => setD({ ...d, min_pages: Number(v) || 0 })} error={errors.min_pages} width={80} />
                    <Num label={t('a.pr.max')} value={d.max_pages} onChange={(v) => setD({ ...d, max_pages: Number(v) || 0 })} error={errors.max_pages} width={80} />
                    <Num label={t('a.pr.extra')} value={d.extra_page_price} onChange={(v) => setD({ ...d, extra_page_price: v })} error={errors.extra_page_price} hint={t('a.pr.empty_hint')} />
                </>
            )}
            <Check label={t('a.pr.active')} checked={d.is_active} onChange={(v) => setD({ ...d, is_active: v })} />
            <button className="ta-btn pri" disabled={busy}>{flag}</button>
        </form>
    );
}

function OptionEditor({ row }: { row: OptionRow }) {
    const { t, url } = useAdmin();
    const [d, setD] = useState(row);
    const { errors, save, flag, busy } = useSave(url(`/pricing/options/${row.id}`));

    return (
        <form className="ta-rowform" onSubmit={(e) => { e.preventDefault(); save({ amount: d.amount, max_quantity: d.max_quantity, is_active: d.is_active }); }}>
            <b className="ta-rowname">{row.name}<small>{t(`a.pr.mode.${row.mode}`)}</small></b>
            <Num label={row.mode === 'percent_bp' ? t('a.pr.amount_pct') : t('a.pr.amount')} value={d.amount} onChange={(v) => setD({ ...d, amount: v })} error={errors.amount} hint={t('a.pr.empty_hint')} />
            <Num label={t('a.pr.max_qty')} value={d.max_quantity} onChange={(v) => setD({ ...d, max_quantity: Number(v) || 1 })} error={errors.max_quantity} width={90} />
            <Check label={t('a.pr.active')} checked={d.is_active} onChange={(v) => setD({ ...d, is_active: v })} />
            <button className="ta-btn pri" disabled={busy}>{flag}</button>
        </form>
    );
}

const EMPTY_DISCOUNT = { code: '', name_en: '', name_ar: '', type: 'percent_bp', value: '', condition_type: 'none', condition_value: '', max_discount: '', stackable: false, priority: 0, is_active: false };

function DiscountFields({ d, setD, errors }: { d: typeof EMPTY_DISCOUNT; setD: (v: typeof EMPTY_DISCOUNT) => void; errors: Errors }) {
    const { t } = useAdmin();

    return (
        <>
            <label className="ta-fld" style={{ width: 150 }}>
                <span>{t('a.pr.value')}</span>
                <select className="ta-in" value={d.type} onChange={(e) => setD({ ...d, type: e.target.value })}>
                    <option value="percent_bp">{t('a.pr.type_percent')}</option><option value="fixed">{t('a.pr.type_fixed')}</option>
                </select>
            </label>
            <Num label={d.type === 'percent_bp' ? '%' : t('a.pr.value')} value={d.value} onChange={(v) => setD({ ...d, value: v })} error={errors.value} width={90} />
            <label className="ta-fld" style={{ width: 200 }}>
                <span>{t('a.pr.condition')}</span>
                <select className="ta-in" value={d.condition_type} onChange={(e) => setD({ ...d, condition_type: e.target.value })}>
                    {['none', 'whatsapp_phone', 'attachment', 'min_subtotal'].map((c) => <option key={c} value={c}>{t(`a.pr.cond.${c}`)}</option>)}
                </select>
            </label>
            {d.condition_type === 'min_subtotal' && <Num label={t('a.pr.min_subtotal')} value={d.condition_value} onChange={(v) => setD({ ...d, condition_value: v })} error={errors.condition_value} />}
            <Num label={t('a.pr.max_discount')} value={d.max_discount} onChange={(v) => setD({ ...d, max_discount: v })} error={errors.max_discount} hint="—" />
            <Num label={t('a.pr.priority')} value={d.priority} onChange={(v) => setD({ ...d, priority: Number(v) || 0 })} error={errors.priority} width={80} />
            <Check label={t('a.pr.stackable')} checked={d.stackable} onChange={(v) => setD({ ...d, stackable: v })} />
            <Check label={t('a.pr.active')} checked={d.is_active} onChange={(v) => setD({ ...d, is_active: v })} />
        </>
    );
}

function DiscountEditor({ row }: { row: DiscountRow }) {
    const { t, url } = useAdmin();
    const [d, setD] = useState({ ...EMPTY_DISCOUNT, ...row, name_en: '', name_ar: '' });
    const { errors, save, flag, busy } = useSave(url(`/pricing/discounts/${row.id}`));

    return (
        <form className="ta-rowform" onSubmit={(e) => { e.preventDefault(); const { code: _c, name_en: _e, name_ar: _a, ...rest } = d; save(rest); }}>
            <b className="ta-rowname">{row.name}<small dir="ltr">{row.code}</small></b>
            <DiscountFields d={d} setD={setD} errors={errors} />
            <button className="ta-btn pri" disabled={busy}>{flag}</button>
        </form>
    );
}

function NewDiscount() {
    const { t, url } = useAdmin();
    const [d, setD] = useState(EMPTY_DISCOUNT);
    const { errors, save, busy } = useSave(url('/pricing/discounts'));

    return (
        <form className="ta-rowform new" onSubmit={(e) => { e.preventDefault(); save(d, 'post', () => setD(EMPTY_DISCOUNT)); }}>
            <b className="ta-rowname">{t('a.pr.new_discount')}</b>
            <label className={`ta-fld${errors.code ? ' has-error' : ''}`} style={{ width: 170 }}><span>{t('a.pr.code')}</span><input className="ta-in" dir="ltr" value={d.code} onChange={(e) => setD({ ...d, code: e.target.value })} />{errors.code && <small>{errors.code}</small>}</label>
            <label className="ta-fld" style={{ width: 160 }}><span>{t('a.pr.name_en')}</span><input className="ta-in" dir="ltr" value={d.name_en} onChange={(e) => setD({ ...d, name_en: e.target.value })} /></label>
            <label className="ta-fld" style={{ width: 160 }}><span>{t('a.pr.name_ar')}</span><input className="ta-in" value={d.name_ar} onChange={(e) => setD({ ...d, name_ar: e.target.value })} /></label>
            <DiscountFields d={d} setD={setD} errors={errors} />
            <button className="ta-btn pri" disabled={busy}>{busy ? '…' : t('a.pr.create')}</button>
        </form>
    );
}

export default function Pricing({ placeholder, types, options, discounts }: Props) {
    const { t, url, locale } = useAdmin();
    const groups = ['platform', 'addon', 'creative'].filter((g) => options.some((o) => o.group === g));

    return (
        <AdminLayout title={t('a.pr.title')}>
            <p className="ta-lead">{t('a.pr.lead')}</p>
            {placeholder && (
                <div className="ta-banner">
                    <span>{t('a.pr.placeholder')}</span>
                    <button className="ta-btn pri" type="button" onClick={() => router.post(url('/pricing/finalize'), {}, { preserveScroll: true })}>{t('a.pr.finalize')}</button>
                </div>
            )}

            <section className="ta-card">
                <h2>{t('a.pr.types')}</h2>
                {types.map((row) => <TypeEditor key={row.id} row={row} />)}
            </section>

            {groups.map((g) => (
                <section className="ta-card" key={g}>
                    <h2>{t(`a.pr.group.${g}`)}</h2>
                    {options.filter((o) => o.group === g).map((row) => <OptionEditor key={row.id} row={{ ...row, name: names(row.name, locale) }} />)}
                </section>
            ))}

            <section className="ta-card">
                <h2>{t('a.pr.discounts')}</h2>
                {discounts.map((row) => <DiscountEditor key={row.id} row={row} />)}
                <NewDiscount />
            </section>
        </AdminLayout>
    );
}
