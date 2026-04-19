<?php

namespace Andr\ChmTideExtractor\Tests\Command;

use Andr\ChmTideExtractor\Command\ParseOne;
use Andr\ChmTideExtractor\Domain\Location;
use Andr\ChmTideExtractor\Domain\Tide\Collection;
use Andr\ChmTideExtractor\Service\PdfParser;
use Andr\ChmTideExtractor\Service\TideStore;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

class ParseOneTest extends TestCase
{
    private $pdfParserMock;
    private $storeMock;
    private CommandTester $commandTester;

    protected function setUp(): void
    {
        $this->pdfParserMock = $this->createMock(PdfParser::class);
        $this->storeMock = $this->createMock(TideStore::class);

        $application = new Application();
        $application->addCommand(new ParseOne($this->pdfParserMock, $this->storeMock, dirname(__DIR__, 2)));

        $command = $application->find('tide:parse');
        $this->commandTester = new CommandTester($command);
    }

    public function testExecuteSuccess(): void
    {
        $location = new Location();
        $location->name = "Test Port";
        $location->tides = new Collection([]);

        $this->pdfParserMock->expects($this->once())
            ->method('processFile')
            ->willReturn($location);

        $this->storeMock->expects($this->once())
            ->method('saveLocation')
            ->with($location)
            ->willReturn($location);

        $this->storeMock->expects($this->once())
            ->method('saveLocationTides')
            ->willReturn(true);

        $this->commandTester->execute([
            'year' => 2026,
            'file' => 'composer.json', // any file that exists relative to app root
        ]);

        $this->commandTester->assertCommandIsSuccessful();
        $output = $this->commandTester->getDisplay();
        $this->assertStringContainsString('Parsing Tide file', $output);
        $this->assertStringContainsString('Test Port', $output);
        $this->assertStringContainsString('Location and tides saved successfully', $output);
    }

    public function testExecuteFailsOnInvalidFile(): void
    {
        $this->commandTester->execute([
            'year' => 2026,
            'file' => 'nonexistent_file.pdf',
        ]);

        $this->assertEquals(Command::FAILURE, $this->commandTester->getStatusCode());
        $output = $this->commandTester->getDisplay();
        $this->assertStringContainsString('The file "nonexistent_file.pdf" does not exist', $output);
    }

    public function testExecuteFailsOnLocationSave(): void
    {
        $location = new Location();
        $location->name = "Test Port";
        $location->tides = new Collection([]);

        $this->pdfParserMock->expects($this->once())
            ->method('processFile')
            ->willReturn($location);

        $this->storeMock->expects($this->once())
            ->method('saveLocation')
            ->with($location)
            ->willReturn(false);

        $this->storeMock->expects($this->never())
            ->method('saveLocationTides');

        $this->commandTester->execute([
            'year' => 2026,
            'file' => 'composer.json',
        ]);

        $this->assertEquals(Command::FAILURE, $this->commandTester->getStatusCode());
        $output = $this->commandTester->getDisplay();
        $this->assertStringContainsString('Error saving location', $output);
    }

    public function testExecuteFailsOnTideSave(): void
    {
        $location = new Location();
        $location->name = "Test Port";
        $location->tides = new Collection([]);

        $this->pdfParserMock->expects($this->once())
            ->method('processFile')
            ->willReturn($location);

        $this->storeMock->expects($this->once())
            ->method('saveLocation')
            ->with($location)
            ->willReturn($location);

        $this->storeMock->expects($this->once())
            ->method('saveLocationTides')
            ->willReturn(false);

        $this->commandTester->execute([
            'year' => 2026,
            'file' => 'composer.json',
        ]);

        $this->assertEquals(Command::FAILURE, $this->commandTester->getStatusCode());
        $output = $this->commandTester->getDisplay();
        $this->assertStringContainsString('Error: No tide was saved', $output);
    }
}
