<?php

namespace Tests\Unit;

use App\Services\RecaptchaService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class RecaptchaServiceTest extends TestCase
{
    use RefreshDatabase;
    public function test_verify_skips_when_keys_are_not_configured(): void
    {
        config([
            'recaptcha.site_key' => null,
            'recaptcha.secret_key' => null,
        ]);

        $service = app(RecaptchaService::class);

        $this->assertFalse($service->isEnabled());
        $this->assertTrue($service->verify('any-token', 'login')['success']);
    }

    public function test_verify_accepts_valid_token_with_matching_action_and_score(): void
    {
        config([
            'recaptcha.site_key' => 'site-key',
            'recaptcha.secret_key' => 'secret-key',
            'recaptcha.score_threshold' => 0.5,
            'recaptcha.verify_url' => 'https://www.google.com/recaptcha/api/siteverify',
        ]);

        Http::fake([
            'www.google.com/recaptcha/api/siteverify' => Http::response([
                'success' => true,
                'score' => 0.9,
                'action' => 'login',
            ]),
        ]);

        $result = app(RecaptchaService::class)->verify('valid-token', 'login', '127.0.0.1');

        $this->assertTrue($result['success']);
        $this->assertSame(0.9, $result['score']);
        $this->assertSame('login', $result['action']);
    }

    public function test_verify_rejects_low_score(): void
    {
        config([
            'recaptcha.site_key' => 'site-key',
            'recaptcha.secret_key' => 'secret-key',
            'recaptcha.score_threshold' => 0.5,
            'recaptcha.verify_url' => 'https://www.google.com/recaptcha/api/siteverify',
        ]);

        Http::fake([
            'www.google.com/recaptcha/api/siteverify' => Http::response([
                'success' => true,
                'score' => 0.1,
                'action' => 'login',
            ]),
        ]);

        $result = app(RecaptchaService::class)->verify('low-score-token', 'login');

        $this->assertFalse($result['success']);
        $this->assertContains('score-threshold', $result['error_codes']);
    }

    public function test_verify_rejects_action_mismatch(): void
    {
        config([
            'recaptcha.site_key' => 'site-key',
            'recaptcha.secret_key' => 'secret-key',
            'recaptcha.score_threshold' => 0.5,
            'recaptcha.verify_url' => 'https://www.google.com/recaptcha/api/siteverify',
        ]);

        Http::fake([
            'www.google.com/recaptcha/api/siteverify' => Http::response([
                'success' => true,
                'score' => 0.9,
                'action' => 'register',
            ]),
        ]);

        $result = app(RecaptchaService::class)->verify('valid-token', 'login');

        $this->assertFalse($result['success']);
        $this->assertContains('action-mismatch', $result['error_codes']);
    }
}
