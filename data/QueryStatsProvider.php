<?php
namespace pavlm\yii\stats\data;

use yii\base\Object;
use yii\db\Query;
use yii\db\Expression;

/**
 * Time series provider for mysql tables.
 * Generates sql query considering date range, group period and time zone.
 * 
 * @author pavlm
 */
class QueryStatsProvider extends Object implements TimeSeriesProvider
{
    const DATETYPE_DATETIME = 'DATETIME';
    const DATETYPE_TIMESTAMP = 'TIMESTAMP';
    const DATETYPE_INT = 'INT';
    
    /**
     * @var \DateInterval
     */
    public $periodInterval;

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
    public function getPeriodInterval()
    {
        return $this->periodInterval;
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
        $range = new \DatePeriod($this->getRangeStart(), $this->getPeriodInterval(), $this->getRangeEnd());
        foreach ($range as $i => $groupStart) {
            $uts = $groupStart->getTimestamp();
            yield [
                'ts' => $uts,
                'value' => isset($data[$uts]) ? $data[$uts]['value'] : null,
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
         if ($this->periodInterval->y) {
         $groupExpr = sprintf("YEAR(%s)", $fieldWithTZ);
         } elseif ($this->periodInterval->m) {
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
    
        if ($this->periodInterval->y) {
            $groupExpr = "DATE_FORMAT( {$fieldWithTZ}, '%Y-01-01' )";
        } elseif ($this->periodInterval->m) {
            $groupExpr = "DATE_FORMAT( {$fieldWithTZ}, '%Y-%m-01' )";
        } elseif ($this->periodInterval->d) {
            $groupExpr = "DATE_FORMAT( {$fieldWithTZ}, '%Y-%m-%d' )";
        } elseif ($this->periodInterval->h) {
            $groupExpr = "DATE_FORMAT( {$fieldWithTZ}, '%Y-%m-%d %H:00:00' )";
        } elseif ($this->periodInterval->i) {
            $groupExpr = "DATE_FORMAT( {$fieldWithTZ}, '%Y-%m-%d %H:%i:00' )";
        } elseif ($this->periodInterval->s) {
            $groupExpr = "DATE_FORMAT( {$fieldWithTZ}, '%Y-%m-%d %H:%i:%s' )";
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