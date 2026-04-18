<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;
use Phinx\Util\Literal;

final class TideTable extends AbstractMigration
{
    public function change(): void
    {
        $this->table('tide', ['primary_key' => ['location_id', 'time'], 'id' => false])
            ->addColumn('location_id', Literal::from('UUID'), ['null' => false])
            ->addColumn('time', 'timestamp', ['null' => false, 'timezone' => true])
            ->addColumn('height', 'decimal', ['scale' => 2, 'precision' => 5])
            ->addColumn('type', 'string', ['limit' => 4])
            ->addForeignKey('location_id', 'location', 'id', ['delete' => 'CASCADE', 'update' => 'CASCADE'])
            ->addIndex(['location_id', 'time'], ['unique' => true])
            ->create();
    }
}
