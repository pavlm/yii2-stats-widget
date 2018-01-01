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
use pavlm\yii\stats\factories\TimeSeriesFormatterFactory;
use pavlm\yii\stats\factories\TimeSeriesFormatterCallbackFactory;
use pavlm\yii\stats\data\formatter\TimeSeriesFormatter;

/**
 * Binds StatsWidget and TimeSeriesProvider together.
 * Handles query from StatsWidget.
 * Responds with data from configured TimeSeriesProvider. 
 * 
 * @author pavlm
 */
class StatsAction extends Action
{
    
    /**
     * @var TimeSeriesProviderFactory specific time series provider constructor
     */
    public $providerFactory;
    
    /**
     * @var TimeSeriesFormatterFactory
     */
    public $formatterFactory;
    
    /**
     * @var RangePagination|string initial range width
     */
    public $defaultRange = 'P1Y';
    
    /**
     * @var \DateInterval|string initial period width
     */
    public $defaultPeriod = 'P1M';
    
    /**
     * @var \DateTime
     */
    public $defaultStart;

    /**
     * @var \DateTimeZone|string time zone of time series
     */
    public $timeZone;

    /**
     * @var string input date format
     */
    public $dateFormat = 'Y-m-d\TH:i:s';
    
    /**
     * @var string class name for a date pagination
     */
    public $rangePaginationClass = RangePagination::class;
    
    /**
     * @var string class name for a date formatter
     */
    public $datePeriodFormatterClass = DatePeriodFormatter::class;
    
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
        if (!$this->providerFactory) {
            throw new InvalidConfigException();
        }
        $this->timeZone = is_string($this->timeZone) ? new \DateTimeZone($this->timeZone) : $this->timeZone;
        $this->timeZone = $this->timeZone ?: new \DateTimeZone(date_default_timezone_get());
        $this->defaultRange = is_string($this->defaultRange) ? Yii::createObject($this->rangePaginationClass, [$this->defaultRange, null, null, $this->timeZone]) : $this->defaultRange;
        $this->defaultPeriod = is_string($this->defaultPeriod) ? new \DateInterval($this->defaultPeriod) : $this->defaultPeriod;
        $this->defaultStart = is_string($this->defaultStart) ? new \DateTime($this->defaultStart) : $this->defaultStart;
        if (!$this->formatterFactory) {
            $this->formatterFactory = new TimeSeriesFormatterCallbackFactory(function ($provider) {
                return new TimeSeriesFormatter($provider);
            });
        }
    }
    
    /**
     * @param string $period
     * @param string $range
     * @param string $start
     * @param string $end
     * @return TimeSeriesProvider
     */
    protected function prepare($period = null, $range = null, $start = null, $end = null)
    {
        $period = $period ? new \DateInterval($period) : $this->defaultPeriod;
        
        $dateStart = $start ? \DateTime::createFromFormat($this->dateFormat, $start, $this->timeZone) : $this->defaultStart;
        
        $dateEnd = $end ? \DateTime::createFromFormat($this->dateFormat, $end, $this->timeZone) : null;
        
        if ($range || $dateStart) {
            $this->rangePagination = Yii::createObject($this->rangePaginationClass, [
                $range ?: $this->defaultRange->getInterval(),
                $dateStart,
                $dateEnd,
                $this->timeZone,
            ]);
        } else {
            $this->rangePagination = $this->defaultRange;
        }
        
        $provider = $this->providerFactory->create(
            $this->rangePagination->getRangeStart(), 
            $this->rangePagination->getRangeEnd(), 
            $period, 
            $this->timeZone);
        $formatter = $this->formatterFactory->create($provider);
        return $formatter;
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
        $dpFormatter = Yii::createObject($this->datePeriodFormatterClass, [$this->rangePagination->getRangeStart(), $this->rangePagination->getRangeEnd()]);
        
        $data = [
            'stats' => [
                'data' => iterator_to_array($provider->getIterator()),
                'totalValue' => $provider->getTotalValue(),
            ],
            'state' => [
                'period' => $intervalSpec($provider->getPeriod()),
                'range' => $intervalSpec($this->rangePagination->getInterval()),
                'start' => $start,
                'prev' => $this->rangePagination->getPrevRangeStart()->format($this->dateFormat),
                'next' => $this->rangePagination->getNextRangeStart()->format($this->dateFormat),
                'rangeLabel' => $dpFormatter->format(),
            ],
        ];
        
        //return $data;
        
        // manual response encoding to avoid JSON_ERROR_INF_OR_NAN error
        $response->format = Response::FORMAT_RAW;
        $response->getHeaders()->set('Content-Type', 'application/javascript; charset=UTF-8');
        $response->data = json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PARTIAL_OUTPUT_ON_ERROR);
        return $response;
    }
    
}