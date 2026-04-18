<?php

use Andr\ChmTideExtractor\Service\PdfParser;
use Andr\ChmTideExtractor\Service\TideStore;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\PhpFileLoader;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\Dotenv\Dotenv;

$baseDir = dirname(__DIR__);
require $baseDir . '/vendor/autoload.php';
(new Dotenv())->load($baseDir . '/.env');

$containerBuilder = new ContainerBuilder();

$loader = new PhpFileLoader($containerBuilder, new FileLocator($baseDir . '/config'));
$loader->load('services.php');

$containerBuilder->compile();

$pdfParser = $containerBuilder->get(PdfParser::class);
$locations = $pdfParser($_ENV['YEAR']);

// $tideStore = $containerBuilder->get(TideStore::class);
// $tideStore->saveLocations(...$locations);
