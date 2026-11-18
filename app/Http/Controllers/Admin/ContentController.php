<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Page;
use App\Models\Service;
use App\Models\SiteSetting;
use App\Models\TeamMember;
use App\Support\Mailing;
use App\Support\SupportInfo;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Throwable;
use Inertia\Inertia;
use Inertia\Response;

/** Website content: services, pages, team, contact details. All text is plain; React renders it as text. */
class ContentController extends Controller
{
    public const ICONS = ['web', 'code', 'mobile', 'ads', 'brand', 'growth', 'camera'];

    // ---- services ----
    public function services(): Response
    {
        return Inertia::render('admin/content/services', [
            'services' => Service::orderBy('sort_order')->get()->map(fn (Service $s) => [
                'id' => $s->id, 'slug' => $s->slug, 'name' => $s->tr('name'), 'is_visible' => $s->is_visible, 'sort_order' => $s->sort_order,
            ])->values(),
        ]);
    }

    public function serviceForm(?Service $service = null): Response
    {
        return Inertia::render('admin/content/service-form', [
            'icons' => self::ICONS,
            'service' => $service ? $service->only(['id', 'slug', 'icon', 'name', 'summary', 'description', 'problems', 'deliverables', 'features', 'steps', 'faqs', 'is_visible', 'sort_order']) : null,
        ]);
    }

    public function serviceSave(Request $request, ?Service $service = null): RedirectResponse
    {
        $list = ['nullable', 'array']; $item = ['nullable', 'string', 'max:300'];
        $data = $request->validate([
            'slug' => [$service ? 'prohibited' : 'required', 'regex:/^[a-z0-9]+(-[a-z0-9]+)*$/', 'max:60', 'unique:services,slug'],
            'icon' => ['nullable', 'in:'.implode(',', self::ICONS)],
            'name' => ['required', 'array'], 'name.en' => ['required', 'string', 'max:120'], 'name.ar' => ['required', 'string', 'max:120'],
            'summary' => ['nullable', 'array'], 'summary.*' => ['nullable', 'string', 'max:300'],
            'description' => ['nullable', 'array'], 'description.*' => ['nullable', 'string', 'max:6000'],
            'problems' => $list, 'problems.*' => $list, 'problems.*.*' => $item,
            'deliverables' => $list, 'deliverables.*' => $list, 'deliverables.*.*' => $item,
            'features' => $list, 'features.*' => $list, 'features.*.*' => $item,
            'steps' => $list, 'steps.*' => $list, 'steps.*.*.title' => ['nullable', 'string', 'max:120'], 'steps.*.*.text' => ['nullable', 'string', 'max:500'],
            'faqs' => $list, 'faqs.*' => $list, 'faqs.*.*.q' => ['nullable', 'string', 'max:200'], 'faqs.*.*.a' => ['nullable', 'string', 'max:1500'],
            'is_visible' => ['required', 'boolean'],
            'sort_order' => ['required', 'integer', 'min:0', 'max:9999'],
        ]);

        $clean = fn (array $by, bool $rows = false) => collect(['en', 'ar'])->mapWithKeys(fn ($l) => [
            $l => collect($by[$l] ?? [])->filter(fn ($v) => $rows ? is_array($v) && collect($v)->filter()->count() === 2 : filled($v))->values()->all(),
        ])->all();

        $attributes = [
            'icon' => $data['icon'] ?? null, 'name' => $data['name'],
            'summary' => $data['summary'] ?? null, 'description' => $data['description'] ?? null,
            'problems' => $clean($data['problems'] ?? []), 'deliverables' => $clean($data['deliverables'] ?? []), 'features' => $clean($data['features'] ?? []),
            'steps' => $clean($data['steps'] ?? [], true), 'faqs' => $clean($data['faqs'] ?? [], true),
            'is_visible' => $data['is_visible'], 'sort_order' => $data['sort_order'],
        ];

        if ($service) {
            $service->update($attributes);
            ActivityLog::record('service.updated', $service);
        } else {
            $service = Service::create($attributes + ['slug' => $data['slug']]);
            ActivityLog::record('service.created', $service);
        }

        return redirect()->route('admin.content.services');
    }

