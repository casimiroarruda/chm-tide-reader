<?php

namespace Andr\ChmTideExtractor\Tests\Domain\Location;

use Andr\ChmTideExtractor\Domain\Location\Point;
use PHPUnit\Framework\TestCase;

class PointTest extends TestCase
{
    #[\PHPUnit\Framework\Attributes\DataProvider('dmsProvider')]
    public function testDMS2Decimal(string $dms, float $expected): void
    {
        $this->assertEquals($expected, Point::DMS2Decimal($dms));
    }

    public static function dmsProvider(): array
    {
        return [
            'South Latitude' => ["02° 54' S", -2.9],
            'North Latitude' => ["02° 54' N", 2.9],
            'West Longitude' => ["39° 55' W", -39.92],
            'East Longitude' => ["39° 55' E", 39.92],
            'With Seconds and dot' => ["02° 54'.0 S", -2.9],
        ];
    }

    public function testFromWKT(): void
    {
        $wkt = "POINT(-39.92 -2.9)";
        $point = Point::fromWKT($wkt);

        $this->assertEquals(-2.9, $point->latitude);
        $this->assertEquals(-39.92, $point->longitude);
    }

    public function testToString(): void
    {
        $point = new Point(-39.92, -2.9);
        $this->assertEquals("-39.92 -2.9", (string) $point);
    }
}
