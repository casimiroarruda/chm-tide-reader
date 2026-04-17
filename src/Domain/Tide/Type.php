<?php

namespace Andr\ChmTideExtractor\Domain\Tide;

enum Type
{
    case HIGH;
    case LOW;

    public static function determine(float $height, float $mean): self
    {
        return $height > $mean ? self::HIGH : self::LOW;
    }
}
