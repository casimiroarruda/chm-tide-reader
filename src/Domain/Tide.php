<?php

namespace Andr\ChmTideExtractor\Domain;

use Andr\ChmTideExtractor\Domain\Tide\Type;
use DateTime;

class Tide
{
    public function __construct(
        public DateTime $time,
        public float $height,
        public Type $type
    ) {}
}
