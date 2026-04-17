<?php

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use Andr\ChmTideExtractor\Foundation\Configuration;
use Andr\ChmTideExtractor\Service\PdfParser;
use Smalot\PdfParser\Parser;

return function (ContainerConfigurator $container): void {
    $services = $container->services();

    $services->defaults()
        ->autowire()
        ->autoconfigure()
        ->public();

    $services->set(Configuration::class)
        ->arg('$tidePdfPath', param('tide_pdf_path'))
        ->arg('$year', param('year'));

    $services->set(Parser::class);

    $services->set(PdfParser::class)
        ->arg('$configuration', service(Configuration::class))
        ->arg('$parser', service(Parser::class));
};
