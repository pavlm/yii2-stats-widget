<?php
namespace pavlm\yii\stats\data;

use pavlm\yii\stats\factories\TimeSeriesProviderFactory;

/**
 * Multiple stats providers data combined 
 * @author pavlm
 */
class MultiStatsProvider implements TimeSeriesProvider
{
    /**
     * @var TimeSeriesProviderFactory[]
     */
    private $statsProviderFactories;
    
    /**
     * @var TimeSeriesProvider[]
     */
    private $statsProviders;
    
    /**
     * @param \DateTime $rangeStart
     * @param \DateTime $rangeEnd
     * @param \DateInterval $period
     * @param \DateTimeZone $timeZone
     * @param TimeSeriesProviderFactory[] $statsProviderFactories
     */
    public function __construct($rangeStart, $rangeEnd, $period, $timeZone, $statsProviderFactories)
    {
        $this->statsProviderFactories = $statsProviderFactories;
        $this->statsProviders = array_map(function (TimeSeriesProviderFactory $factory) use ($rangeStart, $rangeEnd, $period, $timeZone) {
            return $factory->create($rangeStart, $rangeEnd, $period, $timeZone);
        }, $this->statsProviderFactories);
    }

    /**
     * @return \DateInterval
     */
    public function getPeriod()
    {
        return $this->statsProviders[0]->getPeriod();
    }
    
    /**
     * @return \DateTime
     */
    public function getRangeStart()
    {
        return $this->statsProviders[0]->getRangeStart();
    }
    
    /**
     * @return \DateTime
     */
    public function getRangeEnd()
    {
        return $this->statsProviders[0]->getRangeEnd();
    }
    
    /**
     * @return \DateTimeZone
     */
    public function getTimeZone()
    {
        return $this->statsProviders[0]->getTimeZone();
    }
    
    /**
     * @return double
     */
    public function getTotalValue()
    {
        $totals = array_map(function (TimeSeriesProvider $provider) {
            return $provider->getTotalValue();
        }, $this->statsProviders);
        return $totals;
    }
    
    public function getIterator()
    {
        $it = new \MultipleIterator();
        foreach ($this->statsProviders as $provider) {
            $it->attachIterator($provider->getIterator());
        }
        foreach ($it as $mitem) {
            $values = array_map(function ($subItem) {
                return $subItem['value'];
            }, $mitem);
            $item = reset($mitem);
            $item['value'] = $values;
            yield $item;
        }
    }
    
}