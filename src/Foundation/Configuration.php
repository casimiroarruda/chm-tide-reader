<?php

namespace Andr\ChmTideExtractor\Foundation;

class Configuration
{
    public string $year {
        set(string $value) => (int) $value >= 2026 && (int) $value <= 2050 ? $value : date('Y');
    }
    public function __construct(
        public private(set) string $tidePdfPath,
        string $year
    ) {
        $this->year = $year;
    }
}
