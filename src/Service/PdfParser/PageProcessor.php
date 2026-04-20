<?php

namespace Andr\ChmTideExtractor\Service\PdfParser;

use Andr\ChmTideExtractor\Domain\Location;
use Andr\ChmTideExtractor\Domain\Location\Point;
use Andr\ChmTideExtractor\Domain\Tide;
use Andr\ChmTideExtractor\Domain\Tide\Type;
use Andr\ChmTideExtractor\Foundation\Month;
use Smalot\PdfParser\Page;


class PageProcessor
{
    private array $weekdays = ["QUI", "SEX", "SAB", "DOM", "SEG", "TER", "QUA", "SÁB"];
    private Month $month;

    public function __construct(
        private Page $page,
        private Location $location,
        private string $year,
    ) {}

    public function process(): void
    {
        $textArray = $this->page->getTextArray();
        array_walk($textArray, function ($value, $key) use (&$textArray) {
            if (!isset($textArray[$key + 1])) {
                return;
            }
            $type = $this->discoverType($value, $textArray[$key + 1]);
            if ($type === "month") {
                $this->month = Month::get($value);
            }

            if (is_callable($type)) {
                $type($key, $textArray);
            }
        });
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

    public function fillLocation(int $currentKey, array &$textArray): void
    {
        if ($this->location->isFilled()) {
            return;
        }
        $this->location->name = str_replace(" - " . $this->year, "", $textArray[$currentKey]);
        $locationExtractor = new LocationExtractor($textArray, $currentKey + 1);
        $locationData = $locationExtractor->extract();
        $this->location->point = Point::fromDMS($locationData["longitude"], $locationData["latitude"]);
        $this->location->timezone = $locationData["timezone"];
        $this->location->meanSeaLevel = $locationData["meanSeaLevel"];
    }

    public function addTidesOfTheDay(int $currentKey, array &$textArray): void
    {
        $day = ltrim($textArray[$currentKey], "0");
        $month = $this->month->value;
        $year = $this->year;
        $tideDataIndex = $currentKey + 2;
        while (isset($textArray[$tideDataIndex]) && preg_match("/(?P<hour>\d{2})(?P<minute>\d{2})\s+(?P<height>-?\d{1,2}\.\d{1,2})/", $textArray[$tideDataIndex], $matches) === 1) {
            $time = new \DateTime("{$year}-{$month}-{$day} {$matches['hour']}:{$matches['minute']}", $this->location->timezone);
            $height = (float) $matches["height"];
            $type = Type::determine($height, $this->location->meanSeaLevel);
            $this->location->tides->add(new Tide($time, $height, $type, $this->location));
            $matches = [];
            $tideDataIndex++;
        }
    }
}
