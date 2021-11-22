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
			$page = (int) $request->getQueryParam('page', 0);
			$user = $this->service->getUser($entity, $page);
			return $response->writeJsonBody($user);
		} catch (\InvalidArgumentException $exception) {
			throw $exception;
			return $response->writeJsonBody($this->error('User not found'));
		}
	}


	#[Path('/full/{user}')]
	#[Method('GET')]
	#[RequestParameter(name: 'user', type: 'string', description: 'User identification')]
	public function full(ApiRequest $request, ApiResponse $response): ApiResponse
	{
		$entity = $this->service->check($request->getParameters());
		try {
			$user = $this->service->getFullUser($entity);
			return $response->writeJsonBody($user);
		} catch (\InvalidArgumentException $exception) {
			return $response->writeJsonBody($this->error('User not found'));
		}
	}
}
