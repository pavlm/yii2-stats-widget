<?php
namespace pavlm\yii\stats\data;

use yii\base\Object;
use yii\db\Query;
use yii\db\Expression;

class QueryStatsProvider extends Object implements TimeSeriesProvider
{
    const DATETYPE_DATETIME = 'DATETIME';
    const DATETYPE_TIMESTAMP = 'TIMESTAMP';
    const DATETYPE_INT = 'INT';
    
    /**
     * @var \DateInterval
     */
    public $groupInterval;

    /**
     * @var \DateTime
     */
    public $rangeStart;
    
    /**
     * @var \DateTime
     */
    public $rangeEnd;
    
    /**
     * @var Query
     */
    public $query;
    
    public $aggregateExpr = 'COUNT(*)';
    
    public $dateField = 'created_at';
    
    public $dateFieldType = self::DATETYPE_DATETIME;

    /**
     * @var \DateTimeZone - time zone of stored dates
     */
    public $timeZoneStorage = 'UTC';

    /**
     * @var \DateTimeZone - target time zone
     */
    public $timeZone;
    
    /**
     * @var \DateTimeZone - time zone of db connection
     */
    public $timeZoneConnection;
    
    /**
     * @var boolean
     */
    public $timeZoneForHours = false;
    
    /**
     * @var \Closure
     */
    public $totalFunc = 'array_sum';
    
    protected $rawData;

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
        $this->timeZoneConnection = $tzInit($this->timeZoneConnection, null);
    }
    
    /**
     * @return \DateInterval
     */
    public function getGroupInterval()
    {
        return $this->groupInterval;
    }
    
    /**
     * @return \DateTime
     */
    public function getRangeStart()
    {
        return $this->rangeStart;
    }
    
    /**
     * @return \DateTime
     */
    public function getRangeEnd()
    {
        return $this->rangeEnd;
    }
    
    /**
     * @return \DateTimeZone
     */
    public function getTimeZone()
    {
        return $this->timeZone;
    }
    
    /**
     * @return double
     */
    public function getTotalValue()
    {
        $values = array_map(function ($item) {
            return $item['value'];
        }, $this->loadData());
        return call_user_func($this->totalFunc, $values);
    }
    
    public function getIterator()
    {
        $data = $this->loadData(true);
        $range = new \DatePeriod($this->getRangeStart(), $this->getGroupInterval(), $this->getRangeEnd());
        $formatter = new DatePeriodIntervalsFormatter($range);
        $periods = $formatter->getPeriodsFormatted();
        foreach ($range as $i => $groupStart) {
            $uts = $groupStart->getTimestamp();
            $ts = $groupStart->format('Y-m-d\TH:i:s');
            //Yii::trace("stdp ts: $ts ({$groupStart->getTimeZone()->getName()})");
            yield [
                'start' => $ts,
                'value' => isset($data[$uts]) ? $data[$uts]['value'] : null,
                'label' => $periods[$i],
            ];
        }
    }

    protected function prepareQuery()
    {
        $query = $this->query;
        $this->query->andWhere([
            'AND',
            ['>=', $this->dateField, $this->convertDateToDBExpr($this->getRangeStart())],
            ['<', $this->dateField, $this->convertDateToDBExpr($this->getRangeEnd())],
        ]);
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
            if ($this->timeZoneConnection && $this->timeZoneConnection->getName() == $this->timeZone->getName()) {
                $fieldWithTZ = sprintf("FROM_UNIXTIME(%s)", $this->dateField); // no tz conversion needed
            } else {
                $fieldWithTZ = sprintf("CONVERT_TZ(FROM_UNIXTIME(%s), @@session.time_zone, '%s')",
                    $this->dateField, $this->timeZone->getName());
            }
        } else {
            $fieldWithTZ = sprintf("CONVERT_TZ(%s, '%s', '%s')", $this->dateField, $this->timeZoneStorage->getName(), $this->timeZone->getName());
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
'{$this->timeZone->getName()}', @@session.time_zone)
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
    protected function convertDateToDBExpr($dt)
    {
        if ($this->dateFieldType == self::DATETYPE_INT) {
            $sql = "'" . $dt->getTimestamp() . "'";
        } else {
            $sql = sprintf("CONVERT_TZ('%s', '%s', '%s')",
                $dt->format('c'), $dt->getTimezone()->getName(), $this->timeZoneStorage->getName());
        }
        return new Expression($sql);
    }
    
    public function loadData($reload = false)
    {
        if (!$this->rawData || $reload) {
            $this->prepareQuery();
            $this->rawData = $this->query->all();
        }
        return $this->rawData;
    }
    
}