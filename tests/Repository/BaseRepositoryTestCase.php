<?php

namespace Andr\ChmTideExtractor\Tests\Repository;

use PHPUnit\Framework\TestCase;
use PDO;
use Symfony\Component\Dotenv\Dotenv;

abstract class BaseRepositoryTestCase extends TestCase
{
    protected static ?PDO $pdo = null;

    protected function setUp(): void
    {
        if (self::$pdo === null) {
            $baseDir = dirname(__DIR__, 2);
            if (file_exists($baseDir . '/.env')) {
                (new Dotenv())->load($baseDir . '/.env');
            }

            try {
                self::$pdo = new PDO($_ENV['DB_DSN'], $_ENV['DB_USER'], $_ENV['DB_PASSWORD']);
                self::$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                self::$pdo->exec("SET search_path TO " . $_ENV['DB_SCHEMA']);
            } catch (\Exception $e) {
                $this->markTestSkipped("Could not connect to database: " . $e->getMessage());
            }
        }

        self::$pdo->beginTransaction();
    }

    protected function tearDown(): void
    {
        if (self::$pdo && self::$pdo->inTransaction()) {
            self::$pdo->rollBack();
        }
    }
}
