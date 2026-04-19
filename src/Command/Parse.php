<?php

namespace Andr\ChmTideExtractor\Command;

use Andr\ChmTideExtractor\Service\PdfParser;
use Andr\ChmTideExtractor\Service\TideStore;
use Symfony\Component\Console\Attribute\Argument;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'tide:parse-all',
    description: 'Parse all the tide PDFs',
    help: 'This command will parse all the tide PDFs and store them in the database.',
    usages: ['2026']
)]
class Parse extends Command
{
    public function __construct(
        private readonly PdfParser $pdfParser,
        private readonly TideStore $store
    ) {
        parent::__construct();
    }

    public function __invoke(
        #[Argument(description: 'The year to parse')]
        int $year,
        OutputInterface $output,
        SymfonyStyle $io
    ): int {
        $iterator = $this->pdfParser->fromCommand($year);
        $io->title("Parsing Tide files from Marinha do Brasil");
        foreach ($iterator as $location) {
            $io->section("> " . $location->name);
            $io->text("    Saving location");
            $saveLocationResult = $this->store->saveLocation($location);
            if (!$saveLocationResult) {
                $io->error("   Error saving location");
                continue;
            }
            $io->text("    Saving tides");
            $progressBar = new ProgressBar($output, count($location->tides));
            $progressBar->start();
            $saveLocationTidesResult = $this->store->saveLocationTides($location, [$progressBar, 'advance']);
            $progressBar->finish();
            if (!$saveLocationTidesResult) {
                $io->error("   Error: No tide was saved");
                continue;
            }
            $io->info("    Location and tides saved successfully");
        }
        $io->success("Done");
        return Command::SUCCESS;
    }
}
