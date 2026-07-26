<?php

namespace App\Services;

use Google_Client;

class GoogleAuthService
{
    public function verifyIdToken(string $idToken): array|false
    {
        $client = new Google_Client([
            'client_id' => config('services.google.client_id'),
        ]);

        return $client->verifyIdToken($idToken);
    }
}
