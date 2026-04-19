<?php

namespace Andr\ChmTideExtractor\Tests\Domain;

use Andr\ChmTideExtractor\Domain\Location;
use Andr\ChmTideExtractor\Domain\Location\Point;
use DateTimeZone;
use PHPUnit\Framework\TestCase;

class LocationTest extends TestCase
{
    public function testConstructorHydratesPointAndTimezone(): void
    {
        // Simulate PDO hydration
        $location = new class extends Location {
            public function __construct() {
                $this->point = "POINT(-39.92 -2.9)";
                $this->timezone = "America/Fortaleza";
                parent::__construct();
            }
        };

        $this->assertInstanceOf(Point::class, $location->point);
        $this->assertEquals(-2.9, $location->point->latitude);
        $this->assertEquals(-39.92, $location->point->longitude);

        $this->assertInstanceOf(DateTimeZone::class, $location->timezone);
        $this->assertEquals("America/Fortaleza", $location->timezone->getName());
    }

    public function testIsFilled(): void
    {
        $location = new Location();
        $this->assertFalse($location->isFilled());

        $location->marineId = "123";
        $location->name = "Test Port";
        $location->point = new Point(-39.92, -2.9);
        $location->meanSeaLevel = "1.5";
        $location->timezone = new DateTimeZone("America/Fortaleza");

        $this->assertTrue($location->isFilled());
    }
}
