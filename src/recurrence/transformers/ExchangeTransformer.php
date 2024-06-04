<?php

namespace simialbi\yii2\ews\recurrence\transformers;

use jamesiarmes\PhpEws\Enumeration\DayOfWeekIndexType;
use jamesiarmes\PhpEws\Enumeration\DayOfWeekType;
use jamesiarmes\PhpEws\Type\AbsoluteMonthlyRecurrencePatternType;
use jamesiarmes\PhpEws\Type\AbsoluteYearlyRecurrencePatternType;
use jamesiarmes\PhpEws\Type\DailyRecurrencePatternType;
use jamesiarmes\PhpEws\Type\EndDateRecurrenceRangeType;
use jamesiarmes\PhpEws\Type\NoEndRecurrenceRangeType;
use jamesiarmes\PhpEws\Type\NumberedRecurrenceRangeType;
use jamesiarmes\PhpEws\Type\RecurrenceType;
use jamesiarmes\PhpEws\Type\RelativeMonthlyRecurrencePatternType;
use jamesiarmes\PhpEws\Type\RelativeYearlyRecurrencePatternType;
use jamesiarmes\PhpEws\Type\WeeklyRecurrencePatternType;
use Recurr\Frequency;
use Recurr\Rule;
use Yii;
use yii\base\InvalidConfigException;
use yii\helpers\ArrayHelper;

class ExchangeTransformer
{
    /**
     * Transform a recurrence rule to an EWS RecurrencyType object
     * @param Rule $rule The rule to transform
     * @return RecurrenceType|null The resulting object
     * @throws \yii\base\InvalidConfigException
     */
    public function transformRecurrenceToEws(Rule $rule): ?RecurrenceType
    {
        $recurrence = new RecurrenceType();
        $interval = $rule->getInterval();
        $count = $rule->getCount();
        $until = $rule->getUntil();
        $startDate = $rule->getStartDate() ?? 'today';
        $endDate = $rule->getEndDate();

        switch ($rule->getFreq()) {
            case Frequency::YEARLY:
                $byMonthDay = $rule->getByMonthDay();
                $byMonth = $this->partAsString($rule->getByMonth());
                if (!empty($byMonthDay)) {
                    $byMonthDay = $this->partAsString($byMonthDay);
                    $recurrence->AbsoluteYearlyRecurrence = new AbsoluteYearlyRecurrencePatternType();
                    $recurrence->AbsoluteYearlyRecurrence->Month = $byMonth;
                    $recurrence->AbsoluteYearlyRecurrence->DayOfMonth = $byMonthDay;
                } else {
                    $byDay = $this->partAsString($rule->getByDay());
                    $byDayInt = (int)preg_replace('#[^\-+\d]#', '', $byDay);
                    $byDayString = preg_replace('#[\-+\d]#', '', $byDay);
                    $recurrence->RelativeYearlyRecurrence = new RelativeYearlyRecurrencePatternType();
                    $recurrence->RelativeYearlyRecurrence->Month = $byMonth;
                    $recurrence->RelativeYearlyRecurrence->DayOfWeekIndex = $this->weekDayIndexToString($byDayInt);
                    $recurrence->RelativeYearlyRecurrence->DaysOfWeek = $this->abbrToWeekDay($byDayString);
                }
                break;
            case Frequency::MONTHLY:
                $byMonthDay = $rule->getByMonthDay();
                if (!empty($byMonthDay)) {
                    $byMonthDay = $this->partAsString($byMonthDay);
                    $recurrence->AbsoluteMonthlyRecurrence = new AbsoluteMonthlyRecurrencePatternType();
                    $recurrence->AbsoluteMonthlyRecurrence->DayOfMonth = $byMonthDay;
                    $recurrence->AbsoluteMonthlyRecurrence->Interval = $interval;
                } else {
                    $byDay = $this->partAsString($rule->getByDay());
                    $byDayInt = (int)preg_replace('#[^\-\d]#', '', $byDay);
                    $byDayString = preg_replace('#[\-\d]#', '', $byDay);
                    $recurrence->RelativeMonthlyRecurrence = new RelativeMonthlyRecurrencePatternType();
                    $recurrence->RelativeMonthlyRecurrence->DayOfWeekIndex = $this->weekDayIndexToString($byDayInt);
                    $recurrence->RelativeMonthlyRecurrence->DaysOfWeek = $this->abbrToWeekDay($byDayString);
                    $recurrence->RelativeMonthlyRecurrence->Interval = $interval;
                }
                break;
            case Frequency::WEEKLY:
                $byDay = array_map(function (string $item) {
                    return $this->abbrToWeekDay($item);
                }, (array)$rule->getByDay());
                $recurrence->WeeklyRecurrence = new WeeklyRecurrencePatternType();
                $recurrence->WeeklyRecurrence->DaysOfWeek = implode(' ', $byDay);
                $recurrence->WeeklyRecurrence->Interval = $interval;
                break;
            case Frequency::DAILY:
                $recurrence->DailyRecurrence = new DailyRecurrencePatternType();
                $recurrence->DailyRecurrence->Interval = $interval;
                break;
            default:
                return null;
        }

        if ($count) {
            $recurrence->NumberedRecurrence = new NumberedRecurrenceRangeType();
            $recurrence->NumberedRecurrence->StartDate = Yii::$app->formatter->asDatetime($startDate, 'yyyy-MM-dd\'T\'HH:mm:ssxxx');
            $recurrence->NumberedRecurrence->NumberOfOccurrences = $count;
        } elseif ($endDate || $until) {
            $recurrence->EndDateRecurrence = new EndDateRecurrenceRangeType();
            $recurrence->EndDateRecurrence->StartDate = Yii::$app->formatter->asDatetime($startDate, 'yyyy-MM-dd\'T\'HH:mm:ssxxx');
            $recurrence->EndDateRecurrence->EndDate = Yii::$app->formatter->asDatetime($endDate ?? $until, 'yyyy-MM-dd\'T\'HH:mm:ssxxx');
        } else {
            $recurrence->NoEndRecurrence = new NoEndRecurrenceRangeType();
            $recurrence->NoEndRecurrence->StartDate = Yii::$app->formatter->asDatetime($startDate, 'yyyy-MM-dd\'T\'HH:mm:ssxxx');
        }

        return $recurrence;
    }

