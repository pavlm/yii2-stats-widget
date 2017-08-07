<?php
namespace pavlm\yii\stats\data;

class RangePagination
{
    const ALIGN_CURRENT = 'current';
    const ALIGN_PAST = 'past';
    
    /**
     * @var \DateInterval
     */
    private $interval;
    
    /**
     * @var \DateTime
     */
    private $start;
    
    /**
     * @var \DateTimeZone
     */
    private $timeZone;
    
    /**
     * @var \DatePeriod
     */
    private $datePeriod;
    
    /**
     * @var \DateTime
     */
    private $rangeStart;
    
    /**
     * @var \DateTime
     */
    private $rangeEnd;
    
    /**
     * 'currentRange' - current incompleted range with incompleted periods
     * 'past' - only completed periods 
     * @var string 
     */
    public $defaultAlignment;
    
    /**
     * 
     * @param string|\DateInterval $interval
     * @param integer $start
     * @param string $timeZone
     */
    public function __construct($interval = 'P1D', $start = null, $timeZone = null)
    {
        $this->interval = is_string($interval) ? new \DateInterval($interval) : $interval;
        $this->setStart($start);
        $this->setTimeZone($timeZone);
        $this->init();
    }
    
    protected function setTimeZone($timeZone)
    {
        $this->timeZone = is_string($timeZone) ? 
            new \DateTimeZone($timeZone) : 
            (!$timeZone ? (new \DateTime())->getTimezone() : $timeZone);
    }

    protected function init()
    {
        $parts = ['y', 'm', 'd', 'h', 'i', 's'];
        $formats = ['Y', 'm', 'd', 'H', 'i', 's'];
        $values = [0, 1, 1, 0, 0, 0];
        $date = $this->getStart();
        if (!$date) {
            $date = new \DateTime();
            $date->setTimezone($this->timeZone);
        }
        foreach ($parts as $i => $part) {
            $values[$i] = $date->format($formats[$i]);
            if ($this->interval->$part) {
                break; // leave rest of part as zero
            }
        }
        call_user_func_array([$date, 'setDate'], array_slice($values, 0, 3));
        call_user_func_array([$date, 'setTime'], array_slice($values, 3, 3));
        $this->rangeStart = $date;
        $date = clone $date;
        $this->rangeEnd = $date->add($this->interval);
    }
    
    protected function setStart($value)
    {
        if ($value) {
            $this->start = is_object($value) ? $value : \DateTime::createFromFormat('U', $value);
        }
    }
    
    public function getInterval()
    {
        return $this->interval;
    }
    
    public function getStart()
    {
        return $this->start;
    }
    
    public function getRangeStart()
    {
        return $this->rangeStart;
    }
    
    public function getRangeEnd()
    {
        return $this->rangeEnd;
    }
    
    public function getPrevRangeStart()
    {
        $date = clone $this->getRangeStart();
        return $date->sub($this->interval);
    }
    
    public function getNextRangeStart()
    {
        $date = clone $this->getRangeEnd();
        return clone $date;
    }
    
    /**
     * @return \DatePeriod
     */
    public function getDatePeriod()
    {
        return $this->datePeriod;
    }
}
