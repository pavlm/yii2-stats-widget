<?php
namespace pavlm\yii\stats\data\formatter;

use pavlm\yii\stats\data\TimeSeriesProvider;

/**
 * Decorates data of a contained provider with date formatting
 * Input items like this:
 * [ 
 *   'ts' => 1483228800,
 *   'value' => 123, 
 * ]
 * Transformed to items like this:
 * [ 
 *   'ts' => 1483228800,
 *   'value' => 123,
 *   'start' => '2017-01-01T00:00:00',
 *   'label' => '2017 Jan 01', 
 * ]
 * 'label' format detected automatically.
 * It contains only changing date parts.
 * For date range ['2017-01-01', '2017-01-03') two labels will be produced: '01', '02'.
 * 
 * @author pavlm
 */
class TimeSeriesFormatter implements TimeSeriesProvider
{
    /**
     * @var TimeSeriesProvider
     */
    private $provider;
    
    /**
     * @var string
     */
    private $format;

    private $partFormats = [
        0 => '%Y',
        1 => '%h',
        2 => '%d',
        3 => '%H',
        4 => '%M',
        5 => '%S',
    ];
    
    public function __construct($provider)
    {
        $this->provider = $provider;
        $this->detectFormat();
    }
    
    /**
     * @return \DateInterval
     */
    public function getGroupInterval()
    {
        return $this->provider->getGroupInterval();
    }
    
    /**
     * @return \DateTime
     */
    public function getRangeStart()
    {
        return $this->provider->getRangeStart();
    }
    
    /**
     * @return \DateTime
     */
    public function getRangeEnd()
    {
        return $this->provider->getRangeEnd();
    }
    
    /**
     * @return \DateTimeZone
     */
    public function getTimeZone()
    {
        return $this->provider->getTimeZone();
    }
    
    /**
     * @return double
     */
    public function getTotalValue()
    {
        return $this->provider->getTotalValue();
    }
    
    protected function detectFormat()
    {
        // start of first and last periods
        $first = clone $this->getRangeStart();
        $last = clone $this->getRangeEnd();
        $last->sub($this->getGroupInterval());
        
        $splitDate = function ($date) {
            return explode('-', $date->format('Y-m-d-H-i-s'));
        };
        
        $firstParts = $splitDate($first);
        $lastParts = $splitDate($last);
        
        $delta = array_diff_assoc($firstParts, $lastParts);
        if (empty($delta)) {
            // special case for equal dates
            $partFrom = 0;
            $partTo = 5;
        } else {
            $partIndices = array_keys($delta);
            $partFrom = min($partIndices);
            $partTo = max($partIndices);
        }
        $this->format = implode(' ', array_slice($this->partFormats, $partFrom, $partTo - $partFrom + 1));
    }
    
    public function getIterator()
    {
        $it = $this->provider->getIterator();
        $tz = $this->provider->getTimeZone();
        foreach ($it as $period) {
            $date = new \DateTime('@' . $period['ts']);
            $date->setTimezone($tz);
            $period['start'] = $date->format('Y-m-d\TH:i:s');
            $period['label'] = strftime($this->format, $date->getTimestamp());
            yield $period;
        }
    }
    
}