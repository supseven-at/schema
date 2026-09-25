<?php

declare(strict_types=1);

use Supseven\Schema\Builder\SchemaBuilderInterface;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return static function (ContainerConfigurator $containerConfigurator): void {
    $services = $containerConfigurator->services();

    $services->defaults()
        ->public()
        ->autowire()
        ->autoconfigure();

    $services
        ->instanceof(SchemaBuilderInterface::class)
        ->tag('schema.builder');

    $services->load('Supseven\\Schema\\Builder\\', __DIR__ . '/../Classes/Builder/*');
    $services->load('Supseven\\Schema\\DataProcessing\\', __DIR__ . '/../Classes/DataProcessing/*');
    $services->load('Supseven\\Schema\\Service\\', __DIR__ . '/../Classes/Service/*');
    $services->load('Supseven\\Schema\\Utility\\', __DIR__ . '/../Classes/Utility/*');
};
