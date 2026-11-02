<?php

namespace Tests\Feature;

use App\Models\DiscountRule;
use App\Models\ProjectType;
use App\Models\ServiceOption;
use App\Services\Pricing\PricingCalculator;
use App\Services\Pricing\PricingException;
use App\Services\Pricing\PricingInput;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PricingCalculatorTest extends TestCase
{
    use RefreshDatabase;

    private function type(array $o = []): ProjectType
    {
        return ProjectType::create($o + [
            'key' => 't'.uniqid(), 'kind' => 'website', 'name' => ['en' => 'Site', 'ar' => 'موقع'],
            'base_price_minor' => 100000, 'supports_pages' => true, 'min_pages' => 1, 'max_pages' => 20,
            'included_pages' => 3, 'extra_page_price_minor' => 5000, 'is_active' => true,
        ]);
    }

    private function option(ProjectType $t, array $o = [], bool $attach = true): ServiceOption
    {
        $opt = ServiceOption::create($o + [
            'code' => 'o'.uniqid(), 'group' => 'addon', 'name' => ['en' => 'Opt', 'ar' => 'خيار'],
            'pricing_mode' => 'fixed', 'amount' => 20000, 'is_active' => true,
        ]);
        if ($attach) {
            $t->options()->attach($opt->id);
        }

        return $opt;
    }

    private function rule(array $o = []): DiscountRule
    {
        return DiscountRule::create($o + [
            'code' => 'd'.uniqid(), 'name' => ['en' => 'Disc', 'ar' => 'خصم'], 'type' => 'fixed',
            'value' => 5000, 'condition_type' => 'none', 'is_active' => true,
        ]);
    }

    private function calc(PricingInput $in): array
    {
        return app(PricingCalculator::class)->calculate($in);
    }

    public function test_extra_pages_are_charged_beyond_included(): void
    {
        $t = $this->type();
        $r = $this->calc(new PricingInput($t->id, pages: 8));
        $this->assertSame(125000, $r['total_minor']); // 100000 + 5 * 5000
        $this->assertFalse($r['has_unpriced_items']);
    }

    public function test_pages_outside_limits_are_rejected(): void
    {
        $t = $this->type();
        try {
            $this->calc(new PricingInput($t->id, pages: 99));
            $this->fail('Expected PricingException');
        } catch (PricingException $e) {
            $this->assertArrayHasKey('pages', $e->errors);
        }
    }

    public function test_unconfigured_prices_are_flagged_not_invented(): void
    {
        $t = $this->type(['base_price_minor' => null]);
        $r = $this->calc(new PricingInput($t->id));
        $this->assertTrue($r['has_unpriced_items']);
        $this->assertSame(0, $r['total_minor']);
    }

    public function test_incompatible_or_inactive_options_are_rejected(): void
    {
        $t = $this->type();
        $other = $this->option($t, attach: false);
        $this->expectException(PricingException::class);
        $this->calc(new PricingInput($t->id, options: [$other->id]));
    }

    public function test_percent_option_rounds_half_up_on_core_only(): void
    {
        $t = $this->type(['base_price_minor' => 99999, 'included_pages' => 3]);
        $o = $this->option($t, ['pricing_mode' => 'percent_bp', 'amount' => 1250]);
        $r = $this->calc(new PricingInput($t->id, options: [$o->id]));
        $this->assertSame(12500, $r['lines'][1]['line_total_minor']); // 12499.875 -> 12500
        $this->assertSame(112499, $r['total_minor']);
    }

    public function test_only_one_option_per_exclusive_group(): void
    {
        $t = $this->type();
        $a = $this->option($t, ['group' => 'platform', 'exclusive_group' => 'platform']);
        $b = $this->option($t, ['group' => 'platform', 'exclusive_group' => 'platform']);
        $this->expectException(PricingException::class);
        $this->calc(new PricingInput($t->id, options: [$a->id, $b->id]));
    }

    public function test_whatsapp_discount_needs_valid_phone_and_opt_in(): void
    {
        $t = $this->type();
        $this->rule(['condition_type' => 'whatsapp_phone']);

        $ok = $this->calc(new PricingInput($t->id, phone: '+20 100 123 4567', whatsappOptIn: true));
        $this->assertSame(5000, $ok['discount_minor']);

        $noOptIn = $this->calc(new PricingInput($t->id, phone: '+201001234567', whatsappOptIn: false));
        $this->assertSame(0, $noOptIn['discount_minor']);

        $badPhone = $this->calc(new PricingInput($t->id, phone: '12345', whatsappOptIn: true));
        $this->assertSame(0, $badPhone['discount_minor']);
    }

    public function test_attachment_discount_and_percent_cap(): void
    {
        $t = $this->type();
        $this->rule(['condition_type' => 'attachment', 'type' => 'percent_bp', 'value' => 1000, 'max_discount_minor' => 7000]);

        $this->assertSame(0, $this->calc(new PricingInput($t->id))['discount_minor']);
        $this->assertSame(7000, $this->calc(new PricingInput($t->id, attachmentCount: 1))['discount_minor']); // 10% = 10000, capped
    }

    public function test_non_stackable_uses_best_and_stackable_adds_up(): void
    {
        $t = $this->type();
        $this->rule(['value' => 5000]);
        $this->rule(['value' => 8000]);
        $this->assertSame(8000, $this->calc(new PricingInput($t->id))['discount_minor']);

        $this->rule(['value' => 1000, 'stackable' => true]);
        $this->rule(['value' => 2000, 'stackable' => true]);
        $this->assertSame(11000, $this->calc(new PricingInput($t->id))['discount_minor']);
    }

    public function test_total_never_goes_negative(): void
    {
        $t = $this->type();
        $this->rule(['value' => 9999999]);
        $r = $this->calc(new PricingInput($t->id));
        $this->assertSame(100000, $r['discount_minor']);
        $this->assertSame(0, $r['total_minor']);
    }

    public function test_phone_normalization(): void
    {
        $this->assertSame('+201001234567', PricingCalculator::normalizePhone('00 20 (100) 123-4567'));
        $this->assertNull(PricingCalculator::normalizePhone('0100 123 4567'));
        $this->assertNull(PricingCalculator::normalizePhone(null));
    }
}
