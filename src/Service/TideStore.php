<?php

namespace Andr\ChmTideExtractor\Service;

use Andr\ChmTideExtractor\Domain\Location;
use Andr\ChmTideExtractor\Repository\Tide as TideRepository;
use Andr\ChmTideExtractor\Repository\Location as LocationRepository;

class TideStore
{
    public function __construct(
        private LocationRepository $locationRepository,
        private TideRepository $tideRepository
    ) {}

    public function saveLocations(Location ...$locations): void
    {
        array_walk($locations, [$this,  'saveLocation']);
    }
    public function saveLocation(Location $location): void
    {
        $this->locationRepository->save($location);
        $this->tideRepository->saveCollection($location->tides);
    }
}
