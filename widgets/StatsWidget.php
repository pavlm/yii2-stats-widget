<?php
namespace pavlm\yii\stats\widgets;

use Yii;
use yii\base\Widget;
use yii\helpers\Url;
use yii\base\InvalidConfigException;

class StatsWidget extends Widget
{
    public $statsAction;
    
    public $levels = [
        ['P1M', 'P1Y', 'year'],
        ['P1D', 'P1M', 'month'],
        ['PT1H', 'P1D', 'day'],
    ];

    public $levelButtons = true;

    
    public $chartJsOptions = [
        'data' => [
            'datasets' => [
                0 => [
                    'backgroundColor' => 'rgba(0,150,0,0.7)',
                ],
            ],
        ],
        'options' => [
            'legend' => [
                'display' => false,
            ],
        ],
    ];
    
    public $canvasOptions = [
        'width' => '500',
        'height' => '200',
    ];
    
    public $options = [
    ];
    
    public $viewStatsControls = '_stats-controls';
    
    public function getClientOptions()
    {
        return [
            'statsAction' => Url::to($this->statsAction),
            'chartJsOptions' => $this->chartJsOptions,
            'levels' => $this->levels,
        ];
    }
    
    public function t($message, $params = [])
    {
        try {
            return Yii::t('stats-widget', $message, $params);
        } catch (InvalidConfigException $e) {
            return $message;
        }
    }
    
    public function run()
    {
        return $this->render('stats', [
            
        ]);
    }
}