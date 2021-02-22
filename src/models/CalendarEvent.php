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

/**
 * Class CalendarEvent
 * @package simialbi\yii2\ews\models
 */
class CalendarEvent extends Model
{
    public $id;
    public $changeKey;
    public $start;
    public $end;
    public $subject;
    public $type;
    public $organizer;
    public $recurring;
    public $status;

    /**
     * {@inheritDoc}
     */
    public function rules()
    {
        return [
            [['id', 'changeKey', 'subject'], 'string'],
            ['start', 'datetime', 'format' => 'yyyy-MM-dd HH:mm', 'timestampAttribute' => 'start'],
            ['end', 'datetime', 'format' => 'yyyy-MM-dd HH:mm', 'timestampAttribute' => 'end'],
            ['recurring', 'boolean'],
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
            'type' => $event->CalendarItemType,
            'organizer' => new Contact([
                'id' => $event->Organizer->Mailbox->ItemId->Id,
                'changeKey' => $event->Organizer->Mailbox->ItemId->ChangeKey,
                'email' => $event->Organizer->Mailbox->EmailAddress,
                'name' => $event->Organizer->Mailbox->Name
            ]),
            'recurring' => $event->IsRecurring,
            'status' => $event->LegacyFreeBusyStatus
        ]);
    }
}
