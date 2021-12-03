<?php
declare(strict_types=1);

namespace App\Core;

use Nette;

trait Arrays
{
	/**
	 * @template T
	 * @param  array<T> $array
	 * @param  array-key[] $key
	 * @return ?T
	 * @throws Nette\InvalidArgumentException
	 */
	private static function findValueFromKeys(array $array, array $keys, ?string $default = null)
	{
		foreach ($keys as $key) {
			try {
				return Nette\Utils\Arrays::get($array, $key);
			} catch (Nette\InvalidArgumentException $exception) {
			}
		}

		if ($default === null) {
			return null;
		}

		throw new Nette\InvalidArgumentException('Parameter ' . implode('/', $keys) . 'not exists');
	}
}
