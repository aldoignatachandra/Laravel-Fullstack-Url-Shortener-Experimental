<?php

namespace Tests\Feature\Http\Middleware;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use InvalidArgumentException;
use Tests\TestCase;

class SmartThrottleTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_requests_are_rate_limited_by_ip(): void
    {
        Route::middleware(['web', 'smart.throttle:strict-api'])->get('/test-smart-throttle-guest', fn () => 'ok');

        for ($attempt = 1; $attempt <= 10; $attempt++) {
            $this->withServerVariables(['REMOTE_ADDR' => '10.10.10.10'])
                ->get('/test-smart-throttle-guest')
                ->assertOk();
        }

        $this->withServerVariables(['REMOTE_ADDR' => '10.10.10.10'])
            ->get('/test-smart-throttle-guest')
            ->assertSessionHasErrors('rate_limit');

        $this->withServerVariables(['REMOTE_ADDR' => '10.10.10.11'])
            ->get('/test-smart-throttle-guest')
            ->assertOk();
    }

    public function test_authenticated_requests_are_rate_limited_by_user_id(): void
    {
        Route::middleware(['web', 'smart.throttle:strict-api'])->get('/test-smart-throttle-user', fn () => 'ok');

        $user = User::factory()->create();

        for ($attempt = 1; $attempt <= 10; $attempt++) {
            $this->actingAs($user)
                ->withServerVariables(['REMOTE_ADDR' => "10.10.20.{$attempt}"])
                ->get('/test-smart-throttle-user')
                ->assertOk();
        }

        $this->actingAs($user)
            ->withServerVariables(['REMOTE_ADDR' => '10.10.20.99'])
            ->get('/test-smart-throttle-user')
            ->assertSessionHasErrors('rate_limit');
    }

    public function test_json_requests_receive_too_many_requests_response(): void
    {
        Route::middleware(['web', 'smart.throttle:strict-api'])->get('/test-smart-throttle-json', fn () => ['ok' => true]);

        for ($attempt = 1; $attempt <= 10; $attempt++) {
            $this->withServerVariables(['REMOTE_ADDR' => '10.10.30.10'])
                ->getJson('/test-smart-throttle-json')
                ->assertOk();
        }

        $this->withServerVariables(['REMOTE_ADDR' => '10.10.30.10'])
            ->getJson('/test-smart-throttle-json')
            ->assertTooManyRequests()
            ->assertJsonStructure([
                'message',
                'retry_after',
            ]);
    }

    public function test_unknown_policy_fails_closed(): void
    {
        Route::middleware(['web', 'smart.throttle:unknown-policy'])->get('/test-smart-throttle-unknown', fn () => 'ok');

        $this->withoutExceptionHandling();
        $this->expectException(InvalidArgumentException::class);

        $this->get('/test-smart-throttle-unknown');
    }
}
