<?php

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use Andr\ChmTideExtractor\Command\ParseAll;
use Andr\ChmTideExtractor\Command\ParseOne;
use Andr\ChmTideExtractor\Foundation\Configuration;
use Andr\ChmTideExtractor\Repository\Location;
use Andr\ChmTideExtractor\Repository\Tide;
use Andr\ChmTideExtractor\Service\PdfParser;
use Andr\ChmTideExtractor\Service\TideStore;
use Smalot\PdfParser\Parser;
use PDO;
use Symfony\Component\Console\Application;

return function (ContainerConfigurator $container): void {
    $services = $container->services();
    $parameters = $container->parameters();

    $services->defaults()
        ->autowire()
        ->autoconfigure()
        ->public();

    $parameters
        ->set('tide_pdf_path', dirname(__DIR__) . '/' . $_ENV['TIDE_PDF_PATH'])
        ->set('year', $_ENV['YEAR'])
        ->set('db_dsn', $_ENV['DB_DSN'])
        ->set('db_username', $_ENV['DB_USER'])
        ->set('db_password', $_ENV['DB_PASSWORD'])
        ->set('db_schema', $_ENV['DB_SCHEMA'])
        ->set('app_root', dirname(__DIR__));

    $services->set(Configuration::class)
        ->arg('$tidePdfPath', param('tide_pdf_path'))
        ->arg('$year', param('year'));

    $services->set(Parser::class);

    $services->set(PdfParser::class)
        ->arg('$parser', service(Parser::class));

    $services->set('pdo', PDO::class)
        ->arg('$dsn', param('db_dsn'))
        ->arg('$username', param('db_username'))
        ->arg('$password', param('db_password'))
        ->call('exec', ['SET search_path TO ' . param('db_schema')]);

    $services->set(Location::class)
        ->arg('$pdo', service('pdo'));

    $services->set(Tide::class)
        ->arg('$pdo', service('pdo'));

    $services->set(TideStore::class)
        ->arg('$locationRepository', service(Location::class))
        ->arg('$tideRepository', service(Tide::class));

    $services->set(ParseAll::class)
        ->arg('$pdfParser', service(PdfParser::class))
        ->arg('$store', service(TideStore::class));

    $services->set(ParseOne::class)
        ->arg('$pdfParser', service(PdfParser::class))
        ->arg('$store', service(TideStore::class))
        ->arg('$appRoot', param('app_root'));

    $services->set('app', Application::class)
        ->call('addCommand', [service(ParseAll::class)])
        ->call('addCommand', [service(ParseOne::class)]);
};
