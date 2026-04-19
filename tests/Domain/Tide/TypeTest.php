<?php

namespace Andr\ChmTideExtractor\Tests\Domain\Tide;

use Andr\ChmTideExtractor\Domain\Tide\Type;
use PHPUnit\Framework\TestCase;

class TypeTest extends TestCase
{
    public function testDetermineHighTide(): void
    {
        $this->assertEquals(Type::HIGH, Type::determine(3.0, 1.5));
    }

    public function testDetermineLowTide(): void
    {
        $this->assertEquals(Type::LOW, Type::determine(1.0, 1.5));
    }

    public function testDetermineLowTideOnEqual(): void
    {
        $this->assertEquals(Type::LOW, Type::determine(1.5, 1.5));
    }
}