    /**
     * Transform an EWS RecurrencyType array to a recurrence rule
     * @param array $recurrence
     * @return Rule
     * @throws \Exception
     */
    public function transformRecurrenceFromEws(array $recurrence): Rule
    {
        $rule = new Rule();

        // Start and end dates
        if (isset($recurrence['NoEndRecurrence'])) {
            $startDate = $recurrence['NoEndRecurrence']['StartDate'];
        } elseif (isset($recurrence['EndDateRecurrence'])) {
            $startDate = $recurrence['EndDateRecurrence']['StartDate'];
            $endDate = (new \DateTime($recurrence['EndDateRecurrence']['EndDate']))
                ->setTime(23, 59, 59); // EWS is inclusive, so we need the end of the day
            $rule->setUntil($endDate);
        } elseif (isset($recurrence['NumberedRecurrence'])) {
            $startDate = $recurrence['NumberedRecurrence']['StartDate'];
            $rule->setCount($recurrence['NumberedRecurrence']['NumberOfOccurrences']);
        } else {
            throw new InvalidConfigException('No end date or count given');
        }
        $rule->setStartDate(new \DateTime($startDate));


        // Intervals

        //daily
        if (isset($recurrence['DailyRecurrence'])) {
            $rule->setFreq(Frequency::DAILY);
            $rule->setInterval($recurrence['DailyRecurrence']['Interval']);
        }

        // weekly
        if (isset($recurrence['WeeklyRecurrence'])) {
            $rule->setFreq(Frequency::WEEKLY);
            $rule->setInterval($recurrence['WeeklyRecurrence']['Interval']);
            $days = explode(' ', $recurrence['WeeklyRecurrence']['DaysOfWeek']);
            $d = [];
            foreach ($days as $day) {
                $d[] = $this->weekDayToAbbr($day);
            }
            $rule->setByDay($d);
        }

        // absolute monthly
        if (isset($recurrence['AbsoluteMonthlyRecurrence'])) {
            $rule->setFreq(Frequency::MONTHLY);
            $rule->setInterval($recurrence['AbsoluteMonthlyRecurrence']['Interval']);
            $day = $recurrence['AbsoluteMonthlyRecurrence']['DayOfMonth'];
            $rule->setByMonthDay([$day]);
        }

        // relative monthly
        if (isset($recurrence['RelativeMonthlyRecurrence'])) {
            $rule->setFreq(Frequency::MONTHLY);
            $rule->setInterval($recurrence['RelativeMonthlyRecurrence']['Interval']);
            $day = $this->weekDayToAbbr($recurrence['RelativeMonthlyRecurrence']['DaysOfWeek']);
            $week = $this->stringToWeekDayIndex($recurrence['RelativeMonthlyRecurrence']['DayOfWeekIndex']);
            $rule->setByDay([$week . $day]);
        }

        // absolute yearly
        if (isset($recurrence['AbsoluteYearlyRecurrence'])) {
            $rule->setFreq(Frequency::YEARLY);
            $rule->setInterval(1);
            $rule->setByMonth([date_parse($recurrence['AbsoluteYearlyRecurrence']['Month'])['month']]);
            $rule->setByMonthDay([$recurrence['AbsoluteYearlyRecurrence']['DayOfMonth']]);
        }

        // relative yearly
        if (isset($recurrence['RelativeYearlyRecurrence'])) {
            $rule->setFreq(Frequency::YEARLY);
            $rule->setInterval(1);
            $rule->setByMonth([date_parse($recurrence['RelativeYearlyRecurrence']['Month'])['month']]);
            $day = $this->weekDayToAbbr($recurrence['RelativeYearlyRecurrence']['DaysOfWeek']);
            $week = $this->stringToWeekDayIndex($recurrence['RelativeYearlyRecurrence']['DayOfWeekIndex']);
            $rule->setByDay([$week . $day]);
        }

        return $rule;
    }