    public function serviceDelete(Service $service): RedirectResponse
    {
        $service->delete(); // soft delete: projects keep their reference
        ActivityLog::record('service.deleted', $service);

        return redirect()->route('admin.content.services');
    }

    // ---- pages ----
    public function pages(): Response
    {
        return Inertia::render('admin/content/pages', [
            'pages' => Page::orderBy('slug')->get()->map(fn (Page $p) => [
                'id' => $p->id, 'slug' => $p->slug, 'title' => $p->title, 'body' => $p->body, 'is_published' => $p->is_published,
            ])->values(),
        ]);
    }

    public function pageSave(Request $request, Page $page): RedirectResponse
    {
        $data = $request->validate([
            'title' => ['required', 'array'], 'title.en' => ['required', 'string', 'max:160'], 'title.ar' => ['required', 'string', 'max:160'],
            'body' => ['nullable', 'array'], 'body.*' => ['nullable', 'string', 'max:30000'],
            'is_published' => ['required', 'boolean'],
        ]);
        $was = $page->is_published;
        $page->update(['title' => $data['title'], 'body' => $data['body'] ?? null, 'is_published' => $data['is_published']]);
        ActivityLog::record($was === $page->is_published ? 'page.updated' : ($page->is_published ? 'page.published' : 'page.unpublished'), $page);

        return back();
    }

    // ---- team ----
    public function team(): Response
    {
        return Inertia::render('admin/content/team', [
            'members' => TeamMember::orderBy('sort_order')->get()->map(fn (TeamMember $m) => [
                'id' => $m->id, 'name' => $m->name, 'role' => $m->role, 'is_visible' => $m->is_visible, 'sort_order' => $m->sort_order,
            ])->values(),
        ]);
    }

