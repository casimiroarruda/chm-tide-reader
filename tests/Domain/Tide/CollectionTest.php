<?php

namespace Andr\ChmTideExtractor\Tests\Domain\Tide;

use Andr\ChmTideExtractor\Domain\Tide;
use Andr\ChmTideExtractor\Domain\Tide\Collection;
use Andr\ChmTideExtractor\Domain\Tide\Type;
use Andr\ChmTideExtractor\Domain\Location;
use DateTime;
use PHPUnit\Framework\TestCase;

class CollectionTest extends TestCase
{
    public function testAddAndCount(): void
    {
        $collection = new Collection([]);
        $this->assertCount(0, $collection);

        $tide = new Tide(new DateTime(), 1.5, Type::HIGH, new Location());
        $collection->add($tide);

        $this->assertCount(1, $collection);
    }

    public function testIterator(): void
    {
        $tide1 = new Tide(new DateTime(), 1.5, Type::HIGH, new Location());
        $tide2 = new Tide(new DateTime(), 0.5, Type::LOW, new Location());
        
        $collection = new Collection([$tide1, $tide2]);

        $items = iterator_to_array($collection);
        $this->assertCount(2, $items);
        $this->assertSame($tide1, $items[0]);
        $this->assertSame($tide2, $items[1]);
    }
}
