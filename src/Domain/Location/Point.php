<?php

namespace Andr\ChmTideExtractor\Domain\Location;

class Point
{
    public function __construct(
        public float $longitude,
        public float $latitude
    ) {}

    public function __toString(): string
    {
        return "{$this->longitude} {$this->latitude}";
    }

    public static function DMS2Decimal(string $string): float
    {
        if (preg_match("/(?P<degrees>\d{1,2})°\s?(?P<minutes>\d{1,2})'\.?(?P<seconds>\d?)\s(?P<direction>[NSWE])/", $string, $matches) !== 1) {
            return 0.0;
        }
        $degrees = (int) ltrim($matches["degrees"], "0");
        $minutes = (int) ltrim($matches["minutes"], "0");
        $seconds = (int) ltrim($matches["seconds"], "0");
        $direction = $matches["direction"];
        $decimal = round($degrees + ($minutes / 60) + ($seconds / 3600), 2);
        if ($direction === "S" || $direction === "W") {
            $decimal = -$decimal;
        }
        return $decimal;
    }

    public static function fromDMS(string $longitude, string $latitude): self
    {
        return new self(
            self::DMS2Decimal($longitude),
            self::DMS2Decimal($latitude),
        );
    }

    public static function fromWKT(string $wkt): self
    {
        preg_match("/POINT\((?P<longitude>-?\d+\.?\d*)\s+(?P<latitude>-?\d+\.?\d*)\)/", $wkt, $matches);
        return new self(
            (float) $matches["longitude"],
            (float) $matches["latitude"]
        );
    }
}
