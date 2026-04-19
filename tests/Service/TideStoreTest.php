<?php

namespace Andr\ChmTideExtractor\Tests\Service;

use Andr\ChmTideExtractor\Domain\Location;
use Andr\ChmTideExtractor\Domain\Tide\Collection;
use Andr\ChmTideExtractor\Repository\Location as LocationRepository;
use Andr\ChmTideExtractor\Repository\Tide as TideRepository;
use Andr\ChmTideExtractor\Service\TideStore;
use PHPUnit\Framework\TestCase;

class TideStoreTest extends TestCase
{
    private TideStore $tideStore;
    private $locationRepoMock;
    private $tideRepoMock;

    protected function setUp(): void
    {
        $this->locationRepoMock = $this->createMock(LocationRepository::class);
        $this->tideRepoMock = $this->createMock(TideRepository::class);
        $this->tideStore = new TideStore($this->locationRepoMock, $this->tideRepoMock);
    }

    public function testSaveLocation(): void
    {
        $location = new Location();

        $this->locationRepoMock->expects($this->once())
            ->method('save')
            ->with($location);

        $this->tideStore->saveLocation($location);
    }

    public function testSaveLocationTides(): void
    {
        $location = new Location();
        $location->tides = new Collection([]);

        $this->tideRepoMock->expects($this->once())
            ->method('saveCollection')
            ->with($location->tides);

        $this->tideStore->saveLocationTides($location);
    }

}