    public function teamSave(Request $request, ?TeamMember $member = null): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'array'], 'name.en' => ['required', 'string', 'max:120'], 'name.ar' => ['required', 'string', 'max:120'],
            'role' => ['required', 'array'], 'role.en' => ['required', 'string', 'max:120'], 'role.ar' => ['required', 'string', 'max:120'],
            'is_visible' => ['required', 'boolean'], 'sort_order' => ['required', 'integer', 'min:0', 'max:9999'],
        ]);
        $member ? $member->update($data) : ($member = TeamMember::create($data));
        ActivityLog::record($member->wasRecentlyCreated ? 'team.created' : 'team.updated', $member);

        return back();
    }

    public function teamDelete(TeamMember $member): RedirectResponse
    {
        $member->delete();
        ActivityLog::record('team.deleted', $member);

        return back();
    }

    // ---- contact details ----
    public function settings(): Response
    {
        $c = SiteSetting::get('contact', []);

        $n = (array) SiteSetting::get('notifications', []);

        return Inertia::render('admin/content/settings', [
            'contact' => [
                'email' => $c['email'] ?? '', 'phone' => $c['phone'] ?? '', 'whatsapp' => $c['whatsapp'] ?? '', 'address' => $c['address'] ?? '',
            ],
            'alerts' => [
                'email' => $n['email'] ?? '', 'enabled' => (bool) ($n['enabled'] ?? true),
                'mail_ready' => Mailing::isConfigured(), 'mailer' => (string) config('mail.default'),
            ],
        ]);
    }

    // ---- "support our journey" (bank account / Sham Cash) ----
    public function support(): Response
    {
        $s = SupportInfo::raw();
        $s['shamcash']['qr_url'] = SupportInfo::qrUrl($s['shamcash']['qr']);

        return Inertia::render('admin/content/support', ['support' => $s]);
    }

    public function supportSave(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'enabled' => ['boolean'],
            'bank.enabled' => ['boolean'],
            'bank.bank_name' => ['nullable', 'string', 'max:120'],
            'bank.holder' => ['nullable', 'string', 'max:120'],
            'bank.account' => ['nullable', 'string', 'max:60', 'required_if_accepted:bank.enabled'],
            'bank.branch' => ['nullable', 'string', 'max:120'],
            'shamcash.enabled' => ['boolean'],
            'shamcash.holder' => ['nullable', 'string', 'max:120'],
            'shamcash.account' => ['nullable', 'string', 'max:80'],
        ]);
        $current = SupportInfo::raw();
        SiteSetting::put('support', [
            'enabled' => (bool) ($data['enabled'] ?? false),
            'bank' => array_map(fn ($v) => is_string($v) ? trim($v) : $v, (array) ($data['bank'] ?? [])) + $current['bank'],
            // The QR image is managed by its own upload endpoint; keep it.
            'shamcash' => array_map(fn ($v) => is_string($v) ? trim($v) : $v, (array) ($data['shamcash'] ?? [])) + ['qr' => $current['shamcash']['qr']],
        ]);
        ActivityLog::record('settings.support_updated');

        return back();
    }

    public function supportQr(Request $request): RedirectResponse
    {
        $request->validate(['qr' => ['required', 'image', 'mimes:png,jpg,jpeg,webp', 'max:2048']]);
        $s = (array) SiteSetting::get('support', []);
        $old = $s['shamcash']['qr'] ?? null;
        $s['shamcash'] = array_merge((array) ($s['shamcash'] ?? []), ['qr' => $request->file('qr')->store('support', 'public')]);
        SiteSetting::put('support', $s);
        if ($old) {
            Storage::disk('public')->delete($old);
        }
        ActivityLog::record('settings.support_qr_uploaded');

        return back();
    }

    public function supportQrDelete(): RedirectResponse
    {
        $s = (array) SiteSetting::get('support', []);
        if ($old = $s['shamcash']['qr'] ?? null) {
            Storage::disk('public')->delete($old);
        }
        $s['shamcash'] = array_merge((array) ($s['shamcash'] ?? []), ['qr' => null]);
        SiteSetting::put('support', $s);

        return back();
    }

    public function alertsSave(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'email' => ['nullable', 'email:rfc', 'max:190'],
            'enabled' => ['boolean'],
        ]);
        SiteSetting::put('notifications', ['email' => $data['email'] ?? null, 'enabled' => (bool) ($data['enabled'] ?? true)]);
        ActivityLog::record('settings.notifications_updated');

        return back();
    }

    /** Sends a real test email to the alert inbox so the owner can confirm delivery. */
    public function alertsTest(): RedirectResponse
    {
        $to = Mailing::alertEmail();
        if (! $to) {
            return back()->withErrors(['test' => __('admin_sales.s.alert_no_email')]);
        }
        if (! Mailing::isConfigured()) {
            return back()->withErrors(['test' => __('admin_sales.s.alert_no_mailer')]);
        }
        try {
            Mail::raw("Tieco: test alert ✓\nإشعارات الموقع تعمل بشكل صحيح.\n".url('/admin'), fn ($m) => $m->to($to)->subject('Tieco — test alert / إيميل تجريبي'));
        } catch (Throwable $e) {
            report($e);

            return back()->withErrors(['test' => __('admin_sales.s.alert_failed').' '.Str::limit($e->getMessage(), 200)]);
        }

        return back()->with('alert_test_sent', true);
    }

    public function settingsSave(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'email' => ['nullable', 'email:rfc', 'max:190'],
            'phone' => ['nullable', 'string', 'max:30', 'regex:/^\+[0-9\s().-]{7,25}$/'],
            'whatsapp' => ['nullable', 'regex:/^\+[1-9][0-9]{7,14}$/'],
            'address' => ['nullable', 'string', 'max:300'],
        ]);
        SiteSetting::put('contact', array_merge((array) SiteSetting::get('contact', []), $data));
        ActivityLog::record('settings.contact_updated');

        return back();
    }
}
