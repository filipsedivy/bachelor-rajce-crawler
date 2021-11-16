<?php

declare(strict_types=1);

use Apitte\Core\Application\IApplication;

require __DIR__ . '/../vendor/autoload.php';

App\Bootstrap::boot()
    ->createContainer()
    ->getByType(IApplication::class)
    ->run();
