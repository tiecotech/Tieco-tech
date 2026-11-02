<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\DiscountRule;
use App\Models\ProjectType;
use App\Models\ServiceOption;
use App\Models\SiteSetting;
use App\Support\Money;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/** Edit prices, limits and discount policy without touching code. Old requests keep their frozen snapshots. */
class PricingController extends Controller
{
    private const MONEY = ['nullable', 'regex:'.Money::PATTERN];

    public function index(): Response
    {
        return Inertia::render('admin/pricing', [
            'placeholder' => (bool) SiteSetting::get('pricing_is_placeholder', false),
            'types' => ProjectType::orderBy('sort_order')->get()->map(fn (ProjectType $t) => [
                'id' => $t->id, 'key' => $t->key, 'name' => $t->tr('name'), 'supports_pages' => $t->supports_pages,
                'base_price' => Money::toDecimal($t->base_price_minor), 'extra_page_price' => Money::toDecimal($t->extra_page_price_minor),
                'included_pages' => $t->included_pages, 'min_pages' => $t->min_pages, 'max_pages' => $t->max_pages, 'is_active' => $t->is_active,
            ])->values(),
            'options' => ServiceOption::orderBy('group')->orderBy('sort_order')->get()->map(fn (ServiceOption $o) => [
                'id' => $o->id, 'code' => $o->code, 'group' => $o->group, 'name' => $o->tr('name'), 'mode' => $o->pricing_mode,
                'amount' => Money::toDecimal($o->amount), 'max_quantity' => $o->max_quantity, 'is_active' => $o->is_active,
            ])->values(),
            'discounts' => DiscountRule::orderByDesc('priority')->get()->map(fn (DiscountRule $d) => $this->discountRow($d))->values(),
        ]);
    }

    public function updateType(Request $request, ProjectType $type): RedirectResponse
    {
        $data = $request->validate([
            'base_price' => self::MONEY, 'extra_page_price' => self::MONEY,
            'min_pages' => ['required', 'integer', 'min:1', 'max:500'],
            'max_pages' => ['required', 'integer', 'gte:min_pages', 'max:500'],
            'included_pages' => ['required', 'integer', 'gte:min_pages', 'lte:max_pages'],
            'is_active' => ['boolean'],
        ]);

        return $this->save($type, 'pricing.type_updated', [
            'base_price_minor' => Money::toMinor($data['base_price'] ?? null),
            'extra_page_price_minor' => Money::toMinor($data['extra_page_price'] ?? null),
            'min_pages' => $data['min_pages'], 'max_pages' => $data['max_pages'], 'included_pages' => $data['included_pages'],
            'is_active' => (bool) ($data['is_active'] ?? false),
        ]);
    }

    public function updateOption(Request $request, ServiceOption $option): RedirectResponse
    {
        $data = $request->validate([
            'amount' => self::MONEY, 'max_quantity' => ['required', 'integer', 'min:1', 'max:99'], 'is_active' => ['boolean'],
        ]);

        // For percent options the amount is a percentage; Money turns 12.5 into 1250 basis points.
        return $this->save($option, 'pricing.option_updated', [
            'amount' => Money::toMinor($data['amount'] ?? null),
            'max_quantity' => $data['max_quantity'], 'is_active' => (bool) ($data['is_active'] ?? false),
        ]);
    }

    public function storeDiscount(Request $request): RedirectResponse
    {
        $data = $request->validate(array_merge($this->discountRules(), [
            'code' => ['required', 'alpha_dash', 'max:60', 'unique:discount_rules,code'],
            'name_en' => ['required', 'string', 'max:120'], 'name_ar' => ['required', 'string', 'max:120'],
        ]));

        $rule = DiscountRule::create($this->discountAttributes($data) + [
            'code' => $data['code'], 'name' => ['en' => $data['name_en'], 'ar' => $data['name_ar']],
        ]);
        ActivityLog::record('pricing.discount_created', $rule, ['code' => $rule->code]);

        return back();
    }

    public function updateDiscount(Request $request, DiscountRule $discount): RedirectResponse
    {
        $data = $request->validate($this->discountRules());

        return $this->save($discount, 'pricing.discount_updated', $this->discountAttributes($data));
    }

    /** Stops showing "approximate estimate" language once real prices are confirmed. */
    public function finalize(): RedirectResponse
    {
        SiteSetting::put('pricing_is_placeholder', false, 'pricing');
        ActivityLog::record('pricing.finalized');

        return back();
    }

    /** @return array<string,mixed> */
    private function discountRules(): array
    {
        return [
            'type' => ['required', 'in:fixed,percent_bp'], 'value' => ['required', 'regex:'.Money::PATTERN],
            'condition_type' => ['required', 'in:none,whatsapp_phone,attachment,min_subtotal'],
            'condition_value' => self::MONEY, 'max_discount' => self::MONEY,
            'stackable' => ['boolean'], 'priority' => ['required', 'integer', 'min:0', 'max:1000'], 'is_active' => ['boolean'],
        ];
    }

    /** @param array<string,mixed> $d @return array<string,mixed> */
    private function discountAttributes(array $d): array
    {
        return [
            'type' => $d['type'], 'value' => Money::toMinor($d['value']) ?? 0, 'condition_type' => $d['condition_type'],
            'condition_value' => $d['condition_type'] === 'min_subtotal' ? Money::toMinor($d['condition_value'] ?? null) : null,
            'max_discount_minor' => Money::toMinor($d['max_discount'] ?? null),
            'stackable' => (bool) ($d['stackable'] ?? false), 'priority' => $d['priority'], 'is_active' => (bool) ($d['is_active'] ?? false),
        ];
    }

    /** @return array<string,mixed> */
    private function discountRow(DiscountRule $d): array
    {
        return [
            'id' => $d->id, 'code' => $d->code, 'name' => $d->tr('name'), 'type' => $d->type, 'value' => Money::toDecimal($d->value),
            'condition_type' => $d->condition_type, 'condition_value' => Money::toDecimal($d->condition_value),
            'max_discount' => Money::toDecimal($d->max_discount_minor), 'stackable' => $d->stackable,
            'priority' => $d->priority, 'is_active' => $d->is_active,
        ];
    }

    /** @param array<string,mixed> $attributes */
    private function save(Model $model, string $action, array $attributes): RedirectResponse
    {
        $model->fill($attributes);
        $dirty = $model->getDirty();
        if ($dirty) {
            $old = array_intersect_key($model->getOriginal(), $dirty);
            $model->save();
            ActivityLog::record($action, $model, ['old' => $old, 'new' => $dirty]);
        }

        return back();
    }
}
