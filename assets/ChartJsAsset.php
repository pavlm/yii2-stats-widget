<?php
namespace pavlm\yii\stats\assets;

use yii\web\AssetBundle;

class ChartJsAsset extends AssetBundle
{
    public $sourcePath = '@bower/chartjs/dist';

    public $js = [
        'Chart.js'
    ];

}
