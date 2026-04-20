<?php

namespace Andr\ChmTideExtractor\Service\PdfParser;

use Andr\ChmTideExtractor\Domain\Location;
use Andr\ChmTideExtractor\Domain\Tide;
use Andr\ChmTideExtractor\Domain\Tide\Type;
use Andr\ChmTideExtractor\Foundation\Month;
use Smalot\PdfParser\Page;


class PageProcessor
{
    /** @var array<string> */
    private array $weekdays = ["QUI", "SEX", "SAB", "DOM", "SEG", "TER", "QUA", "SÁB"];
    private Month $month;
    /**  @var array<string> */
    private array $months;

    public function __construct(
        private Page $page,
        private Location $location,
        private string $year,
    ) {
        $this->months = array_column(Month::cases(), 'name');
    }

    public function process(): void
    {
        /** @var array<string> $textArray */
        $textArray = $this->page->getTextArray();
        array_walk(
            $textArray,

            /** @var string $value */
            function ($value, $key) use (&$textArray) {
                if (!isset($textArray[$key + 1])) {
                    return;
                }
                $type = $this->discoverType($value, $textArray[$key + 1]);

                if (is_callable($type)) {
                    $type($key, $textArray);
                }
            }
        );
    }

    public function discoverType(string $text, string $nextText): ?callable
    {
        return match (true) {
            preg_match("/([A-ZÀ-Ú()]+ )+-.*/", $text) === 1 && str_starts_with($nextText, "Latitude") => [$this, "fillLocation"],
            in_array($text, $this->months) => [$this, 'setCurrentMonth'],
            is_numeric($text) && in_array($nextText, $this->weekdays) => [$this, "addTidesOfTheDay"],
            default => null,
        };
    }

    /** @param array<string> &$textArray */
    public function setCurrentMonth(int $currentKey, array &$textArray): Month
    {
        /** @var Month $month */
        $month = Month::{$textArray[$currentKey]};
        $this->month = $month;
        return $this->month;
    }

    /** @param array<string> &$textArray */
    public function fillLocation(int $currentKey, array &$textArray): void
    {
        if ($this->location->isFilled()) {
            return;
        }
        $this->location->name = str_replace(" - " . $this->year, "", $textArray[$currentKey]);
        (new LocationExtractor($textArray, $currentKey + 1))->fillLocation($this->location);
    }

    /** @param array<string> &$textArray */
    public function addTidesOfTheDay(int $currentKey, array &$textArray): void
    {
        $day = ltrim($textArray[$currentKey], "0");

        if (!isset($this->month)) {
            throw new \Exception("Month not set!");
        }

        $month = $this->month->value;
        $year = $this->year;
        $tideDataIndex = $currentKey + 2;
        while (isset($textArray[$tideDataIndex]) && preg_match("/(?P<hour>\d{2})(?P<minute>\d{2})\s+(?P<height>-?\d{1,2}\.\d{1,2})/", $textArray[$tideDataIndex], $matches) === 1) {
            $tz = $this->location->timezone instanceof \DateTimeZone ? $this->location->timezone : null;
            $time = new \DateTime("{$year}-{$month}-{$day} {$matches['hour']}:{$matches['minute']}", $tz);
            $height = (float) $matches["height"];
            $type = Type::determine($height, (float) $this->location->meanSeaLevel);
            $this->location->tides->add(new Tide($time, $height, $type, $this->location));
            $matches = [];
            $tideDataIndex++;
        }
    }
}
