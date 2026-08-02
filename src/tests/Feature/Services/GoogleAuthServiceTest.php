<?php

use App\Services\GoogleAuthService;

describe('GoogleAuthService::verifyIdToken', function () {
    test('throws when GOOGLE_CLIENT_ID is not configured', function () {
        config(['services.google.client_id' => null]);

        expect(fn () => (new GoogleAuthService)->verifyIdToken('dummy-token'))
            ->toThrow(RuntimeException::class, 'GOOGLE_CLIENT_ID is not configured.');
    });
});
