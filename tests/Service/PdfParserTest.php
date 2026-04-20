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
        $this->pdfParser = new PdfParser($this->parserMock);
        $this->pdfParser->configure($this->config);
    }

    public function testExtractMarineLocationIdFromFilename(): void
    {
        $filename = '/tmp/2026/123-Porto-de-Teste.pdf';
        $id = $this->pdfParser->extractMarineLocationIdFromFilename($filename);
        $this->assertEquals('123', $id);
    }

    public function testGetListingFiles(): void
    {
        $tempDir = '/tmp/tide_test_' . uniqid();
        mkdir($tempDir . '/2026', 0777, true);
        touch($tempDir . '/2026/1-Test.pdf');
        touch($tempDir . '/2026/2-Test.pdf');

        $config = new Configuration($tempDir . '/', '2026');
        $pdfParser = new PdfParser($this->parserMock);
        $pdfParser->configure($config);

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
            ->setConstructorArgs([$this->parserMock])
            ->onlyMethods(['processFile'])
            ->getMock();
        $pdfParser->configure($this->config);

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

    public function testFromCommand(): void
    {
        $pdfParser = $this->getMockBuilder(PdfParser::class)
            ->setConstructorArgs([$this->parserMock])
            ->onlyMethods(['getListingFiles', 'processFile'])
            ->getMock();
        $pdfParser->configure($this->config);

        $location1 = new Location();

        $pdfParser->expects($this->once())->method('getListingFiles')->willReturn(['file1.pdf']);
        $pdfParser->expects($this->once())->method('processFile')->with('file1.pdf')->willReturn($location1);

        $generator = $pdfParser->fromCommand();
        $results = iterator_to_array($generator);

        $this->assertCount(1, $results);
        $this->assertSame($location1, $results[0]);
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

}
