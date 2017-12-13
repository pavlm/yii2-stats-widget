<?php
namespace pavlm\yii\stats\factories;

class TimeSeriesFormatterCallbackFactory implements TimeSeriesFormatterFactory
{
    private $callback;
    
    public function __construct($callback)
    {
        $this->callback = $callback;
    }
    
    public function create($provider)
    {
        return call_user_func_array($this->callback, func_get_args());
    }
    
}
