<?php

namespace Andr\ChmTideExtractor\Tests\Service\PdfParser;

use Andr\ChmTideExtractor\Service\PdfParser\LocationExtractor;
use PHPUnit\Framework\TestCase;

class LocationExtractorTest extends TestCase
{
    public function testExtract(): void
    {
        $pageArray = [
            "Other text",
            "Latitude 02° 54&#39;.0 S",
            "Longitude 39° 55&#39;.0 W",
            "Fuso UTC -03.0 horas",
            "Nível médio 1.55 m"
        ];
        
        $extractor = new LocationExtractor($pageArray, 1);
        $data = $extractor->extract();

        $this->assertEquals("02° 54'.0 S", $data['latitude']);
        $this->assertEquals("39° 55'.0 W", $data['longitude']);
        $this->assertInstanceOf(\DateTimeZone::class, $data['timezone']);
        $this->assertEquals("-03:00", $data['timezone']->getName());
        $this->assertEquals(1.55, $data['meanSeaLevel']);
    }

    public function testExtractWithSkips(): void
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
        
        $extractor = new LocationExtractor($pageArray, 1);
        $data = $extractor->extract();

        $this->assertEquals("02° 54'.0 S", $data['latitude']);
        $this->assertEquals("39° 55'.0 W", $data['longitude']);
        $this->assertEquals("-03:00", $data['timezone']->getName());
        $this->assertEquals(1.55, $data['meanSeaLevel']);
    }
}
