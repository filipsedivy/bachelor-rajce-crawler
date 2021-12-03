<?php
declare(strict_types=1);

namespace App\Controllers;

use Apitte\Core;
use App;
use Predis;

#[Core\Annotation\Controller\Path('/storage')]
final class StorageController implements Core\UI\Controller\IController
{
	public function __construct(
		private Predis\Client $predis
	) {
	}


	#[Core\Annotation\Controller\Path('/flush')]
	#[Core\Annotation\Controller\Method('DELETE')]
	public function flush(Core\Http\ApiRequest $request, Core\Http\ApiResponse $response): Core\Http\ApiResponse
	{
		$runTime = App\Core\Utils\Debugger::runTime(function (): void {
			$this->predis->flushall();
		});

		return $response
			->writeJsonBody([
				'runTime' => $runTime,
				'flush' => true,
			]);
	}
}
