<?php
namespace pavlm\yii\stats\data;

/**
 * Interface for time series access.
 * Iterator must return such items:
 * [ 
 *   'ts' => 1483228800, // timestamp of item beginning
 *   'value' => 123, // some aggregated value of item 
 * ]
 * 
 * @author pavlm
 */
interface TimeSeriesProvider extends \IteratorAggregate
{

    /**
     * @return \DateInterval
     */
    public function getGroupInterval();
    
    /**
     * @return \DateTime
     */
    public function getRangeStart();
    /**
     * @return \DateTime
     */
    public function getRangeEnd();
    
    /**
     * @return \DateTimeZone
     */
    public function getTimeZone();
    
    /**
     * @return double
     */
    public function getTotalValue();
    
}