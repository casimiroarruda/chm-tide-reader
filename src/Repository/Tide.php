<?php

namespace Andr\ChmTideExtractor\Repository;

use Andr\ChmTideExtractor\Domain\Tide as DomainTide;
use Andr\ChmTideExtractor\Domain\Tide\Collection;

class Tide
{
    public function __construct(private \PDO $pdo) {}

    public function save(DomainTide $tide): DomainTide|false
    {
        $statement = $this->pdo->prepare("INSERT INTO tide (location_id, time, height, type) VALUES (:location_id, :time, :height, :type)");
        $statement->execute([
            "location_id" => $tide->location->id,
            "time" => $tide->time->format("c"),
            "height" => $tide->height,
            "type" => $tide->type->name
        ]);
        return $tide;
    }

    public function saveCollection(Collection $tides): void
    {
        $this->pdo->beginTransaction();
        $statement = $this->pdo->prepare("INSERT INTO tide (location_id, time, height, type) VALUES (:location_id, :time, :height, :type) ON CONFLICT (location_id, time) DO NOTHING");
        try {
            $tidesCount = count($tides);
            echo "    Saving " . $tidesCount . " tide data: ";
            $tenPercentStep = ceil($tidesCount / 10);
            foreach ($tides as $key => $tide) {
                $params = [
                    "location_id" => $tide->location->id,
                    "time" => $tide->time->format("c"),
                    "height" => $tide->height,
                    "type" => $tide->type->name
                ];
                $statement->execute($params);
                if ($key % $tenPercentStep == 0) {
                    echo ".";
                }
            }
        } catch (\PDOException $e) {
            $this->pdo->rollBack();
            throw $e;
        }
        echo " Done!" . PHP_EOL;
        $this->pdo->commit();
    }
}
