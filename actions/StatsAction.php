<?php
namespace pavlm\yii\stats\actions;

use Yii;
use yii\base\Action;
use yii\base\InvalidConfigException;
use yii\web\Response;
use yii\web\JsonResponseFormatter;
use pavlm\yii\stats\data\TimeSeriesProvider;
use pavlm\yii\stats\data\RangePagination;
use pavlm\yii\stats\data\DatePeriodFormatter;
use pavlm\yii\stats\factories\TimeSeriesProviderFactory;

class StatsAction extends Action
{
    
    /**
     * @var TimeSeriesProviderFactory
     */
    public $providerFactory;
    
    /**
     * @var RangePagination|string
     */
    public $defaultRange = 'P1Y';
    
    /**
     * @var \DateInterval|string
     */
    public $defaultGroup = 'P1M';
    
    /**
     * @var \DateTime
     */
    public $defaultStart;

    /**
     * @var \DateTimeZone|string
     */
    public $timeZone;

    /**
     * @var string
     */
    public $dateFormat = 'Y-m-d\TH:i:s';
    
    public $responseFormatter = [
        'class' => JsonResponseFormatter::class,
        'encodeOptions' => JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PARTIAL_OUTPUT_ON_ERROR,
    ];
    
    /**
     * @var RangePagination
     */
    protected $rangePagination;
    
    public function init()
    {
        $this->timeZone = is_string($this->timeZone) ? new \DateTimeZone($this->timeZone) : $this->timeZone;
        $this->timeZone = $this->timeZone ?: new \DateTimeZone(date_default_timezone_get());
        $this->defaultRange = is_string($this->defaultRange) ? new RangePagination($this->defaultRange, null, $this->timeZone) : $this->defaultRange;
        $this->defaultGroup = is_string($this->defaultGroup) ? new \DateInterval($this->defaultGroup) : $this->defaultGroup;
        $this->defaultStart = is_string($this->defaultStart) ? new \DateTime($this->defaultStart) : $this->defaultStart;
    }
    
    /**
     * todo rename $period
     * 
     * @param string $period
     * @param string $range
     * @param string $start
     * @param string $end
     * @return TimeSeriesProvider
     * @throws InvalidConfigException
     */
    protected function prepare($period = null, $range = null, $start = null, $end = null)
    {
        $groupInterval = $period ? new \DateInterval($period) : $this->defaultGroup;
        
        $dateStart = $start ? \DateTime::createFromFormat($this->dateFormat, $start, $this->timeZone) : $this->defaultStart;
        
        if ($range || $dateStart) {
            $this->rangePagination = new RangePagination(
                $range ?: $this->defaultRange->getInterval(),
                $dateStart ?: null,
                $this->timeZone);
        } else {
            $this->rangePagination = $this->defaultRange;
        }
        
        return $this->providerFactory->create(
            $this->rangePagination->getRangeStart(), 
            $this->rangePagination->getRangeEnd(), 
            $groupInterval, 
            $this->timeZone);
    }
    
    /**
     * @param string $period
     * @param string $range
     * @param string $start
     * @param string $end
     * @return mixed[]
     */
    public function run($period = null, $range = null, $start = null, $end = null)
    {
        $response = Yii::$app->response;
        $response->format = Response::FORMAT_JSON;
        $response->formatters[Response::FORMAT_JSON] = $this->responseFormatter;
        $provider = $this->prepare($period, $range, $start, $end);
        
        $intervalSpec = function ($interval) {
            return trim(preg_replace('#(?<=[A-Z])0.#', '', $interval->format('P%yY%mM%dDT%hH%iM%sS')), 'T');
        };
        $dpFormatter = new DatePeriodFormatter($this->rangePagination->getRangeStart(), $this->rangePagination->getRangeEnd());
        
        $data = [
            'stats' => [
                'data' => iterator_to_array($provider->getIterator()),
                'totalValue' => $provider->getTotalValue(),
            ],
            'state' => [
                'period' => $intervalSpec($provider->getGroupInterval()),
                'range' => $intervalSpec($this->rangePagination->getInterval()),
                'start' => $start,
                'prev' => $this->rangePagination->getPrevRangeStart()->format($this->dateFormat),
                'next' => $this->rangePagination->getNextRangeStart()->format($this->dateFormat),
                'rangeLabel' => $dpFormatter->format(),
            ],
        ];
        
        //return $data;
        
        // manual encoding to avoid JSON_ERROR_INF_OR_NAN error
        $response->format = Response::FORMAT_RAW;
        $response->getHeaders()->set('Content-Type', 'application/javascript; charset=UTF-8');
        $response->data = json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PARTIAL_OUTPUT_ON_ERROR);
        return $response;
    }
    
}