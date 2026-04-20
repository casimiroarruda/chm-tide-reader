<?php

namespace Andr\ChmTideExtractor\Service;

use Andr\ChmTideExtractor\Domain\Location;
use Andr\ChmTideExtractor\Domain\Location\Point;
use Andr\ChmTideExtractor\Domain\Tide;
use Andr\ChmTideExtractor\Domain\Tide\Type;
use Andr\ChmTideExtractor\Foundation\Configuration;
use Andr\ChmTideExtractor\Foundation\Month;
use Andr\ChmTideExtractor\Service\PdfParser\LocationExtractor;
use Generator;
use Smalot\PdfParser\Page;
use Smalot\PdfParser\Parser;

class PdfParser
{
    protected array $weekdays = ["QUI", "SEX", "SAB", "DOM", "SEG", "TER", "QUA", "SÁB"];
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
            $this->parsePage($page, $location);
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

    public function parsePage(Page $page, Location $location): void
    {
        $textArray = $page->getTextArray();
        $meta = ["year" => $this->configuration->year];
        array_walk($textArray, function ($value, $key) use ($location, &$textArray, &$meta) {
            if (!isset($textArray[$key + 1])) {
                return;
            }
            $type = $this->discoverType($value, $textArray[$key + 1]);
            if ($type === "month") {
                $meta["month"] = $value;
            }

            if (is_callable($type)) {
                $type($location, $key, $textArray, $meta);
            }
        });
    }

    public function fillLocation(Location $location, int $currentKey, array &$textArray, array $meta = []): void
    {
        if ($location->isFilled()) {
            return;
        }
        $location->name = str_replace(" - " . date("Y"), "", $textArray[$currentKey]);
        $locationExtractor = new LocationExtractor($textArray, $currentKey + 1);
        $locationData = $locationExtractor->extract();
        $location->point = Point::fromDMS($locationData["longitude"], $locationData["latitude"]);
        $location->timezone = $locationData["timezone"];
        $location->meanSeaLevel = $locationData["meanSeaLevel"];
    }

    public function addTidesOfTheDay(Location $location, int $currentKey, array &$textArray, array $meta = []): void
    {
        $day = ltrim($textArray[$currentKey], "0");
        $month = Month::get($meta["month"])->value;
        $year = $meta["year"];
        $tideDataIndex = $currentKey + 2;
        while (isset($textArray[$tideDataIndex]) && preg_match("/(?P<hour>\d{2})(?P<minute>\d{2})\s+(?P<height>-?\d{1,2}\.\d{1,2})/", $textArray[$tideDataIndex], $matches) === 1) {
            $time = new \DateTime("{$year}-{$month}-{$day} {$matches['hour']}:{$matches['minute']}", $location->timezone);
            $height = (float) $matches["height"];
            $type = Type::determine($height, $location->meanSeaLevel);
            $location->tides->add(new Tide($time, $height, $type, $location));
            $matches = [];
            $tideDataIndex++;
        }
    }

    public function discoverType(string $text, string $nextText): string|callable
    {
        return match (true) {
            preg_match("/([A-ZÀ-Ú()]+ )+-.*/", $text) === 1 && str_starts_with($nextText, "Latitude") => [$this, "fillLocation"],
            str_starts_with($text, "Latitude") => "position",
            Month::get($text) !== false => "month",
            is_numeric($text) && in_array($nextText, $this->weekdays) => [$this, "addTidesOfTheDay"],
            preg_match("/\d{4} {3,4}-?\d{1,2}\.\d{2}/", $text) === 1 => "timetide",
            default => "unknown",
        };
    }
}
