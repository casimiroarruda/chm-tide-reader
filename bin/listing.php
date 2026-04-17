<?php

use Andr\ChmTideReader\Service\Listing;
use Andr\ChmTideReader\Service\PdfParser;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\PhpFileLoader;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\Dotenv\Dotenv;

$baseDir = dirname(__DIR__);
require $baseDir . '/vendor/autoload.php';
$dotenv = new Dotenv();
$dotenv->load($baseDir . '/.env');


$containerBuilder = new ContainerBuilder();

$containerBuilder->setParameter('tide_pdf_path', $baseDir . "/" . ($_ENV["TIDE_PDF_PATH"] ?? "tide-pdf"));
$containerBuilder->setParameter('chm_site_host', $_ENV["CHM_SITE_HOST"] ?? "https://marinha.mil.br");
$containerBuilder->setParameter('year', $_ENV["YEAR"] ?? 2026);

$loader = new PhpFileLoader($containerBuilder, new FileLocator($baseDir . '/config'));
$loader->load('services.php');

$containerBuilder->compile();

$pdfParser = $containerBuilder->get(PdfParser::class);

$pdfParser->getListingFiles()
|> $pdfParser->processFiles(...);
