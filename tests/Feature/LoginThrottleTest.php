<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use App\Services\AuthService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Tests\TestCase;

class LoginThrottleTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        RateLimiter::clear($this->key('victim@example.test'));
    }

    private function key(string $email): string
    {
        return 'login:'.hash('sha256', Str::lower($email));
    }

    public function test_login_is_throttled_after_five_failed_attempts(): void
    {
        User::factory()->create([
            'email' => 'victim@example.test',
            'password' => 'correct-password',
            'role' => 'admin',
            'is_active' => true,
        ]);

        for ($i = 0; $i < 5; $i++) {
            $this->postJson('/api/login', [
                'email' => 'victim@example.test',
                'password' => 'wrong-password',
            ])->assertStatus(422);
        }

        $this->postJson('/api/login', [
            'email' => 'victim@example.test',
            'password' => 'wrong-password',
        ])->assertStatus(429)
            ->assertJsonPath('success', false);
    }

    public function test_successful_login_does_not_consume_attempt_quota(): void
    {
        User::factory()->create([
            'email' => 'winner@example.test',
            'password' => 'correct-password',
            'role' => 'admin',
            'is_active' => true,
        ]);

        RateLimiter::clear($this->key('winner@example.test'));

        for ($i = 0; $i < 3; $i++) {
            $this->postJson('/api/login', [
                'email' => 'winner@example.test',
                'password' => 'correct-password',
            ])->assertOk();
        }

        $this->assertSame(0, RateLimiter::attempts($this->key('winner@example.test')));
    }

    public function test_throttle_is_scoped_per_email(): void
    {
        User::factory()->create([
            'email' => 'locked@example.test',
            'password' => 'correct-password',
            'role' => 'admin',
            'is_active' => true,
        ]);
        User::factory()->create([
            'email' => 'other@example.test',
            'password' => 'correct-password',
            'role' => 'admin',
            'is_active' => true,
        ]);

        RateLimiter::clear($this->key('locked@example.test'));
        RateLimiter::clear($this->key('other@example.test'));

        for ($i = 0; $i < 5; $i++) {
            $this->postJson('/api/login', [
                'email' => 'locked@example.test',
                'password' => 'wrong-password',
            ])->assertStatus(422);
        }

        $this->postJson('/api/login', [
            'email' => 'locked@example.test',
            'password' => 'wrong-password',
        ])->assertStatus(429);

        $this->postJson('/api/login', [
            'email' => 'other@example.test',
            'password' => 'correct-password',
        ])->assertOk();
    }

    public function test_correct_password_is_blocked_when_bucket_is_full(): void
    {
        User::factory()->create([
            'email' => 'victim2@example.test',
            'password' => 'correct-password',
            'role' => 'admin',
            'is_active' => true,
        ]);

        RateLimiter::clear($this->key('victim2@example.test'));

        for ($i = 0; $i < 5; $i++) {
            $this->postJson('/api/login', [
                'email' => 'victim2@example.test',
                'password' => 'wrong-password',
            ])->assertStatus(422);
        }

        $this->postJson('/api/login', [
            'email' => 'victim2@example.test',
            'password' => 'correct-password',
        ])->assertStatus(429);
    }

    public function test_throttle_key_stays_within_cache_column_limit(): void
    {
        $longEmail = str_repeat('a', 240).'@example.test';
        $prefix = (string) config('cache.prefix');

        $service = $this->app->make(AuthService::class);
        $method = new \ReflectionMethod($service, 'throttleKey');
        $key = (string) $method->invoke($service, $longEmail);

        $this->assertLessThanOrEqual(
            255,
            strlen($prefix.$key),
            'Throttle cache key must fit the cache table varchar(255) column.'
        );

        $this->assertLessThanOrEqual(255, strlen($prefix.$key.':timer'));
    }

    public function test_long_email_does_not_break_the_throttle(): void
    {
        $this->app['config']->set('cache.default', 'database');

        $longEmail = str_repeat('a', 240).'@example.test';

        User::factory()->create([
            'email' => $longEmail,
            'password' => 'correct-password',
            'role' => 'admin',
            'is_active' => true,
        ]);

        RateLimiter::clear($this->key($longEmail));

        for ($i = 0; $i < 5; $i++) {
            $this->postJson('/api/login', [
                'email' => $longEmail,
                'password' => 'wrong-password',
            ])->assertStatus(422);
        }

        $this->postJson('/api/login', [
            'email' => $longEmail,
            'password' => 'wrong-password',
        ])->assertStatus(429);
    }

    public function test_deactivated_account_is_rejected_without_consuming_quota(): void
    {
        User::factory()->create([
            'email' => 'inactive@example.test',
            'password' => 'correct-password',
            'role' => 'admin',
            'is_active' => false,
        ]);

        RateLimiter::clear($this->key('inactive@example.test'));

        $this->postJson('/api/login', [
            'email' => 'inactive@example.test',
            'password' => 'correct-password',
        ])->assertStatus(403);

        $this->assertSame(0, RateLimiter::attempts($this->key('inactive@example.test')));
    }
}
