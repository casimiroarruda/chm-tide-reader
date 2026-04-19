<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class IncreaseMarineIdLimit extends AbstractMigration
{
    public function change(): void
    {
        $this->table('location')
            ->changeColumn('marine_id', 'string', ['limit' => 10])
            ->update();
    }
}