    /**
     * @param $string string The string to convert
     * @return int
     */
    private function stringToWeekDayIndex(string $string): int
    {
        return match ($string) {
            DayOfWeekIndexType::SECOND => 2,
            DayOfWeekIndexType::THIRD => 3,
            DayOfWeekIndexType::FOURTH => 4,
            DayOfWeekIndexType::LAST => -1,
            default => 1
        };
    }

    /**
     * @param int $index The index to convert
     * @return string
     */
    private function weekDayIndexToString(int $index): string
    {

        return match ($index) {
            2 => DayOfWeekIndexType::SECOND,
            3 => DayOfWeekIndexType::THIRD,
            4 => DayOfWeekIndexType::FOURTH,
            -1 => DayOfWeekIndexType::LAST,
            default => DayOfWeekIndexType::FIRST
        };
    }

    /**
     * @param $weekday
     * @return string
     */
    private function weekDayToAbbr($weekday): string
    {
        return match ($weekday) {
            DayOfWeekType::TUESDAY => 'TU',
            DayOfWeekType::WEDNESDAY => 'WE',
            DayOfWeekType::THURSDAY => 'TH',
            DayOfWeekType::FRIDAY => 'FR',
            DayOfWeekType::SATURDAY => 'SA',
            DayOfWeekType::SUNDAY => 'SU',
            default => 'MO'
        };
    }

    /**
     * @param $abbr
     * @return string
     */
    private function abbrToWeekDay($abbr): string
    {
        return match ($abbr) {
            'TU' => DayOfWeekType::TUESDAY,
            'WE' => DayOfWeekType::WEDNESDAY,
            'TH' => DayOfWeekType::THURSDAY,
            'FR' => DayOfWeekType::FRIDAY,
            'SA' => DayOfWeekType::SATURDAY,
            'SU' => DayOfWeekType::SUNDAY,
            default => DayOfWeekType::MONDAY
        };
    }

    private function partAsString(array|string $part): string
    {
        if (is_array($part) && !ArrayHelper::isAssociative($part) && isset($part[0])) {
            return $part[0];
        }

        return (string)$part;
    }
}
