<?php
namespace pavlm\yii\stats\data;

/**
 * 
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

    protected $datePartFormats = [
        'y' => ['Y', ''],
        'm' => ['M', ' '],
        'd' => ['d', ' '],
        'h' => ['H', ' '],
        'i' => ['i', ':'],
        's' => ['s', ':'],
    ];
    
    public function __construct($start, $end, $datePartFormats = [])
    {
        $this->start = $start;
        $this->end = $end;
        $this->datePartFormats = array_merge($this->datePartFormats, $datePartFormats);
    }
    
    public function format()
    {
        $i = $this->start->diff($this->end, true);
        $iparts = array_map(function ($part) use ($i) {
            return $i->$part;
        }, self::$intervalParts);
        $singleCalendarPeriod = in_array(array_sum($iparts), [1, 0]);
        $aparts = array_filter($iparts);
        $lastPartIndex = end(array_keys($aparts));
        $lastPartIndex = $lastPartIndex === false ? 5 : $lastPartIndex;
        
        $datePartFormats = array_slice(array_values($this->datePartFormats), 0, $lastPartIndex + 1);
        $format = array_reduce($datePartFormats, function ($format, $part) {
            return $format . $part[1] . $part[0];
        }, '');
        $dates = $singleCalendarPeriod ? [$this->start] : [$this->start, $this->end];
        foreach ($dates as $i => $date) {
            $dates[$i] = $date->format($format);
        }
        return implode(' - ', $dates);
    }
}