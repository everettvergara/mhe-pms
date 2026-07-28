<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use App\Rules\RecaptchaV3;

class RecaptchaService
{
    public function isEnabled(): bool
    {
        return filled(config('recaptcha.site_key')) && filled(config('recaptcha.secret_key'));
    }

    /**
     * @return array{success: bool, score: float|null, action: string|null, error_codes: array<int, string>}
     */
    public function verify(string $token, ?string $expectedAction = null, ?string $remoteIp = null): array
    {
        if (! $this->isEnabled()) {
            return [
                'success' => true,
                'score' => 1.0,
                'action' => $expectedAction,
                'error_codes' => [],
            ];
        }

        $response = Http::asForm()->post(config('recaptcha.verify_url'), array_filter([
            'secret' => config('recaptcha.secret_key'),
            'response' => $token,
            'remoteip' => $remoteIp,
        ]));

        if (! $response->successful()) {
            return [
                'success' => false,
                'score' => null,
                'action' => null,
                'error_codes' => ['http-error'],
            ];
        }

        /** @var array<string, mixed> $payload */
        $payload = $response->json();
        $success = (bool) ($payload['success'] ?? false);
        $score = isset($payload['score']) ? (float) $payload['score'] : null;
        $action = isset($payload['action']) ? (string) $payload['action'] : null;
        $errorCodes = array_map('strval', $payload['error-codes'] ?? []);

        if ($success && $score !== null && $score < config('recaptcha.score_threshold')) {
            $success = false;
            $errorCodes[] = 'score-threshold';
        }

        if ($success && $expectedAction !== null && $action !== $expectedAction) {
            $success = false;
            $errorCodes[] = 'action-mismatch';
        }

        return [
            'success' => $success,
            'score' => $score,
            'action' => $action,
            'error_codes' => $errorCodes,
        ];
    }

    /**
     * @return array<int, mixed>
     */
    public function tokenRules(string $action): array
    {
        if (! $this->isEnabled()) {
            return ['nullable', 'string'];
        }

        return ['required', 'string', new RecaptchaV3($action)];
    }
}
