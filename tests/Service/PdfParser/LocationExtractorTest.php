<?php

namespace Andr\ChmTideExtractor\Tests\Service\PdfParser;

use Andr\ChmTideExtractor\Domain\Location;
use Andr\ChmTideExtractor\Service\PdfParser\LocationExtractor;
use PHPUnit\Framework\TestCase;

class LocationExtractorTest extends TestCase
{
    public function testFillLocation(): void
    {
        $pageArray = [
            "Other text",
            "Latitude 02° 54&#39;.0 S",
            "Longitude 39° 55&#39;.0 W",
            "Fuso UTC -03.0 horas",
            "Nível médio 1.55 m"
        ];
        
        $location = new Location();
        $extractor = new LocationExtractor($pageArray, 1);
        $extractor->fillLocation($location);

        $this->assertEquals(-2.9, $location->point->latitude);
        $this->assertEquals(-39.92, $location->point->longitude);
        $this->assertInstanceOf(\DateTimeZone::class, $location->timezone);
        $this->assertEquals("-03:00", $location->timezone->getName());
        $this->assertEquals("1.55", $location->meanSeaLevel);
    }

    public function testFillLocationWithSkips(): void
    {
        $pageArray = [
            "Header",
            "Latitude 02° 54&#39;.0 S",
            "Some garbage",
            "Longitude 39° 55&#39;.0 W",
            "More garbage",
            "Fuso UTC -03.0 horas",
            "Even more",
            "Nível médio 1,55 m"
        ];
        
        $location = new Location();
        $extractor = new LocationExtractor($pageArray, 1);
        $extractor->fillLocation($location);

        $this->assertEquals(-2.9, $location->point->latitude);
        $this->assertEquals(-39.92, $location->point->longitude);
        $this->assertEquals("-03:00", $location->timezone->getName());
        $this->assertEquals("1.55", $location->meanSeaLevel);
    }
}
