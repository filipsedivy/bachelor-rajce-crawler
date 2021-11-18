<?php
declare(strict_types=1);

namespace App\Controllers;

use Apitte\Core\Annotation\Controller\Method;
use Apitte\Core\Annotation\Controller\Path;
use Apitte\Core\Annotation\Controller\RequestParameter;
use Apitte\Core\Http\ApiRequest;
use Apitte\Core\Http\ApiResponse;
use Apitte\Core\UI\Controller\IController;
use App\Core\Controller;
use App\Service\UserService;

#[Path('/user')]
final class UserController implements IController
{
	use Controller;

	public function __construct(
		private UserService $service
	) {
	}


	#[Path('/{user}')]
	#[Method('GET')]
	#[RequestParameter(name: 'user', type: 'string', description: 'User identification')]
	public function index(ApiRequest $request, ApiResponse $response): ApiResponse
	{
		$entity = $this->service->check($request->getParameters());
		try {
			return $response->writeJsonBody($this->service->getUser($entity));
		} catch (\InvalidArgumentException $exception) {
			return $response->writeJsonBody($this->error('User not found'));
		}
	}
}
