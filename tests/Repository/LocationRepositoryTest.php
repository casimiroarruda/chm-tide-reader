<?php

namespace Andr\ChmTideExtractor\Tests\Repository;

use Andr\ChmTideExtractor\Domain\Location as DomainLocation;
use Andr\ChmTideExtractor\Domain\Location\Point;
use Andr\ChmTideExtractor\Repository\Location as LocationRepository;
use DateTimeZone;

class LocationRepositoryTest extends BaseRepositoryTestCase
{
    private LocationRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = new LocationRepository(self::$pdo);
    }

    public function testInsertAndFind(): void
    {
        $location = new DomainLocation();
        $location->marineId = "TEST-123";
        $location->name = "Test Port";
        $location->point = new Point(-39.92, -2.9);
        $location->meanSeaLevel = "1.55";
        $location->timezone = new DateTimeZone("America/Fortaleza");

        $inserted = $this->repository->insert($location);
        $this->assertNotFalse($inserted);
        $this->assertNotEmpty($inserted->id);

        $found = $this->repository->findByMarineId("TEST-123");
        $this->assertNotNull($found);
        $this->assertEquals("Test Port", $found->name);
        $this->assertEquals("1.55", $found->meanSeaLevel);
        $this->assertInstanceOf(Point::class, $found->point);
        $this->assertEquals(-2.9, $found->point->latitude);
    }

    public function testUpdate(): void
    {
        $location = new DomainLocation();
        $location->marineId = "TEST-456";
        $location->name = "Original Name";
        $location->point = new Point(0, 0);
        $location->meanSeaLevel = "1.0";
        $location->timezone = new DateTimeZone("UTC");

        $this->repository->insert($location);
        
        $location->name = "Updated Name";
        $updated = $this->repository->update($location);
        $this->assertNotFalse($updated);

        $found = $this->repository->findByMarineId("TEST-456");
        $this->assertEquals("Updated Name", $found->name);
    }

    public function testSaveUpsert(): void
    {
        $location = new DomainLocation();
        $location->marineId = "TEST-789";
        $location->name = "First Save";
        $location->point = new Point(20, 10);
        $location->meanSeaLevel = "2.0";
        $location->timezone = new DateTimeZone("UTC");

        $this->repository->save($location);
        $id1 = $location->id;

        $location->name = "Second Save";
        $this->repository->save($location);
        $id2 = $location->id;

        $this->assertEquals($id1, $id2);
        
        $found = $this->repository->findByMarineId("TEST-789");
        $this->assertEquals("Second Save", $found->name);
    }
}
