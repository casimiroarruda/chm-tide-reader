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
        $location->tides = new Collection([]);

        $this->locationRepoMock->expects($this->once())
            ->method('save')
            ->with($location);

        $this->tideRepoMock->expects($this->once())
            ->method('saveCollection')
            ->with($location->tides);

        $this->tideStore->saveLocation($location);
    }

    public function testSaveLocations(): void
    {
        $location1 = new Location();
        $location1->tides = new Collection([]);
        $location2 = new Location();
        $location2->tides = new Collection([]);

        $this->locationRepoMock->expects($this->exactly(2))
            ->method('save');

        $this->tideRepoMock->expects($this->exactly(2))
            ->method('saveCollection');

        $this->tideStore->saveLocations($location1, $location2);
    }
}
