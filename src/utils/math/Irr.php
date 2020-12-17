<?php

namespace fulicommons\util\math;

class Irr
{
    public static function irr($values, $guess = 0.1)
    {
        $values = array_values($values);
        // 初始化日期并检查值是否包含至少一个正值和一个负值
        $dates = array();
        $positive = false;
        $negative = false;
        foreach ($values as $index => $value) {
            $dates[] = ($index === 0) ? 0 : $dates[$index - 1] + 365;
            if ($values[$index] > 0) $positive = true;
            if ($values[$index] < 0) $negative = true;
        }
        if (!$positive || !$negative) return null;
        // Initialize guess and resultRate
        $resultRate = $guess;
        // Set maximum epsilon for end of iteration
        $epsMax = 0.0000000001;
        // Set maximum number of iterations
        $iterMax = 100;
        $iteration = 0;
        $contLoop = true;
        while ($contLoop && (++$iteration < $iterMax)) {
            $resultValue = self::irrResult($values, $dates, $resultRate);
            $derivValue = self::irrResultDeriv($values, $dates, $resultRate);
            $epsRate = $resultValue / $derivValue;
            $newRate = $resultRate - $epsRate;
            //echo "newRate:" . ($newRate * 100 * 12) . "%, resultRate:" . ($resultRate * 100 * 12) . "%, resultValue:" . round($resultValue, 6) . ", derivValue:" . round($derivValue, 6) . ", epsRate:" . ($epsRate * 100 * 12) . "\r\n";
            $resultRate = $newRate;
            $contLoop = (abs($epsRate) > $epsMax) && (abs($resultValue) > $epsMax);
        }
        if ($contLoop) return null;
        return $resultRate;
    }

    // Calculates the resulting amount
    public static function irrResult($values, $dates, $rate)
    {
        $r = $rate + 1;
        $result = $values[0];
        for ($i = 1; $i < count($values); $i++) {
            $frac = ($dates[$i] - $dates[0]) / 365;
            $result += $values[$i] / pow($r, $frac);
        }
        return $result;
    }

    // Calculates the first derivation
    public static function irrResultDeriv($values, $dates, $rate)
    {
        $r = $rate + 1;
        $result = 0;
        for ($i = 1; $i < count($values); $i++) {
            $frac = ($dates[$i] - $dates[0]) / 365;
            $result -= $frac * $values[$i] / pow($r, $frac + 1);
        }
        return $result;
    }

}
