<?php

namespace yiiunit\extensions\ews;

use jamesiarmes\PhpEws\Type\RecurrenceType;
use Recurr\Exception\InvalidArgument;
use Recurr\Exception\InvalidRRule;
use Recurr\Frequency;
use Recurr\Rule;
use simialbi\yii2\ews\recurrence\transformers\ExchangeTransformer;
use yii\base\InvalidConfigException;

class RuleToEwsTest extends TestCase
{
    /**
     * @throws InvalidConfigException
     * @throws InvalidArgument
     */
    public function testDaily()
    {
        $startDate = new \DateTime('2025-11-17 00:00:00');
        $endDate = new \DateTime('2026-02-09 00:00:00');

        $rule = new Rule();
        $rule->setFreq(Frequency::DAILY);
        $rule->setUntil($endDate);
        $rule->setInterval(5);
        $rule->setStartDate($startDate);


        $recurrenceType = $this->getRecurrenceType($rule);

        $this->assertEquals(5, $recurrenceType->DailyRecurrence->Interval);
        $this->assertEquals($startDate->format('c'), $recurrenceType->EndDateRecurrence->StartDate);
        $this->assertEquals($endDate->format('c'), $recurrenceType->EndDateRecurrence->EndDate);
    }

    /**
     * @throws InvalidArgument
     * @throws InvalidRRule
     * @throws InvalidConfigException
     */
    public function testWeekly()
    {
        $startDate = new \DateTime();
        $startDate->setTime(0, 0);

        $rule = new Rule();
        $rule->setFreq(Frequency::WEEKLY);
        $rule->setCount(3);
        $rule->setInterval(2);
        $rule->setByDay(['MO', 'SA']);


        $recurrenceType = $this->getRecurrenceType($rule);

        $this->assertEquals(2, $recurrenceType->WeeklyRecurrence->Interval);
        $this->assertEquals(3, $recurrenceType->NumberedRecurrence->NumberOfOccurrences);
        $this->assertEquals($startDate->format('c'), $recurrenceType->NumberedRecurrence->StartDate);
    }

    /**
     * @throws InvalidConfigException
     * @throws InvalidArgument
     */
    public function testAbsoluteMonthly()
    {
        $rule = new Rule();
        $rule->setFreq(Frequency::MONTHLY);
        $rule->setInterval(5);
        $rule->setByMonthDay([13]);


        $recurrenceType = $this->getRecurrenceType($rule);

        $this->assertEquals(5, $recurrenceType->AbsoluteMonthlyRecurrence->Interval);
        $this->assertEquals(13, $recurrenceType->AbsoluteMonthlyRecurrence->DayOfMonth);
    }

    /**
     * @throws InvalidArgument
     * @throws InvalidRRule
     * @throws InvalidConfigException
     */
    public function testRelativeMonth()
    {
        $rule = new Rule();
        $rule->setFreq(Frequency::MONTHLY);
        $rule->setInterval(2);
        $rule->setByDay(['-1SU']);


        $recurrenceType = $this->getRecurrenceType($rule);

        $this->assertEquals(2, $recurrenceType->RelativeMonthlyRecurrence->Interval);
        $this->assertEquals('Last', $recurrenceType->RelativeMonthlyRecurrence->DayOfWeekIndex);
        $this->assertEquals('Sunday', $recurrenceType->RelativeMonthlyRecurrence->DaysOfWeek);
    }

    /**
     * @throws InvalidArgument
     * @throws InvalidConfigException
     */
    public function testAbsoluteYear()
    {
        $rule = new Rule();
        $rule->setFreq(Frequency::YEARLY);
        $rule->setByMonthDay([12]);
        $rule->setByMonth([11]);


        $recurrenceType = $this->getRecurrenceType($rule);

        $this->assertEquals(12, $recurrenceType->AbsoluteYearlyRecurrence->DayOfMonth);
        $this->assertEquals(11, $recurrenceType->AbsoluteYearlyRecurrence->Month);
    }

    /**
     * @return void
     * @throws InvalidRRule
     * @throws InvalidArgument|InvalidConfigException
     */
    public function testRelativeYear()
    {
        $rule = new Rule();
        $rule->setFreq(Frequency::YEARLY);
        $rule->setByMonth([1]);
        $rule->setByDay(['+1MO']);


        $recurrenceType = $this->getRecurrenceType($rule);

        $this->assertEquals('First', $recurrenceType->RelativeYearlyRecurrence->DayOfWeekIndex);
        $this->assertEquals('Monday', $recurrenceType->RelativeYearlyRecurrence->DaysOfWeek);
        $this->assertEquals(1, $recurrenceType->RelativeYearlyRecurrence->Month);
    }

    /**
     * @param Rule $rule
     * @return RecurrenceType|null
     * @throws InvalidConfigException
     */
    protected function getRecurrenceType(Rule $rule): ?RecurrenceType
    {
        $t = new ExchangeTransformer();
        return $t->transformRecurrenceToEws($rule);
    }
}
