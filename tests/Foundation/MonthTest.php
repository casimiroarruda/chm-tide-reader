<?php

namespace Andr\ChmTideExtractor\Tests\Foundation;

use Andr\ChmTideExtractor\Foundation\Month;
use PHPUnit\Framework\TestCase;

class MonthTest extends TestCase
{
    /**
     * @dataProvider monthProvider
     */
    #[\PHPUnit\Framework\Attributes\DataProvider('monthProvider')]
    public function testGet(string $input, Month|false $expected): void
    {
        $this->assertSame($expected, Month::get($input));
    }

    public static function monthProvider(): array
    {
        return [
            'Standard case' => ['Janeiro', Month::Janeiro],
            'Lowercase' => ['fevereiro', Month::Fevereiro],
            'Uppercase' => ['MARÇO', Month::Marco],
            'Without accent' => ['Marco', Month::Marco],
            'Mixed case' => ['SeTeMbRo', Month::Setembro],
            'October' => ['Outubro', Month::Outubro],
            'Dezembro standard' => ['Dezembro', Month::Dezembro],
            'Invalid month' => ['Invalid', false],
            'Empty string' => ['', false],
            'will get Abril?' => ['Abril', Month::Abril],
            'will get Maio?' => ['Maio', Month::Maio],
            'will get Junho?' => ['Junho', Month::Junho],
            'will get Julho?' => ['Julho', Month::Julho],
            'will get Agosto?' => ['Agosto', Month::Agosto],
            'will get Novembro?' => ['Novembro', Month::Novembro],
        ];
    }

    public function testValues(): void
    {
        $this->assertEquals("01", Month::Janeiro->value);
        $this->assertEquals("02", Month::Fevereiro->value);
        $this->assertEquals("03", Month::Marco->value);
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
