<?php
/**
 * @package yii2-ews
 * @author Simon Karlen <simi.albi@gmail.com>
 */

namespace simialbi\yii2\ews\models;

use jamesiarmes\PhpEws\Enumeration\CalendarItemTypeType;
use jamesiarmes\PhpEws\Enumeration\LegacyFreeBusyType;
use jamesiarmes\PhpEws\Type\CalendarItemType;
use yii\base\Model;
use yii\helpers\ArrayHelper;

/**
 * Class CalendarEvent
 * @package simialbi\yii2\ews\models
 */
class CalendarEvent extends Model
{
    /**
     * @var string
     */
    public $id;
    /**
     * @var string
     */
    public $changeKey;
    /**
     * @var string|\DateTime|integer
     */
    public $start;
    /**
     * @var string|\DateTime|integer
     */
    public $end;
    /**
     * @var string
     */
    public $subject;
    /**
     * @var string
     */
    public $type;
    /**
     * @var Contact
     */
    public $organizer;
    /**
     * @var boolean
     */
    public $isRecurring;
    /**
     * @var boolean
     */
    public $isAllDay;
    /**
     * @var boolean
     */
    public $isCancelled;
    /**
     * @var boolean
     */
    public $isOnline;
    /**
     * @var string
     */
    public $status;
    /**
     * @var string
     */
    public $body;

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
            ['organizer', 'safe'],
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
            ]
        ];
    }

    /**
     *
     * @param CalendarItemType $event
     * @return static
     */
    public static function fromEvent($event)
    {
        return new static([
            'id' => $event->ItemId->Id,
            'changeKey' => $event->ItemId->ChangeKey,
            'start' => date('Y-m-d H:i', strtotime($event->Start)),
            'end' => date('Y-m-d H:i', strtotime($event->End)),
            'subject' => $event->Subject,
            'body' => $event->Body,
            'type' => $event->CalendarItemType,
            'organizer' => new Contact([
                'id' => ArrayHelper::getValue($event->Organizer->Mailbox->ItemId, 'Id'),
                'changeKey' => ArrayHelper::getValue($event->Organizer->Mailbox->ItemId, 'ChangeKey'),
                'email' => $event->Organizer->Mailbox->EmailAddress,
                'name' => $event->Organizer->Mailbox->Name
            ]),
            'isRecurring' => $event->IsRecurring,
            'isAllDay' => $event->IsAllDayEvent,
            'isCancelled' => $event->IsCancelled,
            'isOnline' => $event->IsOnlineMeeting,
            'status' => $event->LegacyFreeBusyStatus
        ]);
    }
}
