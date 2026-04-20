<?php

namespace Andr\ChmTideExtractor\Tests\Service\PdfParser;

use Andr\ChmTideExtractor\Domain\Location;
use Andr\ChmTideExtractor\Domain\Location\Point;
use Andr\ChmTideExtractor\Domain\Tide\Type;
use PHPUnit\Framework\TestCase;
use Smalot\PdfParser\Page;
use Andr\ChmTideExtractor\Service\PdfParser\PageProcessor;

class PageProcessorTest extends TestCase
{
    private Page $pageMock;
    private Location $location;
    private string $year = '2026';

    protected function setUp(): void
    {
        $this->pageMock = $this->createMock(Page::class);
        $this->location = new Location();
    }

    private function getProcessor(?Location $location): PageProcessor
    {
        return new PageProcessor($this->pageMock, $location ?? $this->location, $this->year);
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('discoverTypeProvider')]
    public function testDiscoverType(string $text, string $nextText, string|array $expectedType): void
    {
        $processor = $this->getProcessor(null);
        $type = $processor->discoverType($text, $nextText);

        if (is_array($expectedType)) {
            $this->assertIsCallable($type);
            $this->assertEquals($expectedType[1], $type[1]);
        } else {
            $this->assertEquals($expectedType, $type);
        }
    }

    public static function discoverTypeProvider(): array
    {
        return [
            'Location header' => ['PORTO DE TESTE - 2026', 'Latitude 01', ['any', 'fillLocation']],
            'Latitude' => ['Latitude 01', 'Longitude 02', 'position'],
            'Month' => ['JANEIRO', '01', 'month'],
            'Tide data' => ['01', 'SEG', ['any', 'addTidesOfTheDay']],
            'Time Tide' => ['0123    1.55', 'Some other', 'timetide'],
            'Unknown' => ['Some random text', 'Other text', 'unknown'],
        ];
    }

    public function testFillLocation(): void
    {
        $textArray = [
            "PORTO DE TESTE - 2026",
            "Latitude 02° 54&#39;.0 S",
            "Longitude 39° 55&#39;.0 W",
            "Fuso UTC -03.0 horas",
            "Nível médio 1.55 m"
        ];

        $processor = $this->getProcessor(null);
        $processor->fillLocation(0, $textArray);

        $this->assertEquals("PORTO DE TESTE", $this->location->name);
        $this->assertEquals(-2.9, $this->location->point->latitude);
        $this->assertEquals("-03:00", $this->location->timezone->getName());
        $this->assertEquals(1.55, $this->location->meanSeaLevel);
    }

    public function testFillLocationAlreadyFilled(): void
    {
        $this->location->name = "Already Filled";
        $this->location->marineId = "99";
        $this->location->point = new Point(0, 0);
        $this->location->meanSeaLevel = 1.0;
        $this->location->timezone = new \DateTimeZone("UTC");

        $textArray = [
            "PORTO DE TESTE - 2026",
            "Latitude 02° 54&#39;.0 S",
            "Longitude 39° 55&#39;.0 W",
            "Fuso UTC -03.0 horas",
            "Nível médio 1.55 m"
        ];

        $processor = $this->getProcessor(null);
        $processor->fillLocation(0, $textArray);

        $this->assertEquals("Already Filled", $this->location->name);
    }

    public function testAddTidesOfTheDay(): void
    {
        $this->location->timezone = new \DateTimeZone("-03:00");
        $this->location->meanSeaLevel = 1.55;

        $textArray = [
            "PORTO DE TESTE - 2026",
            "JANEIRO",
            "01",
            "SEG",
            "0123    2.10",
            "0745    0.45",
            "1350    2.05",
            "2010    0.50"
        ];

        $this->pageMock->method('getTextArray')->willReturn($textArray);

        $processor = $this->getProcessor(null);
        // We need to set the month by processing the month entry
        $processor->process();

        $this->assertCount(4, $this->location->tides);
        $tides = iterator_to_array($this->location->tides);

        $tide = array_shift($tides);
        $this->assertEquals("2026-01-01 01:23:00", $tide->time->format('Y-m-d H:i:s'));
        $this->assertEquals(2.10, $tide->height);
        $this->assertEquals(Type::HIGH, $tide->type);
    }

    public function testAddTidesOfTheDayNoMatch(): void
    {
        $this->location->timezone = new \DateTimeZone("-03:00");
        $this->location->meanSeaLevel = 1.55;

        $textArray = [
            "JANEIRO",
            "01",
            "SEG",
            "INVALID DATA"
        ];

        $this->pageMock->method('getTextArray')->willReturn($textArray);

        $processor = $this->getProcessor(null);
        $processor->process();
        $this->assertCount(0, $this->location->tides);
    }

    public function testProcess(): void
    {
        $this->pageMock->method('getTextArray')->willReturn([
            "PORTO DE TESTE - 2026",
            "Latitude 02° 54&#39;.0 S",
            "Longitude 39° 55&#39;.0 W",
            "Fuso UTC -03.0 horas",
            "Nível médio 1.55 m",
            "JANEIRO",
            "01",
            "SEG",
            "0123    2.10"
        ]);

        $processor = $this->getProcessor(null);
        $processor->process();

        $this->assertEquals("PORTO DE TESTE", $this->location->name);
        $this->assertCount(1, $this->location->tides);
    }
}
