<?php

namespace Andr\ChmTideReader\Entity\Tide;

enum Type
{
    case HIGH;
    case LOW;

    public static function determine(float $height, float $mean): self
    {
        return $height > $mean ? self::HIGH : self::LOW;
    }

    public function __toString(): string
    {
        return $this->name;
    }
}
