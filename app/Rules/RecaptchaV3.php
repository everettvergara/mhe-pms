<?php

namespace App\Rules;

use App\Services\RecaptchaService;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class RecaptchaV3 implements ValidationRule
{
    public function __construct(
        private readonly ?string $action = null,
    ) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $service = app(RecaptchaService::class);

        if (! $service->isEnabled()) {
            return;
        }

        if (! is_string($value) || $value === '') {
            $fail('CAPTCHA verification failed. Please try again.');

            return;
        }

        $result = $service->verify($value, $this->action, request()->ip());

        if (! $result['success']) {
            $fail('CAPTCHA verification failed. Please try again.');
        }
    }
}
