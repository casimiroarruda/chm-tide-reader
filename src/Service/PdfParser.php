<?php

namespace Andr\ChmTideReader\Service;

use Andr\ChmTideReader\Entity\Location;
use Andr\ChmTideReader\Entity\Location\Point;
use Andr\ChmTideReader\Entity\Tide;
use Andr\ChmTideReader\Entity\Tide\Type;
use Andr\ChmTideReader\Foundation\Configuration;
use Smalot\PdfParser\Page;
use Smalot\PdfParser\Parser;

class PdfParser
{
    protected array $months = [
        "Janeiro" => "01",
        "Fevereiro" => "02",
        "Março" => "03",
        "Abril" => "04",
        "Maio" => "05",
        "Junho" => "06",
        "Julho" => "07",
        "Agosto" => "08",
        "Setembro" => "09",
        "Outubro" => "10",
        "Novembro" => "11",
        "Dezembro" => "12"
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
            $location = $this->processFile($file);
            break;
        }
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
            str_replace($this->configuration->tidePdfPath . "/", "", $filename)
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

    public function addTidesOfTheDay(Location $location, int $currentKey, array &$textArray, array $meta = []): void
    {
        $day = ltrim($textArray[$currentKey], "0");
        $month = $this->months[$meta["month"]];
        $year = $meta["year"];
        $tides = array_slice($textArray, $currentKey + 2, 4);
        foreach ($tides as $tide) {
            if (preg_match("/(?P<hour>\d{2})(?P<minute>\d{2}) {3,4}(?P<height>-?\d{1,2}\.\d{1,2})/", $tide, $matches) !== 1) {
                continue;
            };
            $time = new \DateTime("{$year}-{$month}-{$day} {$matches['hour']}:{$matches['minute']}", $location->timeZone);
            $height = (float) $matches["height"];
            $type = $height > $location->meanSeaLevel ? Type::HIGH : Type::LOW;
            $location->tides->add(new Tide($time, $height, $type));
        }
    }


    public function discoverType(string $text, string $nextText): string|callable
    {
        return match (true) {
            preg_match("/([A-ZÀ-Ú()]+ )+-.*/", $text) === 1 && str_starts_with($nextText, "Latitude") => [$this, "fillLocation"],
            str_starts_with($text, "Latitude") => "position",
            array_key_exists($text, $this->months) => "month",
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
