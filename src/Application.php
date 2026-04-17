<?php

namespace Andr\ChmTideExtractor;

class Application
{
    public function __construct(
        protected array $listingFiles
    ) {}

    public function run(): void {}
}
