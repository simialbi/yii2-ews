<?php
/**
 * @package yii2-ews
 * @author Simon Karlen <simi.albi@gmail.com>
 */

namespace simialbi\yii2\ews\models;

use jamesiarmes\PhpEws\Enumeration\CalendarItemTypeType;
use jamesiarmes\PhpEws\Enumeration\LegacyFreeBusyType;
use jamesiarmes\PhpEws\Type\CalendarItemType;
use simialbi\yii2\ews\ActiveRecord;

/**
 * Class CalendarEvent
 * @package simialbi\yii2\ews\models
 *
 * @property string $id => \jamesiarmes\PhpEws\Type\ItemIdType:ItemId.Id
 * @property string $changeKey => \jamesiarmes\PhpEws\Type\ItemIdType:ItemId.ChangeKey
 * @property string|\DateTime|integer $start => Start
 * @property string|\DateTime|integer $end => End
 * @property string $subject => Subject
 * @property string $body => \jamesiarmes\PhpEws\Type\BodyType:Body._
 * @property string $type => CalendarItemType
 * @property boolean $isRecurring => IsRecurring
 * @property boolean $isAllDay => IsAllDayEvent
 * @property boolean $isCancelled => IsCancelled
 * @property boolean $isOnline => IsOnlineMeeting
 * @property string $status => LegacyFreeBusyStatus
 *
 * @property Contact $organizer => \jamesiarmes\PhpEws\Type\SingleRecipientType:Organizer
 * @property Attendee[] $requiredAttendees => \jamesiarmes\PhpEws\ArrayType\NonEmptyArrayOfAttendeesType:RequiredAttendees.Attendee
 * @property Attendee[] $optionalAttendees => \jamesiarmes\PhpEws\ArrayType\NonEmptyArrayOfAttendeesType:OptionalAttendees.Attendee
 */
class CalendarEvent extends ActiveRecord
{
    /**
     * {@inheritDoc}
     */
    public static function modelName(): string
    {
        return CalendarItemType::class;
    }

    /**
     * {@inheritDoc}
     */
    public function rules()
    {
        return [
            [['id', 'changeKey', 'subject', 'body'], 'string'],
            ['start', 'datetime', 'format' => 'yyyy-MM-dd HH:mm', 'timestampAttribute' => 'start'],
            ['end', 'datetime', 'format' => 'yyyy-MM-dd HH:mm', 'timestampAttribute' => 'end'],
            [['isRecurring', 'isAllDay', 'isCancelled', 'isOnline'], 'boolean'],
            ['type', 'in', 'range' => [
                CalendarItemTypeType::EXCEPTION,
                CalendarItemTypeType::OCCURRENCE,
                CalendarItemTypeType::RECURRING_MASTER,
                CalendarItemTypeType::SINGLE
            ]],
            [['organizer', 'requiredAttendees', 'optionalAttendees'], 'safe'],
            [
                'status',
                'in',
                'range' => [
                    LegacyFreeBusyType::BUSY,
                    LegacyFreeBusyType::FREE,
                    LegacyFreeBusyType::NO_DATA,
                    LegacyFreeBusyType::OUT_OF_OFFICE,
                    LegacyFreeBusyType::TENTATIVE,
                    LegacyFreeBusyType::WORKING_ELSEWHERE
                ]
            ],

            ['status', 'default', 'value' => LegacyFreeBusyType::BUSY],
            ['type', 'default', 'value' => CalendarItemTypeType::SINGLE],
            [['isRecurring', 'isAllDay', 'isCancelled', 'isOnline'], 'default', 'value' => false]
        ];
    }
}
