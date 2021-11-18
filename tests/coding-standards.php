<?php
declare(strict_types=1);

namespace App\Tests;

use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symplify\EasyCodingStandard\ValueObject\Option;

return function (ContainerConfigurator $containerConfigurator): void {
	$netteCSDir = dirname(__DIR__) . '/vendor/nette/coding-standard/preset';

	$containerConfigurator->import($netteCSDir . '/php80.php');
	$containerConfigurator->import($netteCSDir . '/clean-code.php');
	$containerConfigurator->import($netteCSDir . '/types.php');

	$parameters = $containerConfigurator->parameters();
	$parameters->set(Option::LINE_ENDING, "\n");
};
