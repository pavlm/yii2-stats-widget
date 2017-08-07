<?php
namespace pavlm\yii\stats\data;

use yii\base\Object;
use yii\db\Query;
use yii\db\Expression;

class StatsDataProvider extends Object
{
    const DATETYPE_DATETIME = 'DATETIME';
    const DATETYPE_TIMESTAMP = 'TIMESTAMP';
    const DATETYPE_INT = 'INT';
    
    /**
     * @var \DateInterval
     */
    public $groupInterval;
    
    /**
     * @var \DateInterval
     */
    public $rangeInterval;
    
    /**
     * @var Query
     */
    public $query;
    
    public $aggregateExpr = 'COUNT(*)';
    
    public $dateField = 'created_at';
    
    public $dateFieldType = self::DATETYPE_DATETIME;

    /**
     * @var string|\DateTimeZone - target time zone
     */
    public $timeZone;
    
    /**
     * @var string|\DateTimeZone - time zone of stored dates
     */
    public $timeZoneStorage = 'UTC';
    
    /**
     * @var string|\DateTimeZone - time zone of db connection
     */
    public $timeZoneConnection;
    
    /**
     * @var boolean
     */
    public $timeZoneForHours = false;
    
    /**
     * @var RangePagination
     */
    public $pagination;
    
    /**
     * @var \Closure
     */
    public $totalFunc = 'array_sum';
    
    protected $rawData;
    
    protected $dataArray;
    
    public function init()
    {
        $tzInit = function ($tz, $default) {
            if ($tz === null) {
                return is_string($default) ? new \DateTimeZone($default) : $default;
            }
            return is_string($tz) ? new \DateTimeZone($tz) : $tz;
        };
        
        $this->timeZone = $tzInit($this->timeZone, $this->timeZone ?: (new \DateTime())->getTimezone());
        $this->timeZoneStorage = $tzInit($this->timeZoneStorage, 'UTC');
        $this->timeZoneConnection = $tzInit($this->timeZoneConnection, $this->timeZoneStorage);
    }
    
