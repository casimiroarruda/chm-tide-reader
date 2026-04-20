<?php

namespace Andr\ChmTideExtractor\Domain\Tide;

use Andr\ChmTideExtractor\Domain\Tide;
use ArrayIterator, IteratorAggregate, Countable, Iterator;

/**
 * @implements IteratorAggregate<int, Tide>
 */
class Collection implements IteratorAggregate, Countable
{
    /** @param array<Tide> $tides */
    public function __construct(
        private array $tides
    ) {}

    /** @return Iterator<Tide> */
    public function getIterator(): Iterator
    {
        return new ArrayIterator($this->tides);
    }

    public function count(): int
    {
        return count($this->tides);
    }

    /** @param Tide $tide */
    public function add(Tide $tide): void
    {
        $this->tides[] = $tide;
    }
}
