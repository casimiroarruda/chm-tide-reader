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

    public function saveLocation(Location $location): Location|false
    {
        return $this->locationRepository->save($location);
    }

    public function saveLocationTides(Location $location, ?callable $callback = null): bool
    {
        return $this->tideRepository->saveCollection($location->tides, $callback);
    }
}