    public function prepare()
    {
        $query = $this->query;
        if ($this->pagination) {
            $start = $this->pagination->getRangeStart();
            $end = $this->pagination->getRangeEnd();
            $this->query->andWhere([
                'AND',
                ['>=', $this->dateField, $this->convertDateToDBExpr($start)],
                ['<', $this->dateField, $this->convertDateToDBExpr($end)],
            ]);
        }
        $query->select(['value' => $this->aggregateExpr]);
        
        /*
        if ($this->dateFieldType == self::DATETYPE_INT) {
            $fieldWithTZ = sprintf("CONVERT_TZ(FROM_UNIXTIME(%s), 'SYSTEM', '%s')", $this->dateField, $this->timeZone->getName());
        } else {
            $fieldWithTZ = sprintf("CONVERT_TZ(%s, 'SYSTEM', '%s')", $this->dateField, $this->timeZone->getName());
        }

        $groupExpr = '';
        if ($this->groupInterval->y) {
            $groupExpr = sprintf("YEAR(%s)", $fieldWithTZ);
        } elseif ($this->groupInterval->m) {
            $groupExpr = sprintf("YEAR(%s) * 12 + MONTH(%s)", $fieldWithTZ, $fieldWithTZ);
        }
        
        $query->addSelect(['value' => new Expression($groupExpr)]);
        $query->groupBy('value');
        $query->orderBy([$this->dateField => SORT_ASC]);
        */
        
        if ($this->dateFieldType == self::DATETYPE_INT) {
            if ($this->timeZoneConnection->getName() == $this->timeZone->getName()) {
                $fieldWithTZ = sprintf("FROM_UNIXTIME(%s)", $this->dateField); // no tz conversion needed
            } else {
                $fieldWithTZ = sprintf("CONVERT_TZ(FROM_UNIXTIME(%s), '%s', '%s')", 
                    $this->dateField, $this->timeZoneConnection->getName(), $this->timeZone->getName());
            }
        } else {
            $fieldWithTZ = sprintf("CONVERT_TZ(%s, 'SYSTEM', '%s')", $this->dateField, $this->timeZone->getName());
        }
        
        if ($this->groupInterval->y) {
            $groupExpr = <<<EXPR
CONCAT(
 YEAR( {$fieldWithTZ} ), '-'
 '01', '-',
 '01'
)
EXPR;
            $groupExpr = "DATE_FORMAT( {$fieldWithTZ}, '%Y-01-01' )";
        } elseif ($this->groupInterval->m) {
            $groupExpr = <<<EXPR
CONCAT(
 YEAR( {$fieldWithTZ} ), '-',
 MONTH( {$fieldWithTZ} ), '-',
 '01'
)
EXPR;
        } elseif ($this->groupInterval->d) {
            $groupExpr = <<<EXPR
CONCAT(
 YEAR( {$fieldWithTZ} ), '-',
 MONTH( {$fieldWithTZ} ), '-',
 DAY( {$fieldWithTZ} )
)
EXPR;
        } elseif ($this->groupInterval->h) {
                
            if ($this->timeZoneForHours) {
                $groupExpr = <<<EXPR
CONCAT(
 YEAR( {$fieldWithTZ} ), '-',
 MONTH( {$fieldWithTZ} ), '-',
 DAY( {$fieldWithTZ} ), ' ',
 HOUR( {$fieldWithTZ} ), '-',
 '00', '-',
 '00'
)
EXPR;
            } else {
                $groupExpr = "DATE_FORMAT( {$fieldWithTZ}, '%Y-%m-%d %H:00:00' )";
            }
        }
        
        // now $groupExpr contains date timestamp in target time zone
        // converting to universal time
        
        $groupExpr = <<<EXPR
UNIX_TIMESTAMP(
CONVERT_TZ(
{$groupExpr},
'{$this->timeZone->getName()}', '{$this->timeZoneConnection->getName()}')
)
EXPR;

        $query->addSelect(['start' => new Expression($groupExpr)]);
        $query->groupBy('start');
        $query->indexBy('start');
        $query->orderBy([$this->dateField => SORT_ASC]);
        
        /*
         * query result fields
         * 'start' - timestamp of period start
         * 'value' - aggregated value
         * 
         */
        
        /*
         * s ts - (ts % ${seconds:-1})
         * i ts - (ts % (60 * ${minutes:-1}))
         * h YMD() + (HOUR() - HOUR() % ${hours});
         * d YEAR() + DAYOFYEAR()
         * w YEARWEEK() - % ...
         * m YEAR() * 12 + MONTH() - % ...
         * y YEAR() - % ...
         * 
         */
    }
    
    /**
     * @param \DateTime $dt
     */
    public function convertDateToDBExpr($dt)
    {
        if ($this->dateFieldType == self::DATETYPE_INT) {
            $sql = "'" . $dt->getTimestamp() . "'";
        } else {
            $sql = sprintf("CONVERT_TZ('%s', '%s', '%s')", 
                $dt->format('c'), $dt->getTimezone()->getName(), $this->timeZoneStorage->getName());
        }
        return new Expression($sql);
    }
    
    public function getRawData()
    {
        if (!$this->rawData) {
            $this->prepare();
            $this->rawData = $this->query->all();
        }
        return $this->rawData;
    }
    
    /**
     * @return Generator
     */
    public function getData()
    {
        $data = $this->getRawData();
        if ($this->pagination) {
            $start = $this->pagination->getRangeStart();
            $end = $this->pagination->getRangeEnd();
            $range = new \DatePeriod($start, $this->groupInterval, $end);
            $formatter = new DatePeriodIntervalsFormatter($range);
            $periods = $formatter->getPeriodsFormatted();
            foreach ($range as $i => $groupStart) {
                $uts = $groupStart->getTimestamp();
                $ts = $groupStart->format('Y-m-d\TH:i:s');
                yield [
                    'start' => $ts, 
                    'value' => isset($data[$uts]) ? $data[$uts]['value'] : 0,
                    'label' => $periods[$i],
                ];
            }
        } else {
            
        }
    }
    
    public function getDataArray($refresh = false)
    {
        if ($refresh || $this->dataArray === null) {
            $this->dataArray = iterator_to_array($this->getData());
        }
        return $this->dataArray;
    }
    
    public function getTotalValue()
    {
        $values = array_map(function ($item) {
            return $item['value'];
        }, $this->getDataArray());
        return call_user_func($this->totalFunc, $values);
    }
    
}
