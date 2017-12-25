<?php

namespace pavlm\yii\stats\tests\data;

use pavlm\yii\stats\data\TimeSeriesProvider;

/**
 * Time series provider based on array 
 * For tests  
 * @author pavlm
 */
class TestStatsProvider implements TimeSeriesProvider
{
    private $array;
    
    private $start;
    
    private $end;
    
    private $period;
    
    /**
     * @param array $array
     * @param \DateTime $start
     * @param \DateInterval $interval
     */
    public function __construct($array, $start, $interval)
    {
        $this->array = $array;
        $this->start = $start;
        $this->period = $interval;
        $mulInterval = function ($idst, $isrc, $m) {
            foreach (['y', 'm', 'd', 'h', 'i', 's', ] as $part) {
                $idst->$part = $isrc->$part * $m;
            }
        };
        $rangeInterval = new \DateInterval('PT0S');
        $mulInterval($rangeInterval, $interval, count($array));
        $this->end = clone $start;
        $this->end->add($rangeInterval);
    }

    /**
     * @return \DateInterval
     */
    public function getPeriod()
    {
        return $this->period;
    }
    
    /**
     * @return \DateTime
     */
    public function getRangeStart()
    {
        return $this->start;
    }
    
    /**
     * @return \DateTime
     */
    public function getRangeEnd()
    {
        return $this->end;
    }
    
    /**
     * @return \DateTimeZone
     */
    public function getTimeZone()
    {
        return new \DateTimeZone('UTC');
    }
    
    /**
     * @return double
     */
    public function getTotalValue()
    {
        return 0;
    }
    
    public function getIterator()
    {
        $datePeriod = new \DatePeriod($this->start, $this->period, count($this->array) - 1);
        foreach ($datePeriod as $i => $period) {
            $v = $this->array[$i];
            yield [
                'ts' => $period->getTimestamp(),
                'value' => $v,
            ];
        }
    }
    
}