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

    public function saveCollection(Collection $tides, ?callable $callback = null): bool
    {
        $startedTransaction = false;
        if (!$this->pdo->inTransaction()) {
            $this->pdo->beginTransaction();
            $startedTransaction = true;
        }

        $statement = $this->pdo->prepare("INSERT INTO tide (location_id, time, height, type) VALUES (:location_id, :time, :height, :type) ON CONFLICT (location_id, time) DO NOTHING");
        try {
            foreach ($tides as $key => $tide) {
                $params = [
                    "location_id" => $tide->location->id,
                    "time" => $tide->time->format("c"),
                    "height" => $tide->height,
                    "type" => $tide->type->name
                ];
                $statement->execute($params);
                if ($callback) {
                    $callback();
                }
            }
            if ($startedTransaction) {
                $this->pdo->commit();
            }
        } catch (\PDOException $e) {
            if ($startedTransaction) {
                $this->pdo->rollBack();
                return false;
            }
            throw $e;
        }

        return true;
    }
}
