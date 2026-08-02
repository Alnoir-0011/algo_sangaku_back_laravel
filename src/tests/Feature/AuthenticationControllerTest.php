<?php

use App\Models\User;
use App\Services\GoogleAuthService;
use Laravel\Sanctum\Sanctum;

describe('AuthenticationController', function () {
    describe('POST /api/v1/authentication', function () {
        test('creates a new user on first google login', function () {
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

            expect($response->status())->toBe(200);

            expect($response->headers->has('AccessToken'))->toBeTrue();

            expect(User::where([
                'provider' => 'google',
                'uid' => 'google-user-id',
                'email' => 'test@example.com',
            ])->exists())->toBeTrue();
        });

        test('returns the existing user when the user already exists', function () {
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

            expect($response->status())->toBe(200);

            expect(User::count())->toBe(1);
        });

        test('returns 401 when the google token is invalid', function () {
            $this->mock(GoogleAuthService::class, function ($mock) {
                $mock->shouldReceive('verifyIdToken')
                    ->once()
                    ->andReturn(false);
            });

            $response = $this->postJson('/api/v1/authentication', [
                'token' => 'invalid-token',
            ]);

            expect($response->status())->toBe(401);

            expect($response->json())->toMatchArray([
                'error' => 'Invalid ID token',
            ]);
        });
    });

    describe('DELETE /api/v1/authentication', function () {
        test('allows an authenticated user to logout', function () {
            $user = User::factory()->create();

            Sanctum::actingAs($user);

            $response = $this->deleteJson('/api/v1/authentication');

            expect($response->status())->toBe(200);

            expect($response->json())->toMatchArray([
                'message' => 'signout successful',
            ]);
        });
    });
});
