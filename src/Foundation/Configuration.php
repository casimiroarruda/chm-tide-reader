<?php

namespace Andr\ChmTideReader\Foundation;

class Configuration
{
    public function __construct(
        public private(set) string $tidePdfPath,
        public private(set) string $chmSiteHost,
        public private(set) int $year
    ) {}
}
