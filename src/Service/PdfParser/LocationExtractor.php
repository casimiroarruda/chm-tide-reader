<?php

namespace Andr\ChmTideExtractor\Service\PdfParser;

use Andr\ChmTideExtractor\Domain\Location;
use Andr\ChmTideExtractor\Domain\Location\Point;

class LocationExtractor
{
    /** @param array<string> &$pageArray */
    public function __construct(
        private array &$pageArray,
        private int $index
    ) {}

    public function fillLocation(Location $location): void
    {
        $latitude = $this->extractLatitudeFromPageArray();
        $longitude = $this->extractLongitudeFromPageArray();
        $location->point = Point::fromDMS($longitude, $latitude);
        $location->timezone = $this->extractTimeZoneFromPageArray();
        $location->meanSeaLevel = (string) $this->extractMeanSeaLevelFromPageArray();
    }

    public function extractLatitudeFromPageArray(): string
    {
        while (preg_match("/Latitude (?P<latitude>\d{1,2}°\s?\d{1,2}(&#39;|')\.?\d?\s[NSWE])/", $this->pageArray[$this->index], $matches) !== 1) {
            $this->index++;
        }
        return str_replace("&#39;", "'", $matches["latitude"]);
    }

    public function extractLongitudeFromPageArray(): string
    {
        while (preg_match("/Longitude (?P<longitude>\d{1,2}°\s?\d{1,2}(&#39;|')\.?\d?\s[NSWE])/", $this->pageArray[$this->index], $matches) !== 1) {
            $this->index++;
        }
        return str_replace("&#39;", "'", $matches["longitude"]);
    }

    public function extractTimeZoneFromPageArray(): \DateTimeZone
    {
        while (preg_match("/Fuso (?P<timezone>.*) horas/", $this->pageArray[$this->index], $matches) !== 1) {
            $this->index++;
        }
        return str_replace(["UTC ", "."], ["", ":"], $matches['timezone'])
        |> (fn($str) => str_pad($str, 6, "0", STR_PAD_RIGHT))
        |> (fn($str) =>  new \DateTimeZone($str));
    }

    public function extractMeanSeaLevelFromPageArray(): float
    {
        while (preg_match("/dio (?P<meanSeaLevel>[0-9.,]*)\s?m/", $this->pageArray[$this->index], $meanSeaLevelMatches) !== 1) {
            $this->index++;
        };
        return $meanSeaLevelMatches["meanSeaLevel"]
        |> (fn($str) => str_replace(",", ".", $str))
        |> (fn($str) => (float) trim($str));
    }
}
