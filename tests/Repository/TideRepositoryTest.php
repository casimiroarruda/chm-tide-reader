<?php

namespace Andr\ChmTideExtractor\Tests\Repository;

use Andr\ChmTideExtractor\Domain\Location as DomainLocation;
use Andr\ChmTideExtractor\Domain\Location\Point;
use Andr\ChmTideExtractor\Domain\Tide as DomainTide;
use Andr\ChmTideExtractor\Domain\Tide\Collection;
use Andr\ChmTideExtractor\Domain\Tide\Type;
use Andr\ChmTideExtractor\Repository\Location as LocationRepository;
use Andr\ChmTideExtractor\Repository\Tide as TideRepository;
use DateTime;
use DateTimeZone;

class TideRepositoryTest extends BaseRepositoryTestCase
{
    private TideRepository $repository;
    private LocationRepository $locationRepository;
    private DomainLocation $testLocation;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = new TideRepository(self::$pdo);
        $this->locationRepository = new LocationRepository(self::$pdo);

        // Prepare a location for tide tests
        $location = new DomainLocation();
        $location->marineId = bin2hex(random_bytes(5));
        $location->name = "Tide Test Port";
        $location->point = new Point(0, 0);
        $location->meanSeaLevel = "1.0";
        $location->timezone = new DateTimeZone("UTC");
        $this->locationRepository->save($location);
        $this->testLocation = $location;
    }

    public function testSave(): void
    {
        $tide = new DomainTide(
            new DateTime("2026-01-01 12:00:00"),
            1.55,
            Type::HIGH,
            $this->testLocation
        );

        $saved = $this->repository->save($tide);
        $this->assertNotFalse($saved);

        // Verify insertion
        $query = self::$pdo->prepare("SELECT count(*) FROM tide WHERE location_id = :loc_id");
        $query->execute(['loc_id' => $this->testLocation->id]);
        $this->assertEquals(1, $query->fetchColumn());
    }

    public function testSaveCollection(): void
    {
        $tide1 = new DomainTide(new DateTime("2026-01-01 00:00:00"), 1.0, Type::HIGH, $this->testLocation);
        $tide2 = new DomainTide(new DateTime("2026-01-01 06:00:00"), 0.5, Type::LOW, $this->testLocation);
        $collection = new Collection([$tide1, $tide2]);

        $this->repository->saveCollection($collection);

        $query = self::$pdo->prepare("SELECT count(*) FROM tide WHERE location_id = :loc_id");
        $query->execute(['loc_id' => $this->testLocation->id]);
        $this->assertEquals(2, $query->fetchColumn());
    }

    public function testSaveCollectionOnConflict(): void
    {
        $time = new DateTime("2026-01-01 00:00:00");
        $tide1 = new DomainTide($time, 1.0, Type::HIGH, $this->testLocation);
        $this->repository->save($tide1);

        // Try to save a collection containing the same tide time
        $tide2 = new DomainTide($time, 1.1, Type::HIGH, $this->testLocation); // Different height, same time
        $collection = new Collection([$tide2]);

        $this->repository->saveCollection($collection);

        $query = self::$pdo->prepare("SELECT height FROM tide WHERE location_id = :loc_id AND time = :time");
        $query->execute([
            'loc_id' => $this->testLocation->id,
            'time' => $time->format("c")
        ]);
        $this->assertEquals(1.0, $query->fetchColumn()); // Should still be 1.0 due to DO NOTHING
    }

    public function testSaveCollectionWithoutPreExistingTransaction(): void
    {
        // Commit the transaction started by BaseRepositoryTestCase
        if (self::$pdo->inTransaction()) {
            self::$pdo->commit();
        }

        $tide = new DomainTide(new DateTime("2026-01-02 00:00:00"), 1.0, Type::HIGH, $this->testLocation);
        $collection = new Collection([$tide]);

        $this->repository->saveCollection($collection);

        $query = self::$pdo->prepare("SELECT count(*) FROM tide WHERE location_id = :loc_id AND time = '2026-01-02 00:00:00'");
        $query->execute(['loc_id' => $this->testLocation->id]);
        $this->assertEquals(1, $query->fetchColumn());

        // Restart transaction for tearDown
        self::$pdo->beginTransaction();
    }

    public function testSaveCollectionWithException(): void
    {
        // Commit the transaction started by BaseRepositoryTestCase
        if (self::$pdo->inTransaction()) {
            self::$pdo->commit();
        }

        // Cause an exception by passing an invalid location ID
        $location = new DomainLocation();
        $location->id = "00000000-0000-0000-0000-000000000000"; // Likely non-existent
        
        $tide = new DomainTide(new DateTime("2026-01-03 00:00:00"), 1.0, Type::HIGH, $location);
        $collection = new Collection([$tide]);

        try {
            $this->repository->saveCollection($collection);
            $this->fail("Expected PDOException was not thrown");
        } catch (\PDOException $e) {
            $this->assertFalse(self::$pdo->inTransaction());
        } finally {
            // Restart transaction for tearDown
            if (!self::$pdo->inTransaction()) {
                self::$pdo->beginTransaction();
            }
        }
    }
}
