<?php
declare(strict_types=1);

namespace App\Core;


trait Controller
{
	/**
	 * @return array{error: bool, msg: string}
	 */
	public function error(string $message): array
	{
		$body = [
			'error' => true,
			'msg' => $message,
		];

		return $body;
	}
}
