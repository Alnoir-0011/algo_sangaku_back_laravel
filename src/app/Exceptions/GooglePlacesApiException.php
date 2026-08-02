<?php

namespace App\Exceptions;

use RuntimeException;

class GooglePlacesApiException extends RuntimeException
{
    public function __construct()
    {
        parent::__construct('Google Places API request failed');
    }
}
