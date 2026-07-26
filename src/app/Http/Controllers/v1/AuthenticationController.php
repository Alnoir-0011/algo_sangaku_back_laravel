<?php

namespace App\Http\Controllers\v1;

use App\Http\Controllers\v1\BaseController;
use Illuminate\Http\Request;
use App\Http\Requests\AuthenticationRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use Google_Client;

class AuthenticationController extends BaseController
{

    /**
     * Store a newly created resource in storage.
     */
    public function store(AuthenticationRequest $request)
    {
        $id_token = $request->input('token');

        $client = new Google_Client(['client_id' => env('GOOGLE_CLIENT_ID')]);
        $payload = $client->verifyIdToken($id_token);

        if ($payload) {
            $user = User::firstOrCreate(
                ['provider' => 'google', 'uid' => $payload['sub']],
                [
                    'name' => $payload['name'],
                    'email' => $payload['email'],
                    'nickname' => $payload['name']
                ]
            );

            $token = $user->createToken('auth_token')->plainTextToken;

            return (new UserResource($user))
            ->response()
            ->header('AccessToken', $token);
        } else {
            return response()->json(['error' => 'Invalid ID token'], 401);
        }
    }


    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request)
    {
        $request->user()->currentAccessToken()->delete();
        return response()->json(['message' => 'signout successful'], 200);
    }
}
