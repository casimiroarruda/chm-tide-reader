<?php

namespace Andr\ChmTideExtractor\Domain\Tide;

use Andr\ChmTideExtractor\Domain\Tide;
use ArrayIterator, IteratorAggregate, Countable, Iterator;

class Collection implements IteratorAggregate, Countable
{
    public function __construct(
        private array $tides
    ) {}

    public function getIterator(): Iterator
    {
        return new ArrayIterator($this->tides);
    }

    public function count(): int
    {
        return count($this->tides);
    }

    public function add(Tide $tide): void
    {
        $this->tides[] = $tide;
    }
}
