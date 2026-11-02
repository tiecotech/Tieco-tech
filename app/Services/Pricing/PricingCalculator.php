<?php

namespace App\Services\Pricing;

use App\Models\DiscountRule;
use App\Models\ProjectType;
use App\Models\SiteSetting;

/**
 * The single source of truth for estimates. All math is integer minor units (no floats).
 *
 * estimate = base + extra pages + selected options - discounts
 *  - percent options are computed on (base + extra pages) only, never on other options.
 *  - every discount is computed on the pre-discount subtotal and applied once (no compounding).
 *  - non-stackable discounts: only the single largest eligible one applies.
 *  - total discount is clipped so the total can never go below zero.
 *  - a NULL price means "not configured": the line costs 0 and has_unpriced_items is set.
 */
final class PricingCalculator
{
    /** @return array<string,mixed> */
    public function calculate(PricingInput $in): array
    {
        $type = ProjectType::active()->find($in->projectTypeId);
        if (! $type) {
            throw new PricingException(['project_type' => 'The selected project type is not available.']);
        }

        $errors = [];
        $lines = [$this->line('base', 'base', $type->name, 1, $type->base_price_minor)];
        $pages = null;

        if ($type->supports_pages) {
            $pages = $in->pages ?? $type->included_pages;
            if ($pages < $type->min_pages || $pages > $type->max_pages) {
                $errors['pages'] = "Pages must be between {$type->min_pages} and {$type->max_pages}.";
            } elseif (($extra = max(0, $pages - $type->included_pages)) > 0) {
                $lines[] = $this->line('pages', 'extra_pages', ['en' => 'Additional pages', 'ar' => 'صفحات إضافية'], $extra, $type->extra_page_price_minor, ['pages' => $pages, 'included' => $type->included_pages]);
            }
        } elseif ($in->pages !== null) {
            $errors['pages'] = 'This project type does not use a page count.';
        }

        $core = array_sum(array_column($lines, 'line_total_minor'));
        $allowed = $type->options()->active()->get()->keyBy('id');

        foreach (array_diff_key($in->options, $allowed->all()) as $id => $_) {
            $errors["options.$id"] = 'This option is not available for the selected project type.';
        }

        $exclusive = [];
        foreach ($allowed as $id => $opt) {
            if (! isset($in->options[$id])) {
                continue;
            }
            $qty = $in->options[$id];
            if ($qty < 1 || $qty > $opt->max_quantity) {
                $errors["options.$id"] = "Quantity must be between 1 and {$opt->max_quantity}.";
                continue;
            }
            if ($opt->exclusive_group) {
                if (isset($exclusive[$opt->exclusive_group])) {
                    $errors["options.$id"] = 'Only one option can be chosen from this group.';
                    continue;
                }
                $exclusive[$opt->exclusive_group] = true;
            }

            $unit = match ($opt->pricing_mode) {
                'per_page' => $pages === null ? false : ($opt->amount === null ? null : $opt->amount * $pages),
                'percent_bp' => $opt->amount === null ? null : self::roundDiv($core * $opt->amount, 10000),
                default => $opt->amount,
            };
            if ($unit === false) {
                $errors["options.$id"] = 'This option needs a page count.';
                continue;
            }
            $lines[] = $this->line('option', $opt->code, $opt->name, $qty, $unit, ['option_id' => $opt->id, 'mode' => $opt->pricing_mode, 'amount' => $opt->amount]) + ['option_id' => $opt->id];
        }

        if ($errors) {
            throw new PricingException($errors);
        }

        $subtotal = array_sum(array_column($lines, 'line_total_minor'));
        $discounts = $this->discounts($in, $subtotal);
        $discountTotal = array_sum(array_column($discounts, 'amount_minor'));

        return [
            'currency' => (string) (SiteSetting::get('currency')['code'] ?? 'USD'),
            'pages' => $pages,
            'lines' => $lines,
            'discounts' => $discounts,
            'subtotal_minor' => $subtotal,
            'discount_minor' => $discountTotal,
            'total_minor' => $subtotal - $discountTotal,
            'has_unpriced_items' => in_array(false, array_column($lines, 'priced'), true),
        ];
    }

    /** @return list<array<string,mixed>> */
    private function discounts(PricingInput $in, int $subtotal): array
    {
        if ($subtotal <= 0) {
            return [];
        }
        $phoneOk = self::normalizePhone($in->phone) !== null && $in->whatsappOptIn;

        $eligible = [];
        foreach (DiscountRule::currentlyActive()->get() as $rule) {
            $ok = match ($rule->condition_type) {
                'whatsapp_phone' => $phoneOk,
                'attachment' => $in->attachmentCount >= 1,
                'min_subtotal' => $rule->condition_value !== null && $subtotal >= $rule->condition_value,
                default => true,
            };
            if (! $ok) {
                continue;
            }
            $amount = $rule->type === 'percent_bp' ? self::roundDiv($subtotal * $rule->value, 10000) : $rule->value;
            if ($rule->max_discount_minor !== null) {
                $amount = min($amount, $rule->max_discount_minor);
            }
            if ($amount > 0) {
                $eligible[] = ['rule' => $rule, 'amount' => $amount];
            }
        }

        // Non-stackable: keep only the best one (first wins on ties, rules are already priority-ordered).
        $stackable = array_filter($eligible, fn ($e) => $e['rule']->stackable);
        $single = array_filter($eligible, fn ($e) => ! $e['rule']->stackable);
        if ($single) {
            usort($single, fn ($a, $b) => $b['amount'] <=> $a['amount']);
            $stackable[] = $single[0];
        }

        $remaining = $subtotal;
        $out = [];
        foreach ($stackable as $e) {
            $amount = min($e['amount'], $remaining);
            if ($amount <= 0) {
                continue;
            }
            $remaining -= $amount;
            $out[] = ['rule_id' => $e['rule']->id, 'code' => $e['rule']->code, 'name' => $e['rule']->name,
                'type' => $e['rule']->type, 'value' => $e['rule']->value, 'amount_minor' => $amount];
        }

        return $out;
    }

    /** @return array<string,mixed> */
    private function line(string $kind, string $code, array $name, int $qty, ?int $unit, array $meta = []): array
    {
        return [
            'kind' => $kind, 'code' => $code, 'name' => $name, 'quantity' => $qty,
            'unit_price_minor' => $unit, 'line_total_minor' => $unit === null ? 0 : $unit * $qty,
            'priced' => $unit !== null, 'meta' => $meta,
        ];
    }

    /** Integer division rounded half-up (non-negative operands). */
    public static function roundDiv(int $numerator, int $denominator): int
    {
        return intdiv(2 * $numerator + $denominator, 2 * $denominator);
    }

    /** Returns E.164 (+15551234567) or null when the number is not plausibly valid. */
    public static function normalizePhone(?string $phone): ?string
    {
        if ($phone === null) {
            return null;
        }
        $p = preg_replace('/[\s().-]+/', '', trim($phone));
        if (str_starts_with($p, '00')) {
            $p = '+'.substr($p, 2);
        }

        return preg_match('/^\+[1-9]\d{7,14}$/', $p) ? $p : null;
    }
}
