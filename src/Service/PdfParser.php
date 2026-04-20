<?php

namespace Andr\ChmTideExtractor\Service;

use Andr\ChmTideExtractor\Domain\Location;
use Andr\ChmTideExtractor\Foundation\Configuration;
use Andr\ChmTideExtractor\Service\PdfParser\PageProcessor;
use Generator;
use Smalot\PdfParser\Parser;

class PdfParser
{
    protected Configuration $configuration;

    public function __construct(
        protected Parser $parser
    ) {}

    public function configure(Configuration $configuration): void
    {
        $this->configuration = $configuration;
    }

    public function fromCommand(): Generator
    {
        if (!isset($this->configuration)) {
            throw new \Exception("Configuration not set");
        }
        $files = $this->getListingFiles();
        foreach ($files as $file) {
            yield $this->processFile($file);
        }
    }

    public function getListingFiles(): array
    {
        return array_map(
            fn($file) => $this->configuration->tidePdfPath . $this->configuration->year . "/" . $file,
            array_filter(
                scandir($this->configuration->tidePdfPath . $this->configuration->year),
                fn($file) => is_file($this->configuration->tidePdfPath . $this->configuration->year . "/" . $file)
            )
        );
    }

    public function processFiles(array $listingFiles): array
    {
        if (!isset($this->configuration)) {
            throw new \Exception("Configuration not set");
        }
        return array_map(
            fn($file) => $this->processFile($file),
            $listingFiles
        ) |> array_values(...);
    }

    public function processFile(string $file): Location
    {
        $pdf = $this->parser->parseFile($file);
        $location = new Location();
        $location->marineId = $this->extractMarineLocationIdFromFilename($file);
        foreach ($pdf->getPages() as $page) {
            (new PageProcessor($page, $location, $this->configuration->year))->process();
        }
        return $location;
    }


    public function extractMarineLocationIdFromFilename(string $filename): string
    {
        return
            basename($filename)
            |> (fn($string) => explode(separator: "-", limit: 2, string: $string))
            |> array_first(...)
            |> trim(...);
    }
}
