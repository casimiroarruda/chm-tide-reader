<?php

namespace Andr\ChmTideExtractor\Tests\Command;

use Andr\ChmTideExtractor\Command\ParseAll;
use Andr\ChmTideExtractor\Domain\Location;
use Andr\ChmTideExtractor\Domain\Tide\Collection;
use Andr\ChmTideExtractor\Service\PdfParser;
use Andr\ChmTideExtractor\Service\TideStore;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;

class ParseAllTest extends TestCase
{
    private $pdfParserMock;
    private $storeMock;
    private CommandTester $commandTester;

    protected function setUp(): void
    {
        $_ENV['TIDE_PDF_PATH'] = 'resources/tide-pdf/';
        $this->pdfParserMock = $this->createMock(PdfParser::class);
        $this->storeMock = $this->createMock(TideStore::class);

        $application = new Application();
        $application->addCommand(new ParseAll($this->pdfParserMock, $this->storeMock, $_ENV['TIDE_PDF_PATH']));

        $command = $application->find('tide:parse-all');
        $this->commandTester = new CommandTester($command);
    }

    public function testExecuteSuccess(): void
    {
        $location = new Location();
        $location->name = "Test Port";
        $location->tides = new Collection([]);

        $this->pdfParserMock->expects($this->once())
            ->method('fromCommand')
            ->willReturn(
                (function () use ($location) {
                    yield $location;
                })()
            );

        $this->storeMock->expects($this->once())
            ->method('saveLocation')
            ->with($location)
            ->willReturn($location);

        $this->storeMock->expects($this->once())
            ->method('saveLocationTides')
            ->willReturn(true);

        $this->commandTester->execute([
            'year' => 2026,
        ]);

        $this->commandTester->assertCommandIsSuccessful();
        $output = $this->commandTester->getDisplay();
        $this->assertStringContainsString('Parsing Tide files from Marinha do Brasil', $output);
        $this->assertStringContainsString('Test Port', $output);
        $this->assertStringContainsString('Location and tides saved successfully', $output);
    }

    public function testExecuteFailsOnLocationSave(): void
    {
        $location = new Location();
        $location->name = "Test Port";
        $location->tides = new Collection([]);

        $this->pdfParserMock->expects($this->once())
            ->method('fromCommand')
            ->willReturn(
                (function () use ($location) {
                    yield $location;
                })()
            );

        $this->storeMock->expects($this->once())
            ->method('saveLocation')
            ->with($location)
            ->willReturn(false);

        $this->storeMock->expects($this->never())
            ->method('saveLocationTides');

        $this->commandTester->execute([
            'year' => 2026,
        ]);

        $this->commandTester->assertCommandIsSuccessful(); // The command continues the loop
        $output = $this->commandTester->getDisplay();
        $this->assertStringContainsString('Error saving location', $output);
    }

    public function testExecuteFailsOnTideSave(): void
    {
        $location = new Location();
        $location->name = "Test Port";
        $location->tides = new Collection([]);

        $this->pdfParserMock->expects($this->once())
            ->method('fromCommand')
            ->willReturn(
                (function () use ($location) {
                    yield $location;
                })()
            );

        $this->storeMock->expects($this->once())
            ->method('saveLocation')
            ->with($location)
            ->willReturn($location);

        $this->storeMock->expects($this->once())
            ->method('saveLocationTides')
            ->willReturn(false);

        $this->commandTester->execute([
            'year' => 2026,
        ]);

        $this->commandTester->assertCommandIsSuccessful(); // Continues loop
        $output = $this->commandTester->getDisplay();
        $this->assertStringContainsString('Error: No tide was saved', $output);
    }
}
