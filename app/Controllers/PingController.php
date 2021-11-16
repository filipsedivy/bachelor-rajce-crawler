<?php declare(strict_types=1);

namespace App\Controllers;

use Apitte\Core\Http\ApiRequest;
use Apitte\Core\Http\ApiResponse;
use Apitte\Core\UI\Controller\IController;
use Apitte\Core\Annotation\Controller\Path;
use Apitte\Core\Annotation\Controller\Method;
use Nette\Utils\Json;

#[Path("/ping")]
final class PingController implements IController
{
    #[Path("/")]
    #[Method("GET")]
    public function index(ApiRequest $request, ApiResponse $response): ApiResponse
    {
        $body = [
            'ping' => 'pong'
        ];

        return $response->writeBody(Json::encode($body));
    }
}
