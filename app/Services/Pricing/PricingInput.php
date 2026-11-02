<?php

namespace App\Services\Pricing;

/** Raw customer selections. Nothing here is trusted until PricingCalculator validates it. */
final class PricingInput
{
    /** @var array<int,int> option id => quantity */
    public readonly array $options;

    /** @param array<int|string,int|string>|list<int|string> $options list of ids, or id => quantity */
    public function __construct(
        public readonly int $projectTypeId,
        public readonly ?int $pages = null,
        array $options = [],
        public readonly ?string $phone = null,
        public readonly bool $whatsappOptIn = false,
        public readonly int $attachmentCount = 0,
    ) {
        $normalized = [];
        foreach ($options as $key => $value) {
            if (array_is_list($options)) {
                $normalized[(int) $value] = 1;
            } else {
                $normalized[(int) $key] = (int) $value;
            }
        }
        $this->options = $normalized;
    }
}
