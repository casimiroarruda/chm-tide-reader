<?php

namespace Andr\ChmTideReader\Entity;

use Andr\ChmTideReader\Entity\Tide\Type;
use DateTime;

class Tide
{
    public function __construct(
        public DateTime $time,
        public float $height,
        public Type $type
    ) {}
}
