<?php
namespace pavlm\yii\stats\data;

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