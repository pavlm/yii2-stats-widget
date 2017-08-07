<?php
namespace pavlm\yii\stats\data;

class TotalFuncs
{

    public static function sum($values)
    {
        return array_sum($values);
    }

    public static function avg($values)
    {
        return array_sum($values) / count($values);
    }

    public static function min($values)
    {
        $start = reset($values);
        return array_reduce($values, function ($min, $value) {
            return $min < $value ? $min : $value;
        }, $start);
    }

    public static function max($values)
    {
        $start = reset($values);
        return array_reduce($values, function ($max, $value) {
            return $max > $value ? $max : $value;
        }, $start);
    }
    
}