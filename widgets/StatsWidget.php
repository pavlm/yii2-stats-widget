<?php
namespace pavlm\yii\stats\widgets;

use yii\base\Widget;
use yii\helpers\Url;

class StatsWidget extends Widget
{
    public $statsAction;
    
    public $levels = [
        ['P1M', 'P1Y', '12 monthes'],
        ['P1D', 'P1M', '31 day'],
        ['PT1H', 'P1D', '24 hours'],
    ];

    public $btnHome = ['a', '<i class="glyphicon glyphicon-home"></i>', ['class' => 'stwg-btn-home btn btn-default btn-xs']];

    public $btnPrev = ['a', '<i class="glyphicon glyphicon-chevron-left"></i>', ['class' => 'stwg-btn-prev btn btn-default btn-xs']];

    public $btnNext = ['a', '<i class="glyphicon glyphicon-chevron-right"></i>', ['class' => 'stwg-btn-next btn btn-default btn-xs']];

    public $btnZoomIn = ['a', '<i class="glyphicon glyphicon-plus"></i>', ['class' => 'stwg-btn-zoomin btn btn-default btn-xs']];

    public $btnZoomOut = ['a', '<i class="glyphicon glyphicon-minus"></i>', ['class' => 'stwg-btn-zoomout btn btn-default btn-xs']];
    
    public $rangeLabel = ['span', '', ['class' => 'stwg-range-label btn btn-default btn-xs']];
    
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
    
    public function getClientOptions()
    {
        return [
            'statsAction' => Url::to($this->statsAction),
            'chartJsOptions' => $this->chartJsOptions,
            'levels' => $this->levels,
        ];
    }
    
    public function run()
    {
        return $this->render('stats', [
            
        ]);
    }
}