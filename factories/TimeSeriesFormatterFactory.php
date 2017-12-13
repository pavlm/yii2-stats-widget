<?php
namespace pavlm\yii\stats\factories;

use pavlm\yii\stats\data\TimeSeriesProvider;

interface TimeSeriesFormatterFactory
{
    /**
     * @param TimeSeriesProvider $provider
     * @return TimeSeriesProvider
     */
    public function create($provider);
}