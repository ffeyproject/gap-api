<?php

namespace App\Helpers;

class Converter
{
    const YARD_TO_METER = 0.9144;
    const METER_TO_YARD = 1.09361;

    public static function yardToMeter($yard)
    {
        return $yard * self::YARD_TO_METER;
    }

    public static function meterToYard($meter)
    {
        return $meter * self::METER_TO_YARD;
    }
}