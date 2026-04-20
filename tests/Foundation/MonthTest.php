<?php

namespace Andr\ChmTideExtractor\Tests\Foundation;

use Andr\ChmTideExtractor\Foundation\Month;
use PHPUnit\Framework\TestCase;

class MonthTest extends TestCase
{
    public function testValues(): void
    {
        $this->assertEquals("01", Month::Janeiro->value);
        $this->assertEquals("02", Month::Fevereiro->value);
        $this->assertEquals("03", Month::Março->value);
        $this->assertEquals("04", Month::Abril->value);
        $this->assertEquals("05", Month::Maio->value);
        $this->assertEquals("06", Month::Junho->value);
        $this->assertEquals("07", Month::Julho->value);
        $this->assertEquals("08", Month::Agosto->value);
        $this->assertEquals("09", Month::Setembro->value);
        $this->assertEquals("10", Month::Outubro->value);
        $this->assertEquals("11", Month::Novembro->value);
        $this->assertEquals("12", Month::Dezembro->value);
    }
}
