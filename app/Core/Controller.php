<?php

namespace App\Core;

use Nette\Utils;

trait Controller
{
    public function error(string $message): array
    {
        $body = [
            'error' => true,
            'msg' => $message
        ];

        return $body;
    }
}
