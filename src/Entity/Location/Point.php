<?php

namespace Andr\ChmTideReader\Entity\Location;

class Point
{
    public function __construct(
        public float $latitude,
        public float $longitude
    ) {}

    public function __toString(): string
    {
        return "{$this->latitude} {$this->longitude}";
    }

    public static function DMS2Decimal(string $string): float
    {
        preg_match("/(?P<degrees>\d{1,2})°\s?(?P<minutes>\d{1,2})'\.?(?P<seconds>\d?)\s(?P<direction>[NSWE])/", $string, $matches);
        $degrees = (int) ltrim($matches["degrees"], "0");
        $minutes = (int) ltrim($matches["minutes"], "0");
        $seconds = (int) ltrim($matches["seconds"], "0");
        $direction = $matches["direction"];
        $decimal = round($degrees + ($minutes / 60), 2);
        if ($direction === "S" || $direction === "W") {
            $decimal = -$decimal;
        }
        return $decimal;
    }

    public static function fromDMS(string $latitude, string $longitude): self
    {
        return new self(
            self::DMS2Decimal($latitude),
            self::DMS2Decimal($longitude)
        );
    }
}
