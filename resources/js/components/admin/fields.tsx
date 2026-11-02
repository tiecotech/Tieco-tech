import type { ReactNode } from 'react';

interface Common { label: string; error?: string; hint?: string; width?: number | string }

export function Txt({ label, value, onChange, error, hint, width, dir, type = 'text', area = 0 }: Common & {
    value: string; onChange: (v: string) => void; dir?: 'ltr' | 'rtl'; type?: string; area?: number;
}) {
    return (
        <label className={`ta-fld${error ? ' has-error' : ''}`} style={width ? { width } : { flex: '1 1 220px' }}>
            <span>{label}</span>
            {area > 0
                ? <textarea className="ta-in" rows={area} dir={dir} value={value} onChange={(e) => onChange(e.target.value)} />
                : <input className="ta-in" type={type} dir={dir} value={value} placeholder={hint} onChange={(e) => onChange(e.target.value)} />}
            {error && <small>{error}</small>}
        </label>
    );
}

export function Check({ label, checked, onChange }: { label: string; checked: boolean; onChange: (v: boolean) => void }) {
    return <label className="ta-chk"><input type="checkbox" checked={checked} onChange={(e) => onChange(e.target.checked)} />{label}</label>;
}

export function Saved({ show, children }: { show: boolean; children: ReactNode }) {
    return <span className="ta-muted" style={{ alignSelf: 'center' }}>{show ? children : null}</span>;
}
