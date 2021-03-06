<?php
namespace pavlm\yii\stats\data;

/**
 * Formates date range in brief form.
 * Examples of formatting:
 * ['2016-12-31', '2017-01-01') => '2016 Dec 31'
 * ['2016-01-01', '2016-03-01') => '2016 Jan - 2016 Feb',
 */
class DatePeriodFormatter
{

    /**
     * @var \DateTime
     */
    protected $start;

    /**
     * @var \DateTime
     */
    protected $end;
    
    protected static $intervalParts = ['y', 'm', 'd', 'h', 'i', 's', ];
    
    protected static $intervalPartSpecs = ['Y', 'M', 'D', 'H', 'M', 'S', ];

    protected $datePartFormats = [
        'y' => ['%Y', ''],
        'm' => ['%h', ' '],
        'd' => ['%d', ' '],
        'h' => ['%H', ' '],
        'i' => ['%M', ':'],
        's' => ['%S', ':'],
    ];
    
    /**
     * @param \DateTime $start
     * @param \DateTime $end
     * @param array $datePartFormats
     */
    public function __construct($start, $end, $datePartFormats = [])
    {
        $this->setStart($start);
        $this->setEnd($end);
        $this->datePartFormats = array_merge($this->datePartFormats, $datePartFormats);
    }

    protected function setStart($start)
    {
        // disable tz due to bug https://bugs.php.net/bug.php?id=52480
        $this->start = new \DateTime($start->format('Y-m-d H:i:s'), new \DateTimeZone('UTC'));
    }

    protected function setEnd($end)
    {
        // disable tz due to bug https://bugs.php.net/bug.php?id=52480
        $this->end = new \DateTime($end->format('Y-m-d H:i:s'), new \DateTimeZone('UTC'));
    }
    
    public function format()
    {
        $i = $this->start->diff($this->end, true);
        $iparts = array_map(function ($part) use ($i) {
            return $i->$part;
        }, self::$intervalParts);
        $singleCalendarPeriod = in_array(array_sum($iparts), [1, 0]);
        $aparts = array_filter($iparts);
        $lastPartIndex = empty($aparts) ? false : array_keys($aparts)[count($aparts) - 1];
        $lastPartIndex = $lastPartIndex === false ? 5 : $lastPartIndex;
        
        $datePartFormats = array_slice(array_values($this->datePartFormats), 0, $lastPartIndex + 1);
        $format = array_reduce($datePartFormats, function ($format, $part) {
            return $format . $part[1] . $part[0];
        }, '');
        if (!$singleCalendarPeriod) {
            // decrease last changed component by one
            $spec = 'P' . ($lastPartIndex > 2 ? 'T' : '') . '1' . self::$intervalPartSpecs[$lastPartIndex];
            $subInterval = new \DateInterval($spec);
            $this->end->sub($subInterval);
        }
        $tzPrev = date_default_timezone_get();
        date_default_timezone_set($this->start->getTimezone()->getName()); // for strftime()
        $dates = $singleCalendarPeriod ? [$this->start] : [$this->start, $this->end];
        foreach ($dates as $i => $date) {
            $dates[$i] = strftime($format, $date->getTimestamp()); // $date->format($format);
        }
        date_default_timezone_set($tzPrev);
        return implode(' - ', $dates);
    }
}