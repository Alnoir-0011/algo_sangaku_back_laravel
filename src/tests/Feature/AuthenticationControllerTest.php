<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\User;
use App\Services\GoogleAuthService;
use Tests\TestCase;
use Laravel\Sanctum\Sanctum;


class AuthenticationControllerTest extends TestCase
{
    use RefreshDatabase;
    /**
     * A basic test example.
     */
    public function test_creates_new_user_on_first_google_login(): void
    {
        $this->mock(GoogleAuthService::class, function ($mock) {
            $mock->shouldReceive('verifyIdToken')
                ->once()
                ->andReturn([
                    'sub' => 'google-user-id',
                    'name' => 'Test User',
                    'email' => 'test@example.com',
                ]);
        });

        $response = $this->postJson('/api/v1/authentication', [
            'token' => 'dummy-token',
        ]);

        $response->assertOk();

        $response->assertHeader('AccessToken');

        $this->assertDatabaseHas('users', [
            'provider' => 'google',
            'uid' => 'google-user-id',
            'email' => 'test@example.com',
        ]);
    }

    public function test_returns_existing_user_when_user_already_exists(): void
    {
        $user = User::factory()->create([
            'provider' => 'google',
            'uid' => 'google-user-id',
            'email' => 'test@example.com',
            'name' => 'Test User',
            'nickname' => 'Test User',
        ]);

        $this->mock(GoogleAuthService::class, function ($mock) {
            $mock->shouldReceive('verifyIdToken')
                ->once()
                ->andReturn([
                    'sub' => 'google-user-id',
                    'name' => 'Test User',
                    'email' => 'test@example.com',
                ]);
        });

        $response = $this->postJson('/api/v1/authentication', [
            'token' => 'dummy-token',
        ]);

        $response->assertOk();

        $this->assertDatabaseCount('users', 1);
    }

    public function test_returns_401_when_google_token_is_invalid(): void
    {
        $this->mock(GoogleAuthService::class, function ($mock) {
            $mock->shouldReceive('verifyIdToken')
                ->once()
                ->andReturn(false);
        });

        $response = $this->postJson('/api/v1/authentication', [
            'token' => 'invalid-token',
        ]);

        $response
            ->assertUnauthorized()
            ->assertJson([
                'error' => 'Invalid ID token',
            ]);
    }

    public function test_authenticated_user_can_logout(): void
    {
        $user = User::factory()->create();

        Sanctum::actingAs($user);

        $response = $this->deleteJson('/api/v1/authentication');

        $response
            ->assertOk()
            ->assertJson([
                'message' => 'signout successful',
            ]);
    }
}
