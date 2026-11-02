<?php

namespace App\Services\Pricing;

use InvalidArgumentException;

/** Carries field => message pairs so controllers can turn it into a ValidationException. */
final class PricingException extends InvalidArgumentException
{
    /** @param array<string,string> $errors */
    public function __construct(public readonly array $errors)
    {
        parent::__construct(implode(' ', $errors));
    }
}
