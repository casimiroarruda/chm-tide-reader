<?php

namespace Andr\ChmTideExtractor\Tests\Service;

use Andr\ChmTideExtractor\Domain\Location;
use Andr\ChmTideExtractor\Domain\Location\Point;
use Andr\ChmTideExtractor\Foundation\Configuration;
use Andr\ChmTideExtractor\Service\PdfParser;
use Andr\ChmTideExtractor\Service\TideStore;
use PHPUnit\Framework\TestCase;
use Smalot\PdfParser\Parser;

class PdfParserTest extends TestCase
{
    private PdfParser $pdfParser;
    private Configuration $config;
    private $parserMock;
    private $storeMock;

    protected function setUp(): void
    {
        $this->config = new Configuration('/tmp/', '2026');
        $this->parserMock = $this->createMock(Parser::class);
        $this->storeMock = $this->createMock(TideStore::class);
        $this->pdfParser = new PdfParser($this->config, $this->parserMock, $this->storeMock);
    }

    public function testExtractMarineLocationIdFromFilename(): void
    {
        $filename = '/tmp/2026/123-Porto-de-Teste.pdf';
        $id = $this->pdfParser->extractMarineLocationIdFromFilename($filename);
        $this->assertEquals('123', $id);
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('discoverTypeProvider')]
    public function testDiscoverType(string $text, string $nextText, string|array $expectedType): void
    {
        $type = $this->pdfParser->discoverType($text, $nextText);

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
        $location = new Location();
        $textArray = [
            "PORTO DE TESTE - 2026",
            "Latitude 02° 54&#39;.0 S",
            "Longitude 39° 55&#39;.0 W",
            "Fuso UTC -03.0 horas",
            "Nível médio 1.55 m"
        ];

        $this->pdfParser->fillLocation($location, 0, $textArray);

        $this->assertEquals("PORTO DE TESTE", $location->name);
        $this->assertEquals(-2.9, $location->point->latitude);
        $this->assertEquals("-03:00", $location->timezone->getName());
        $this->assertEquals(1.55, $location->meanSeaLevel);
    }

    public function testAddTidesOfTheDay(): void
    {
        $location = new Location();
        $location->timezone = new \DateTimeZone("-03:00");
        $location->meanSeaLevel = 1.55;

        $textArray = [
            "01",
            "SEG",
            "0123    2.10",
            "0745    0.45",
            "1350    2.05",
            "2010    0.50"
        ];
        $meta = ["month" => "JANEIRO", "year" => "2026"];

        $this->pdfParser->addTidesOfTheDay($location, 0, $textArray, $meta);

        $this->assertCount(4, $location->tides);
        $tides = iterator_to_array($location->tides);

        $tide = array_first($tides);
        $this->assertEquals("2026-01-01 01:23:00", $tide->time->format('Y-m-d H:i:s'));
        $this->assertEquals(2.10, $tide->height);
        $this->assertEquals(\Andr\ChmTideExtractor\Domain\Tide\Type::HIGH, $tide->type);
    }

    public function testParsePage(): void
    {
        $pageMock = $this->createMock(\Smalot\PdfParser\Page::class);
        $pageMock->method('getTextArray')->willReturn([
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

        $location = new Location();
        $this->pdfParser->parsePage($pageMock, $location);

        $this->assertEquals("PORTO DE TESTE", $location->name);
        $this->assertCount(1, $location->tides);
    }

    public function testGetListingFiles(): void
    {
        $tempDir = '/tmp/tide_test_' . uniqid();
        mkdir($tempDir . '/2026', 0777, true);
        touch($tempDir . '/2026/1-Test.pdf');
        touch($tempDir . '/2026/2-Test.pdf');

        $config = new Configuration($tempDir . '/', '2026');
        $pdfParser = new PdfParser($config, $this->parserMock, $this->storeMock);

        $files = $pdfParser->getListingFiles();

        $this->assertCount(2, $files);
        $this->assertStringContainsString('1-Test.pdf', array_first($files));

        // Clean up
        unlink($tempDir . '/2026/1-Test.pdf');
        unlink($tempDir . '/2026/2-Test.pdf');
        rmdir($tempDir . '/2026');
        rmdir($tempDir);
    }

    public function testProcessFiles(): void
    {
        $pdfParser = $this->getMockBuilder(PdfParser::class)
            ->setConstructorArgs([$this->config, $this->parserMock, $this->storeMock])
            ->onlyMethods(['processFile'])
            ->getMock();

        $location1 = new Location();
        $location1->name = "Port 1";
        $location2 = new Location();
        $location2->name = "Port 2";

        $pdfParser->expects($this->exactly(2))
            ->method('processFile')
            ->willReturnOnConsecutiveCalls($location1, $location2);

        $results = $pdfParser->processFiles(['file1.pdf', 'file2.pdf']);

        $this->assertCount(2, $results);
        $this->assertEquals("Port 1", $results[0]->name);
        $this->assertEquals("Port 2", $results[1]->name);
    }

    public function testInvoke(): void
    {
        $pdfParser = $this->getMockBuilder(PdfParser::class)
            ->setConstructorArgs([$this->config, $this->parserMock, $this->storeMock])
            ->onlyMethods(['getListingFiles', 'processFiles'])
            ->getMock();

        $pdfParser->expects($this->once())->method('getListingFiles')->willReturn(['file1.pdf']);
        $pdfParser->expects($this->once())->method('processFiles')->with(['file1.pdf'])->willReturn([]);

        $results = $pdfParser("2026");
        $this->assertEquals([], $results);
        $this->assertEquals("2026", $this->config->year);
    }

    public function testProcessFile(): void
    {
        $pdfMock = $this->createMock(\Smalot\PdfParser\Document::class);
        $pageMock = $this->createMock(\Smalot\PdfParser\Page::class);

        $this->parserMock->method('parseFile')->willReturn($pdfMock);
        $pdfMock->method('getPages')->willReturn([$pageMock]);
        $pageMock->method('getTextArray')->willReturn([
            "PORTO DE TESTE - 2026",
            "Latitude 02° 54&#39;.0 S",
            "Longitude 39° 55&#39;.0 W",
            "Fuso UTC -03.0 horas",
            "Nível médio 1.55 m"
        ]);

        $location = $this->pdfParser->processFile('/tmp/2026/123-Porto.pdf');
        $this->assertInstanceOf(Location::class, $location);
        $this->assertEquals('123', $location->marineId);
        $this->assertEquals('PORTO DE TESTE', $location->name);
    }

    public function testFillLocationAlreadyFilled(): void
    {
        $location = new Location();
        $location->name = "Already Filled";
        $location->marineId = "99";
        $location->point = new Point(0, 0);
        $location->meanSeaLevel = 1.0;
        $location->timezone = new \DateTimeZone("UTC");

        $textArray = [
            "PORTO DE TESTE - 2026",
            "Latitude 02° 54&#39;.0 S",
            "Longitude 39° 55&#39;.0 W",
            "Fuso UTC -03.0 horas",
            "Nível médio 1.55 m"
        ];
        $this->pdfParser->fillLocation($location, 0, $textArray);

        $this->assertEquals("Already Filled", $location->name);
    }

    public function testAddTidesOfTheDayNoMatch(): void
    {
        $location = new Location();
        $textArray = ["01", "SEG", "INVALID DATA"];
        $meta = ["month" => "JANEIRO", "year" => "2026"];

        $this->pdfParser->addTidesOfTheDay($location, 0, $textArray, $meta);
        $this->assertCount(0, $location->tides);
    }
}
