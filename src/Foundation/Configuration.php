<?php

namespace Andr\ChmTideExtractor\Foundation;

class Configuration
{
    public function __construct(
        public private(set) string $tidePdfPath,
        public private(set) int $year
    ) {}
}
