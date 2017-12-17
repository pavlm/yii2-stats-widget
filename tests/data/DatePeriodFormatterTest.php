<?php
namespace pavlm\yii\stats\tests\data;

use pavlm\yii\stats\tests\TestCase;
use pavlm\yii\stats\data\DatePeriodFormatter;

class DatePeriodFormatterTest extends TestCase
{
    
    /**
     * @param \DateTime $start
     * @param \DateTime $end
     * @param string $expectedResult
     * @dataProvider testProvider
     */
    public function test($start, $end, $expectedResult)
    {
        setlocale(LC_ALL, 'C');
        $f = new DatePeriodFormatter($start, $end);
        $result = $f->format();
        $this->assertEquals($expectedResult, $result);
    }
    
    public function testProvider()
    {
        return [
            [
                new \DateTime('2016-12-31 01:02:03'),
                new \DateTime('2016-12-31 01:02:03'),
                '2016 Dec 31 01:02:03',
            ],
            [
                new \DateTime('2016-12-31 00:00:00'),
                new \DateTime('2017-01-01 00:00:00'),
                '2016 Dec 31',
            ],
            [
                new \DateTime('2016-12-01 00:00:00'),
                new \DateTime('2017-01-01 00:00:00'),
                '2016 Dec',
            ],
            [
                new \DateTime('2017-07-01 00:00:00'),
                new \DateTime('2017-08-01 00:00:00'),
                '2017 Jul',
            ],
            [
                new \DateTime('2017-07-01 00:00:00', new \DateTimeZone('Europe/Moscow')),
                new \DateTime('2017-08-01 00:00:00', new \DateTimeZone('Europe/Moscow')),
                '2017 Jul',
            ],
            [
                new \DateTime('2016-01-01 00:00:00'),
                new \DateTime('2017-01-01 00:00:00'),
                '2016',
            ],
            [
                new \DateTime('2016-01-01 00:00:00'),
                new \DateTime('2018-01-01 00:00:00'),
                '2016 - 2017',
            ],
            [
                new \DateTime('2016-01-01 00:00:00'),
                new \DateTime('2016-03-01 00:00:00'),
                '2016 Jan - 2016 Feb',
            ],
            [
                new \DateTime('2016-01-01 00:00:00'),
                new \DateTime('2016-01-03 00:00:00'),
                '2016 Jan 01 - 2016 Jan 02',
            ],
            [
                new \DateTime('2016-01-01 00:00:00'),
                new \DateTime('2016-01-02 00:00:00'),
                '2016 Jan 01',
            ],
            
        ];
    }
}