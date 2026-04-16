<?php

namespace Andr\ChmTideReader\Service;

use Andr\ChmTideReader\Foundation\Configuration;
use Dom\HTMLDocument;

class Listing
{
    public function __construct(
        protected Configuration $configuration
    ) {}

    public function getListingFiles(): array
    {
        return array_filter(
            scandir($this->configuration->listingsPath),
            fn($file) => is_file($this->configuration->listingsPath . "/" . $file)
        );
    }

    public function processFiles(array $listingFiles)
    {
        foreach ($listingFiles as $file) {
            $this->processFile($this->configuration->listingsPath . "/" . $file);
        }
    }

    public function processFile(string $file): void
    {
        $html = HTMLDocument::createFromFile($file);

        $tds = $html->querySelectorAll("table tbody tr td>a");
        foreach ($tds as $td) {

            $url = $this->configuration->chmSiteHost . $td->getAttribute("href") . "\n";
            $file = file_get_contents($url);
            file_put_contents($this->configuration->tidePdfPath . "/" . $td->getAttribute("href"), $file);
        }
    }
}
