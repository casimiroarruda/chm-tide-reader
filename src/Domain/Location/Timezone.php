<?php

namespace Andr\ChmTideExtractor\Domain\Location;

enum Timezone: string
{
    case AMERICA_NORONHA = "America/Noronha";
    case AMERICA_SAO_PAULO = "America/Sao_Paulo";
    case AMERICA_MANAUS = "America/Manaus";
    case AMERICA_RIO_BRANCO = "America/Rio_Branco";

    public static function fromOffset(string $offset): self
    {
        return match ($offset) {
            '-02:00' => self::AMERICA_NORONHA,
            '-03:00' => self::AMERICA_SAO_PAULO,
            '-04:00' => self::AMERICA_MANAUS,
            '-05:00' => self::AMERICA_RIO_BRANCO,
            default => throw new \InvalidArgumentException("Unknown timezone offset: $offset"),
        };
    }
}
