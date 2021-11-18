<?php
declare(strict_types=1);

namespace App\Core;

use Goutte;

final class BrowserKitFactory
{
	public function create(): Goutte\Client
	{
		return new Goutte\Client;
	}
}
