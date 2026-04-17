<?php

namespace Andr\ChmTideExtractor\Domain;

use Andr\ChmTideExtractor\Domain\Location\Point;
use DateTimeZone;

class Location
{
    public string $id;
    public string $marineId;
    public string $name;
    public Point $point;
    public string $meanSeaLevel;
    public DateTimeZone $timeZone;
    public Tide\Collection $tides;
    public function __construct()
    {
        $this->tides = new Tide\Collection([]);
    }

    public function isFilled(): bool
    {
        return isset($this->marineId, $this->name, $this->point, $this->meanSeaLevel, $this->timeZone);
    }
}
