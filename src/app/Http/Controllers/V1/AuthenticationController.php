<?php

namespace App\Http\Controllers\V1;

use App\Exceptions\InvalidGoogleTokenException;
use App\Http\Requests\AuthenticationRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Services\GoogleAuthService;
use Illuminate\Http\Request;

class AuthenticationController extends BaseController
{
    public function __construct(
        private GoogleAuthService $googleAuthService
    ) {}

    /**
     * Store a newly created resource in storage.
     */
    public function store(AuthenticationRequest $request)
    {
        $idToken = $request->input('token');

        $payload = $this->googleAuthService->verifyIdToken($idToken);

        if (! $payload) {
            throw new InvalidGoogleTokenException;
        }

        $user = User::firstOrCreate(
            ['provider' => 'google', 'uid' => $payload['sub']],
            [
                'name' => $payload['name'],
                'email' => $payload['email'],
                'nickname' => $payload['name'],
            ]
        );

        $token = $user->createToken('auth_token')->plainTextToken;

        return (new UserResource($user))
            ->response()
            ->setStatusCode(200)
            ->header('AccessToken', $token);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->noContent();
    }
}
