<?php

namespace Andr\ChmTideReader\Foundation;

enum Month: string
{
    case Janeiro = "01";
    case Fevereiro = "02";
    case Marco = "03";
    case Abril = "04";
    case Maio = "05";
    case Junho = "06";
    case Julho = "07";
    case Agosto = "08";
    case Setembro = "09";
    case Outubro = "10";
    case Novembro = "11";
    case Dezembro = "12";

    public static function get(string $month): self|false
    {
        return match ($month) {
            "Janeiro" => self::Janeiro,
            "Fevereiro" => self::Fevereiro,
            "Março" => self::Marco,
            "Abril" => self::Abril,
            "Maio" => self::Maio,
            "Junho" => self::Junho,
            "Julho" => self::Julho,
            "Agosto" => self::Agosto,
            "Setembro" => self::Setembro,
            "Outubro" => self::Outubro,
            "Novembro" => self::Novembro,
            "Dezembro" => self::Dezembro,
            default => false
        };
    }
}
