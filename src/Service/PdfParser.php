<?php

namespace Andr\ChmTideReader\Service;

use Andr\ChmTideReader\Entity\Location;
use Andr\ChmTideReader\Entity\Location\Point;
use Andr\ChmTideReader\Foundation\Configuration;
use Smalot\PdfParser\Page;
use Smalot\PdfParser\Parser;

class PdfParser
{
    protected array $months = [
        "Janeiro",
        "Fevereiro",
        "Março",
        "Abril",
        "Maio",
        "Junho",
        "Julho",
        "Agosto",
        "Setembro",
        "Outubro",
        "Novembro",
        "Dezembro"
    ];

    protected array $weekdays = ["QUI", "SEX", "SAB", "DOM", "SEG", "TER", "QUA", "SÁB"];

    public function __construct(
        protected Configuration $configuration,
        protected Parser $parser
    ) {}
    public function getListingFiles(): array
    {
        return array_map(
            fn($file) => $this->configuration->tidePdfPath . "/" . $file,
            array_filter(
                scandir($this->configuration->tidePdfPath),
                fn($file) => is_file($this->configuration->tidePdfPath . "/" . $file)
            )
        );
    }

    public function processFiles(array $listingFiles)
    {
        foreach ($listingFiles as $file) {
            $this->processFile($file);
            break;
        }
    }

    public function processFile(string $file): void
    {
        $pdf = $this->parser->parseFile($file);
        $location = new Location();
        $location->marineId = $this->extractMarineLocationIdFromFilename($file);
        foreach ($pdf->getPages() as $page) {
            $this->parsePage($page, $location);
        }
    }
    public function extractMarineLocationIdFromFilename(string $filename): string
    {
        return
            str_replace($this->configuration->tidePdfPath . "/", "", $filename)
            |> (fn($string) => explode(separator: "-", limit: 2, string: $string))
            |> array_first(...)
            |> trim(...);
    }
    public function parsePage(Page $page, Location $location): void
    {
        $textArray = $page->getTextArray();
        array_walk($textArray, function ($value, $key) use ($location, $textArray) {
            $type = $this->discoverType($value, $textArray[$key + 1]);
            // echo "{$type} - {$value}" . PHP_EOL;
            if (is_callable($type)) {
                $type($location, $key, $textArray);
            }
        });
    }

    public function fillLocation(Location $location, int $currentKey, array &$textArray): void
    {
        if ($location->isFilled()) {
            return;
        }
        $location->name = str_replace(" - " . date("Y"), "", $textArray[$currentKey]);
        $string = str_replace("&#39;", "'", $textArray[$currentKey + 1]);
        preg_match("/Latitude (?P<latitude>.*) Longitude (?P<longitude>.*) Fuso (?P<timezone>.*) horas/", $string, $matches);
        $location->point = Point::fromDMS($matches["latitude"], $matches["longitude"]);
        str_replace(["UTC ", "."], ["", ":"], $matches['timezone'])
        |> (fn($str) => str_pad($str, 6, "0", STR_PAD_RIGHT))
        |> (fn($str) => $location->timeZone = new \DateTimeZone($str));
        preg_match("/dio (?P<meanSeaLevel>[0-9.]*)\s?m Carta/", $textArray[$currentKey + 2], $meanSeaLevelMatches);
        $location->meanSeaLevel = $meanSeaLevelMatches["meanSeaLevel"]
        |> (fn($str) => (float) trim($str));
    }

    public function addTidesOfTheDay(Location $location, int $currentKey, array &$textArray): void {}


    public function discoverType(string $text, string $nextText): string|callable
    {
        return match (true) {
            preg_match("/([A-ZÀ-Ú()]+ )+-.*/", $text) === 1 && str_starts_with($nextText, "Latitude") => [$this, "fillLocation"],
            str_starts_with($text, "Latitude") => "position",
            in_array($text, $this->months) => "month",
            is_numeric($text) && in_array($nextText, $this->weekdays) => [$this, "addTidesOfTheDay"],
            preg_match("/\d{4} {3,4}-?\d{1,2}\.\d{2}/", $text) === 1 => "timetide",
            default => "unknown",
        };
    }
}
/*
"13"
"BARRA NORTE - ARCO LAMOSO - 2026"
"Latitude 01° 26&#39;.1 N Longitude 49° 13&#39;.3 W Fuso UTC -03.0 horas"
"CHM 26 Componentes Nível Médio 1.9 m Carta 21300"
"Janeiro"
"HORA  ALT(m) HORA  ALT(m)"
"01"
"QUI"
"0114    0.29"
"0755    3.37"
"1342    0.83"
"2002    3.59"
*/
