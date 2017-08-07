<?php
namespace pavlm\yii\stats\data;

/**
 * 
 */
class DatePeriodIntervalsFormatter
{
    /**
     * @var \DatePeriod
     */
    protected $period;
    
    protected $partFormats = [
        'y' => 'Y',
        'm' => 'M',
        'd' => 'd',
        'h' => 'H',
        'i' => 'i',
        's' => 's',
    ];
    
    protected $dates = [];
    
    protected $periodFormat;
    
    public function __construct($period, $partFormats = [])
    {
        $this->period = $period;
        $this->partFormats = array_merge($this->partFormats, $partFormats);
    }

    protected function detectFormat()
    {
        $this->dates = [];
        $datePartFormats = ['Y', 'm', 'd', 'h', 'i', 's', ];
        $datePartsPrev = null;
        $datePartsChangedTotal = [];
        foreach ($this->period as $date) {
            $this->dates[] = $date;
            $dateParts = array_map(function ($format) use ($date) {
                return $date->format($format);
            }, $datePartFormats);
            if ($datePartsPrev) {
                $partsChanged = array_diff($dateParts, $datePartsPrev);
                if (!empty($partsChanged)) {
                    $datePartsChangedTotal = array_replace($datePartsChangedTotal, $partsChanged);
                }
            } else {
                $datePartsPrev = $dateParts;
            }
        }
        if (empty($datePartsChangedTotal)) {
            $this->periodFormat = 'Y.m.d H:i:s'; // todo
        } else {
            $indices = array_keys($datePartsChangedTotal);
            $from = min($indices);
            $to = max($indices);
            $formats = array_slice($this->partFormats, $from, $to - $from + 1);
            $this->periodFormat = implode(' ', $formats);
        }
    }
    
    public function getPeriodFormat()
    {
        if ($this->periodFormat === null) {
            $this->detectFormat();
        }
        return $this->periodFormat;
    }
    
    public function getPeriodsFormatted()
    {
        $this->getPeriodFormat();
        $periods = [];
        foreach ($this->dates as $date) {
            $periods[] = $date->format($this->getPeriodFormat());
        }
        return $periods;
    }
    
}