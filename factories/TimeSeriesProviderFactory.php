<?php
namespace pavlm\yii\stats\factories;

use pavlm\yii\stats\data\TimeSeriesProvider;

interface TimeSeriesProviderFactory
{
    /**
     * @param \DateTime $rangeStart
     * @param \DateTime $rangeEnd
     * @param \DateInterval $groupInterval
     * @param \DateTimeZone $timeZone
     * @return TimeSeriesProvider
     */
    public function create($rangeStart, $rangeEnd, $groupInterval, $timeZone);
}