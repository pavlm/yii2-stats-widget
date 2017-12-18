<?php
namespace pavlm\yii\stats\tests\data\formatter;

use pavlm\yii\stats\tests\TestCase;
use pavlm\yii\stats\tests\data\TestStatsProvider;
use pavlm\yii\stats\data\formatter\TimeSeriesFormatter;

class TimeSeriesFormatterTest extends TestCase
{
    
    /**
     * @param array $array
     * @param \DateTime $start
     * @param \DateInterval $interval
     * @param array $labelsExpected
     * 
     * @dataProvider dataProviderForTest
     */
    public function test($array, $start, $interval, $labelsExpected)
    {
        $provider = new TestStatsProvider($array, $start, $interval);
        $formatter = new TimeSeriesFormatter($provider);
        $tseries = iterator_to_array($formatter->getIterator());
        $this->assertEquals(count($array), count($tseries));
        $labels = array_map(function ($period) {
            return $period['label'];
        }, $tseries);
        $this->assertEquals($labelsExpected, $labels);
        //print_R($tseries);
    }
    
    public function dataProviderForTest()
    {
        return [
            [
                [1, 2, 3, ], 
                new \DateTime('2017-01-01'), 
                new \DateInterval('P1D'),
                ['01', '02', '03', ],
            ],
            [
                [1, 2, 3, ],
                new \DateTime('2017-01-31'),
                new \DateInterval('P1D'),
                ['Jan 31', 'Feb 01', 'Feb 02', ],
            ],
            [
                [1, 2, 3, ],
                new \DateTime('2017-12-31'),
                new \DateInterval('P1D'),
                ['2017 Dec 31', '2018 Jan 01', '2018 Jan 02', ],
            ],
            [
                [1, 2, ],
                new \DateTime('2017-01-01'),
                new \DateInterval('P1Y'),
                ['2017', '2018', ],
            ],
            
        ];
    }
    
}
