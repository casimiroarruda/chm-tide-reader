<?php

namespace Andr\ChmTideExtractor\Repository;

use Andr\ChmTideExtractor\Domain\Location as DomainLocation;
use Andr\ChmTideExtractor\Domain\Location\Point;
use DateTimeZone;

class Location
{
    public function __construct(
        private \PDO $pdo
    ) {}

    public function findByMarineId(string $marineId): DomainLocation|null
    {
        $query = $this->pdo->prepare(
            "SELECT id,
                    marine_id as \"marineId\", 
                    name, 
                    ST_AsText(point) as point, 
                    mean_sea_level as \"meanSeaLevel\", 
                    timezone
               FROM location 
              WHERE marine_id = :marine_id"
        );
        $query->execute(['marine_id' => $marineId]);
        $query->setFetchMode(\PDO::FETCH_CLASS, DomainLocation::class);
        return $query->fetch() ?: null;
    }

    public function insert(DomainLocation $location): DomainLocation|false
    {
        $query = $this->pdo->prepare(
            "INSERT INTO location (marine_id, name, point, mean_sea_level, timezone) 
             VALUES (:marine_id, :name, ST_GeographyFromText(:point), :mean_sea_level, :timezone)
             RETURNING id"
        );
        $result = $query->execute([
            'marine_id' => $location->marineId,
            'name' => $location->name,
            'point' => "POINT($location->point)",
            'mean_sea_level' => $location->meanSeaLevel,
            'timezone' => $location->timezone->getName(),
        ]);
        if (!$result) {
            return false;
        }
        $result = $query->fetch(\PDO::FETCH_OBJ);
        $location->id = $result->id;
        return $location;
    }

    public function update(DomainLocation $location): DomainLocation|false
    {
        $query = $this->pdo->prepare(
            "UPDATE location 
             SET marine_id = :marine_id, 
                 name = :name, 
                 point = ST_GeographyFromText(:point), 
                 mean_sea_level = :mean_sea_level, 
                 timezone = :timezone 
             WHERE id = :id"
        );
        $result = $query->execute([
            'marine_id' => $location->marineId,
            'name' => $location->name,
            'point' => "POINT($location->point)",
            'mean_sea_level' => $location->meanSeaLevel,
            'timezone' => $location->timezone->getName(),
            'id' => $location->id,
        ]);
        return $location ?? false;
    }

    public function save(DomainLocation $location): DomainLocation|false
    {
        $findByMarineId = $this->findByMarineId($location->marineId);
        if ($findByMarineId !== null) {
            $location->id = $findByMarineId->id;
            return $this->update($location);
        }
        return $this->insert($location);
    }
}
