<?php
/**
 * @package yii2-ews
 * @author Simon Karlen <simi.albi@gmail.com>
 */

namespace yiiunit\extensions\ews;

use simialbi\yii2\ews\recurrence\transformers\ExchangeTransformer;

class EwsToRuleTest extends TestCase
{
    /**
     * @throws \Exception
     */
    public function testDaily()
    {
        $data = [
            'AbsoluteMonthlyRecurrence' => null,
            'AbsoluteYearlyRecurrence' => null,
            'DailyRecurrence' => [
                'Interval' => 5
            ],
            'EndDateRecurrence' => [
                'StartDate' => '2025-11-17+01:00',
                'EndDate' => '2026-02-09+01:00'
            ],
            'NoEndRecurrence' => null,
            'NumberedRecurrence' => null,
            'RelativeMonthlyRecurrence' => null,
            'RelativeYearlyRecurrence' => null,
            'WeeklyRecurrence' => null,
            'DailyRegeneration' => null,
            'MonthlyRegeneration' => null,
            'WeeklyRegeneration' => null,
            'YearlyRegeneration' => null
        ];

        $this->assertEquals(
            'FREQ=DAILY;UNTIL=20260209T000000;INTERVAL=5',
            $this->getRule($data)->getString()
        );
    }

    /**
     * @throws \Exception
     */
    public function testeWeekly()
    {
        $data = [
            'AbsoluteMonthlyRecurrence' => null,
            'AbsoluteYearlyRecurrence' => null,
            'DailyRecurrence' => null,
            'EndDateRecurrence' => [
                'StartDate' => '2025-11-17+01:00',
                'EndDate' => '2026-05-04+02:00'
            ],
            'NoEndRecurrence' => null,
            'NumberedRecurrence' => null,
            'RelativeMonthlyRecurrence' => null,
            'RelativeYearlyRecurrence' => null,
            'WeeklyRecurrence' => [
                'Interval' => 2,
                'DaysOfWeek' => 'Monday Saturday',
                'FirstDayOfWeek' => 'Sunday'
            ],
            'DailyRegeneration' => null,
            'MonthlyRegeneration' => null,
            'WeeklyRegeneration' => null,
            'YearlyRegeneration' => null
        ];

        $this->assertEquals(
            'FREQ=WEEKLY;UNTIL=20260504T000000;INTERVAL=2;BYDAY=MO,SA',
            $this->getRule($data)->getString()
        );
    }

    /**
     * @throws \Exception
     */
    public function testAbsoluteMonthly()
    {
        $data = [
            'AbsoluteMonthlyRecurrence' => [
                'Interval' => 2,
                'DayOfMonth' => 17
            ],
            'AbsoluteYearlyRecurrence' => null,
            'DailyRecurrence' => null,
            'EndDateRecurrence' => null,
            'NoEndRecurrence' => null,
            'NumberedRecurrence' => [
                'StartDate' => '2025-11-17+01:00',
                'NumberOfOccurrences' => 7
            ],
            'RelativeMonthlyRecurrence' => null,
            'RelativeYearlyRecurrence' => null,
            'WeeklyRecurrence' => null,
            'DailyRegeneration' => null,
            'MonthlyRegeneration' => null,
            'WeeklyRegeneration' => null,
            'YearlyRegeneration' => null
        ];

        $this->assertEquals(
            'FREQ=MONTHLY;COUNT=7;INTERVAL=2;BYMONTHDAY=17',
            $this->getRule($data)->getString()
        );
    }

    /**
     * @throws \Exception
     */
    public function testRelativeMonthly()
    {
        $data = [
            'AbsoluteMonthlyRecurrence' => null,
            'AbsoluteYearlyRecurrence' => null,
            'DailyRecurrence' => null,
            'EndDateRecurrence' => null,
            'NoEndRecurrence' => [
                'StartDate' => '2025-11-17+01:00'
            ],
            'NumberedRecurrence' => null,
            'RelativeMonthlyRecurrence' => [
                'Interval' => 2,
                'DayOfWeekIndex' => 'Third',
                'DaysOfWeek' => 'Thursday'
            ],
            'RelativeYearlyRecurrence' => null,
            'WeeklyRecurrence' => null,
            'DailyRegeneration' => null,
            'MonthlyRegeneration' => null,
            'WeeklyRegeneration' => null,
            'YearlyRegeneration' => null
        ];

        $this->assertEquals(
            'FREQ=MONTHLY;INTERVAL=2;BYDAY=3TH',
            $this->getRule($data)->getString()
        );
    }

    /**
     * @throws \Exception
     */
    public function testAbsoluteYearly(): void
    {
        $data = [
            'AbsoluteMonthlyRecurrence' => null,
            'AbsoluteYearlyRecurrence' => [
                'DayOfMonth' => 20,
                'Month' => 'November'
            ],
            'DailyRecurrence' => null,
            'EndDateRecurrence' => null,
            'NoEndRecurrence' => [
                'StartDate' => '2023-11-20+01:00'
            ],
            'NumberedRecurrence' => null,
            'RelativeMonthlyRecurrence' => null,
            'RelativeYearlyRecurrence' => null,
            'WeeklyRecurrence' => null,
            'DailyRegeneration' => null,
            'MonthlyRegeneration' => null,
            'WeeklyRegeneration' => null,
            'YearlyRegeneration' => null,
        ];

        $this->assertEquals(
            'FREQ=YEARLY;INTERVAL=1;BYMONTHDAY=20;BYMONTH=11',
            $this->getRule($data)->getString()
        );
    }

    /**
     * @throws \Exception
     */
    public function testRelativeYearly(): void
    {
        $data = [
            'AbsoluteMonthlyRecurrence' => null,
            'AbsoluteYearlyRecurrence' => null,
            'DailyRecurrence' => null,
            'EndDateRecurrence' => [
                'StartDate' => '2025-11-17+01:00',
                'EndDate' => '2032-11-30+01:00'
            ],
            'NoEndRecurrence' => null,
            'NumberedRecurrence' => null,
            'RelativeMonthlyRecurrence' => null,
            'RelativeYearlyRecurrence' => [
                'DayOfWeekIndex' => 'Third',
                'DaysOfWeek' => 'Monday',
                'Month' => 'November',
            ],
            'WeeklyRecurrence' => null,
            'DailyRegeneration' => null,
            'MonthlyRegeneration' => null,
            'WeeklyRegeneration' => null,
            'YearlyRegeneration' => null,
        ];

        $this->assertEquals(
            'FREQ=YEARLY;UNTIL=20321130T000000;INTERVAL=1;BYDAY=3MO;BYMONTH=11',
            $this->getRule($data)->getString()
        );
    }

    /**
     * @param array $data
     * @return \Recurr\Rule
     * @throws \Exception
     */
    protected function getRule(array $data): \Recurr\Rule
    {
        $transformer = new ExchangeTransformer();
        return $transformer->transformRecurrenceFromEws($data);
    }
}
