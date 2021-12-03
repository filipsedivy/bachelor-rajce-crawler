<?php
declare(strict_types=1);

namespace App\Core\Utils;

use Nette;
use Tracy;

final class Debugger
{
	use Nette\StaticClass;

	public static function runTime(callable $callback): float
	{
		Tracy\Debugger::timer();
		$callback();
		return Tracy\Debugger::timer();
	}
}
