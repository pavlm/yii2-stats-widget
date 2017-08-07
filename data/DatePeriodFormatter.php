<?php
namespace pavlm\yii\stats\data;

/**
 * 
 */
class DatePeriodFormatter
{
    /**
     * @var \DatePeriod
     */
    protected $period;
    
    /**
     * @var \DatePeriod
     */
    protected $range;
    
    public function __construct($period, $range = null)
    {
        $this->period = $period;
        $this->range = $range;
    }
}