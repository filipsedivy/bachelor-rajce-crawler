<?php

namespace App\Core;

use Goutte;

final class BrowserKitFactory
{
    public function create(): Goutte\Client
    {
        return new Goutte\Client;
    }
}
