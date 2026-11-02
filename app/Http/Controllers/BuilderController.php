<?php

namespace App\Http\Controllers;

use App\Http\Requests\EstimateRequest;
use App\Http\Requests\SubmitProjectRequest;
use App\Models\DiscountRule;
use App\Models\ProjectType;
use App\Models\SiteSetting;
use App\Services\Pricing\PricingCalculator;
use App\Services\Pricing\PricingException;
use App\Services\ProjectRequests\ProjectRequestSubmitter;
use App\Support\Mailing;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class BuilderController extends Controller
{
    public function show(Request $request): Response
    {
        $types = ProjectType::active()->with(['options' => fn ($q) => $q->active()])->get()->map(fn (ProjectType $t) => [
            'id' => $t->id, 'key' => $t->key, 'kind' => $t->kind,
            'name' => $t->tr('name'), 'description' => $t->tr('description'),
            'base_price_minor' => $t->base_price_minor, 'supports_pages' => $t->supports_pages,
            'min_pages' => $t->min_pages, 'max_pages' => $t->max_pages, 'included_pages' => $t->included_pages,
            'extra_page_price_minor' => $t->extra_page_price_minor,
            'options' => $t->options->map(fn ($o) => [
                'id' => $o->id, 'code' => $o->code, 'group' => $o->group, 'exclusive_group' => $o->exclusive_group,
                'name' => $o->tr('name'), 'description' => $o->tr('description'),
                'pricing_mode' => $o->pricing_mode, 'amount' => $o->amount, 'max_quantity' => $o->max_quantity,
            ])->values(),
        ])->values();

        $discounts = DiscountRule::currentlyActive()->get()->map(fn (DiscountRule $d) => [
            'code' => $d->code, 'name' => $d->tr('name'), 'description' => $d->tr('description'),
            'type' => $d->type, 'value' => $d->value, 'condition_type' => $d->condition_type,
            'condition_value' => $d->condition_value, 'max_discount_minor' => $d->max_discount_minor,
        ])->values();

        return Inertia::render('builder', [
            'types' => $types,
            'discounts' => $discounts,
            'currency' => SiteSetting::get('currency') ?? ['code' => 'USD', 'symbol' => '$', 'decimals' => 2, 'position' => 'before'],
            'limits' => config('tieco.uploads'),
            'preselect' => (string) $request->query('type', ''),
            // Suggestions from the guided advisor link. Re-checked against the type in the browser; the server re-prices on submit anyway.
            'presetPages' => min(500, max(0, $request->integer('pages'))) ?: null,
            'presetOptions' => collect(explode(',', (string) $request->query('options', '')))
                ->filter(fn ($c) => preg_match('/^[a-z0-9_]{1,40}$/', $c))->unique()->take(20)->values()->all(),
        ]);
    }

    /** Live estimate. The browser never computes prices; it displays what this returns. */
    public function estimate(EstimateRequest $request, PricingCalculator $calculator): JsonResponse
    {
        try {
            return response()->json($calculator->calculate($request->toInput()));
        } catch (PricingException $e) {
            return response()->json(['errors' => $e->errors], 422);
        }
    }

    public function submit(SubmitProjectRequest $request, ProjectRequestSubmitter $submitter): RedirectResponse
    {
        $saved = $submitter->submit(
            $request->safe()->except(['attachments', 'website']),
            $request->file('attachments', []),
            app()->getLocale(),
        );

        // Reached only after the transaction committed.
        $request->session()->put('submitted', [
            'number' => $saved->number,
            'total_minor' => $saved->total_minor,
            'currency' => $saved->currency,
            'has_unpriced_items' => $saved->has_unpriced_items,
            'email_sent' => (bool) ($saved->email && Mailing::isConfigured()),
        ]);

        return redirect()->route('site.start.received');
    }

    public function received(Request $request): Response|RedirectResponse
    {
        $submitted = $request->session()->get('submitted');

        return $submitted
            ? Inertia::render('builder-received', ['submitted' => $submitted, 'currency' => SiteSetting::get('currency') ?? ['code' => 'USD', 'symbol' => '$', 'decimals' => 2, 'position' => 'before']])
            : redirect()->route('site.start');
    }
}
