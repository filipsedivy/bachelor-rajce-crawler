<?php
declare(strict_types=1);

namespace App\Controllers;

use Apitte\Core;
use App\Core\Controller;
use App\Service;

#[Core\Annotation\Controller\Path('/proxy')]
final class ProxyController implements Core\UI\Controller\IController
{
	use Controller;

	public function __construct(
		private Service\UserService $userService,
		private Service\AlbumService $albumService
	) {
	}


	#[Core\Annotation\Controller\Path('/user/{user}')]
	#[Core\Annotation\Controller\Method('GET')]
	#[Core\Annotation\Controller\RequestParameter(name: 'user', type: 'string', description: 'Username')]
	public function user(Core\Http\ApiRequest $request, Core\Http\ApiResponse $response): Core\Http\ApiResponse
	{
		try {
			$params = $this->userService->check($request->getParameters());

			if ($request->getQueryParam('full', null) === null) {
				$page = (int) $request->getQueryParam('page', 0);
				$result = $this->userService->getOnePage($params, $page);
			} else {
				$result = $this->userService->getAllPage($params);
			}

			return $response->writeJsonBody($result);
		} catch (\InvalidArgumentException $exception) {
			return $response->writeJsonBody($this->error('User not found'));
		}
	}


	#[Core\Annotation\Controller\Path('/user/{user}/{album}')]
	#[Core\Annotation\Controller\Method('GET')]
	#[Core\Annotation\Controller\RequestParameter(name: 'user', type: 'string', description: 'Username')]
	#[Core\Annotation\Controller\RequestParameter(name: 'album', type: 'string', description: 'Album')]
	public function album(Core\Http\ApiRequest $request, Core\Http\ApiResponse $response): Core\Http\ApiResponse
	{
		try {
			$params = $this->albumService->check($request->getParameters());
			return $response->writeJsonBody($this->albumService->getPhotos($params));
		} catch (\InvalidArgumentException $exception) {
			return $response->writeJsonBody($this->error('User not found'));
		}
	}
}
