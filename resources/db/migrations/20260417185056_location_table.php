<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;
use Phinx\Util\Literal;

final class LocationTable extends AbstractMigration
{
    public function change(): void
    {
        $this->execute('CREATE EXTENSION IF NOT EXISTS "uuid-ossp";');
        $this->execute('CREATE EXTENSION IF NOT EXISTS postgis;');
        $this->table('location', ['primary_key' => 'id', 'id' => false])
            ->addColumn('id', Literal::from('UUID'), ['null' => false, 'limit' => 36, 'default' => Literal::from('gen_random_uuid()')])
            ->addColumn('marine_id', 'string', ['limit' => 2])
            ->addColumn('name', 'string', ['limit' => 100])
            ->addColumn('point', 'point', ['srid' => 4326, 'null' => false])
            ->addColumn('mean_sea_level', 'float')
            ->addColumn('timezone', 'string', ['limit' => 30])
            ->addIndex(['marine_id'], ['unique' => true])
            ->addIndex(['point'], ['type' => 'gist'])
            ->create();
    }
}
