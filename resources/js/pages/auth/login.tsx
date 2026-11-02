import '../../../css/login.css';

import symbol from '@/assets/brand/tieco-symbol.png';
import wordmark from '@/assets/brand/tieco-wordmark-white.png';
import { Head, Link, useForm } from '@inertiajs/react';
import { useEffect, useState, type FormEvent, type ReactNode } from 'react';

type Lang = 'en' | 'ar';

const TEXT: Record<Lang, Record<string, string>> = {
    en: {
        title: 'Staff sign in', kicker: 'Team area', lead: 'Manage requests, messages, prices and website content.',
        email: 'Email', password: 'Password', remember: 'Keep me signed in', submit: 'Sign in', busy: 'Signing in...', forgot: 'Forgot your password?',
        show: 'Show', hide: 'Hide', site: 'Back to the website', closed: 'Accounts are created by an administrator.', tagline: 'Ideas to Digital Solutions', lang: 'العربية',
    },
    ar: {
        title: 'دخول الفريق', kicker: 'منطقة الفريق', lead: 'أدر الطلبات والرسائل والأسعار ومحتوى الموقع.',
        email: 'البريد الإلكتروني', password: 'كلمة المرور', remember: 'ابقَ مسجّلًا', submit: 'تسجيل الدخول', busy: 'جارٍ الدخول...', forgot: 'نسيت كلمة المرور؟',
        show: 'إظهار', hide: 'إخفاء', site: 'العودة إلى الموقع', closed: 'الحسابات يُنشئها المدير.', tagline: 'من الفكرة إلى الحل الرقمي', lang: 'English',
    },
};

function initialLang(): Lang {
    try {
        const saved = window.localStorage.getItem('tieco_login_lang');
        if (saved === 'ar' || saved === 'en') return saved;
    } catch { /* storage can be blocked */ }

    return typeof navigator !== 'undefined' && navigator.language?.toLowerCase().startsWith('ar') ? 'ar' : 'en';
}

interface Props { status?: string; canResetPassword?: boolean }

export default function Login({ status, canResetPassword = true }: Props) {
    const [lang, setLang] = useState<Lang>('en');
    const [show, setShow] = useState(false);
    const form = useForm({ email: '', password: '', remember: false });
    const t = (k: string) => TEXT[lang][k];

    useEffect(() => setLang(initialLang()), []);

    const switchLang = () => {
        const next: Lang = lang === 'ar' ? 'en' : 'ar';
        setLang(next);
        try { window.localStorage.setItem('tieco_login_lang', next); } catch { /* ignore */ }
    };
    const submit = (e: FormEvent) => {
        e.preventDefault();
        form.post('/login', { onFinish: () => form.reset('password') });
    };

    return (
        <div className="tt tl" dir={lang === 'ar' ? 'rtl' : 'ltr'} lang={lang}>
            <Head title={t('title')} />
            <div className="tl-blob tl-b1" /><div className="tl-blob tl-b2" /><div className="tl-blob tl-b3" />

            <div className="tl-wrap">
                <aside className="tl-brand" aria-hidden="true">
                    <img className="tl-word" src={wordmark} alt="" />
                    <img className="tl-sym" src={symbol} alt="" />
                    <p className="tl-tag">{t('tagline')}</p>
                </aside>

                <main className="tl-card">
                    <div className="tl-top">
                        <img className="tl-word-sm" src={wordmark} alt="Tieco Tech" />
                        <button type="button" className="tl-lang" onClick={switchLang}>{t('lang')}</button>
                    </div>
                    <p className="tl-kicker">{t('kicker')}</p>
                    <h1>{t('title')}</h1>
                    <p className="tl-lead">{t('lead')}</p>

                    {status && <div className="tl-status" role="status">{status}</div>}

                    <form onSubmit={submit} noValidate>
                        <Field id="email" label={t('email')} error={form.errors.email}>
                            <input id="email" type="email" dir="ltr" autoComplete="username" autoFocus required value={form.data.email} onChange={(e) => form.setData('email', e.target.value)} />
                        </Field>
                        <Field id="password" label={t('password')} error={form.errors.password}>
                            <div className="tl-pw">
                                <input id="password" type={show ? 'text' : 'password'} dir="ltr" autoComplete="current-password" required value={form.data.password} onChange={(e) => form.setData('password', e.target.value)} />
                                <button type="button" onClick={() => setShow(!show)} aria-pressed={show}>{show ? t('hide') : t('show')}</button>
                            </div>
                        </Field>
                        <div className="tl-row">
                            <label className="tl-check"><input type="checkbox" checked={form.data.remember} onChange={(e) => form.setData('remember', e.target.checked)} />{t('remember')}</label>
                            {canResetPassword && <Link href="/forgot-password">{t('forgot')}</Link>}
                        </div>
                        <button className="tl-submit" type="submit" disabled={form.processing}>{form.processing ? t('busy') : t('submit')}</button>
                    </form>

                    <p className="tl-foot">{t('closed')}</p>
                    <a className="tl-back" href="/">← {t('site')}</a>
                </main>
            </div>
        </div>
    );
}

function Field({ id, label, error, children }: { id: string; label: string; error?: string; children: ReactNode }) {
    return (
        <div className={`tl-field${error ? ' has-error' : ''}`}>
            <label htmlFor={id}>{label}</label>
            {children}
            {error && <small role="alert">{error}</small>}
        </div>
    );
}

// Full-screen branded page: opt out of the starter kit's default auth layout.
Login.layout = (page: ReactNode) => page;
