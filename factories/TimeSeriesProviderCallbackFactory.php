<?php
namespace pavlm\yii\stats\factories;

class TimeSeriesProviderCallbackFactory implements TimeSeriesProviderFactory
{
    private $callback;
    
    public function __construct($callback)
    {
        $this->callback = $callback;
    }
    
    public function create($rangeStart, $rangeEnd, $groupInterval, $timeZone)
    {
        return call_user_func_array($this->callback, func_get_args());
    }
    
}
