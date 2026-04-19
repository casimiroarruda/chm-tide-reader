<?php

namespace Andr\ChmTideExtractor\Command;

use Andr\ChmTideExtractor\Foundation\Configuration;
use Andr\ChmTideExtractor\Service\PdfParser;
use Andr\ChmTideExtractor\Service\TideStore;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\Console\Attribute\Argument;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'tide:parse',
    description: 'Parse a single tide PDF file',
    help: 'This command will parse a single tide PDF file and store it in the database.',
    usages: ['2026 ./resources/tide-pdf/2026/24 - PORTO DO RECIFE - 82 - 84.pdf']
)]
class ParseOne extends Command
{
    public function __construct(
        private readonly PdfParser $pdfParser,
        private readonly TideStore $store,
        private readonly string $appRoot
    ) {
        parent::__construct();
    }

    public function __invoke(
        #[Argument(description: 'The year to parse')]
        int $year,
        #[Argument(description: 'The PDF file to parse')]
        string $file,
        OutputInterface $output,
        SymfonyStyle $io
    ): int {
        $locator = new FileLocator($this->appRoot);
        try {
            $file = $locator->locate($file);
        } catch (\Exception $e) {
            $io->error($e->getMessage());
            return Command::FAILURE;
        }
        $configuration = new Configuration(dirname($file, 2), $year);
        $this->pdfParser->configure($configuration);
        $io->title("Parsing Tide file: [" . basename($file) . "] from Marinha do Brasil");
        $location = $this->pdfParser->processFile($file);
        $io->section("> " . $location->name);
        $io->text("    Saving location");
        $saveLocationResult = $this->store->saveLocation($location);
        if (!$saveLocationResult) {
            $io->error("   Error saving location");
            return Command::FAILURE;
        }
        $io->text("    Saving tides");
        $progressBar = new ProgressBar($output, count($location->tides));
        $progressBar->start();
        $saveLocationTidesResult = $this->store->saveLocationTides($location, [$progressBar, 'advance']);
        $progressBar->finish();
        if (!$saveLocationTidesResult) {
            $io->error("   Error: No tide was saved");
            return Command::FAILURE;
        }
        $io->info("    Location and tides saved successfully");
        $io->success("Done");
        return Command::SUCCESS;
    }
}
