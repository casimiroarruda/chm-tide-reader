<?php

enum Month: string {
    case Janeiro = '01';
}

$name = 'Janeiro';
try {
    $m = Month::{$name};
    echo "Success: " . $m->value . PHP_EOL;
} catch (\Throwable $e) {
    echo "Error: " . $e->getMessage() . PHP_EOL;
}
