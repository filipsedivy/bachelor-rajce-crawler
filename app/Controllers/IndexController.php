<?php
declare(strict_types=1);

namespace App\Controllers;

use Apitte\Core\Annotation\Controller\Method;
use Apitte\Core\Annotation\Controller\Path;
use Apitte\Core\Http\ApiRequest;
use Apitte\Core\Http\ApiResponse;
use Apitte\Core\UI\Controller\IController;
use Nette\Bridges\ApplicationLatte\LatteFactory;
use Nette\DI\Container;
use Nette\Utils\Arrays;
use Nette\Utils\FileSystem;

#[Path('/')]
final class IndexController implements IController
{
    public function __construct(
        private LatteFactory $latteFactory,
        private Container    $container
    )
    {
    }


    #[Path('/')]
    #[Method('GET')]
    public function index(ApiRequest $request, ApiResponse $response): ApiResponse
    {
        $logo = FileSystem::read(dirname(__DIR__, 2) . '/.docs/logo.png');
        $engine = $this->latteFactory->create();
        $response->writeBody($engine->renderToString(
            dirname(__DIR__) . '/Templates/Index.latte',
            [
                'logo' => base64_encode($logo),
                'version' => Arrays::get($this->container->getParameters(), 'version', 'N/A')
            ]
        ));

        return $response;
    }
}
