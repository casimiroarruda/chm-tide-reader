<?php

namespace Andr\ChmTideExtractor\Domain;

use Andr\ChmTideExtractor\Domain\Location\Point;
use DateTimeZone;

class Location
{
    public string $id;
    public string $marineId;
    public string $name;
    public Point|string $point;
    public string $meanSeaLevel;
    public DateTimeZone|string $timezone;
    public Tide\Collection $tides;

    public function __construct()
    {
        $this->tides = new Tide\Collection([]);
        if (isset($this->point) && !($this->point instanceof Point)) {
            $this->point = Point::fromWKT($this->point);
        }
        if (isset($this->timezone) && !($this->timezone instanceof DateTimeZone)) {
            $this->timezone = new DateTimeZone($this->timezone);
        }
    }

    public function isFilled(): bool
    {
        return isset($this->marineId, $this->name, $this->point, $this->meanSeaLevel, $this->timezone);
    }
}
