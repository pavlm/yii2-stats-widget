<?php
namespace pavlm\yii\stats\assets;

use yii\web\AssetBundle;
use yii\web\JqueryAsset;

class StatsWidgetAsset extends AssetBundle
{
    public $sourcePath = __DIR__ . '/src';

    public $js = [
        'stats-widget.js'
    ];

    public $depends = [
        ChartJsAsset::class,
        JqueryAsset::class,
    ];
}
