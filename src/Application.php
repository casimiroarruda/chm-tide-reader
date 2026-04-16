<?php

namespace Andr\ChmTideReader;

class Application
{
    public function __construct(
        protected array $listingFiles
    ) {}

    public function run(): void {}
}
