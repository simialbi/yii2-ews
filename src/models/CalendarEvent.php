<?php
/**
 * @package yii2-ews
 * @author Simon Karlen <simi.albi@gmail.com>
 */

namespace simialbi\yii2\ews\models;

use jamesiarmes\PhpEws\Enumeration\BodyTypeType;
use jamesiarmes\PhpEws\Enumeration\CalendarItemTypeType;
use jamesiarmes\PhpEws\Enumeration\LegacyFreeBusyType;
use jamesiarmes\PhpEws\Type\CalendarItemType;
use jamesiarmes\PhpEws\Type\RecurrenceType;
use Recurr\Rule;
use simialbi\yii2\ews\ActiveRecord;
use simialbi\yii2\ews\recurrence\transformers\ExchangeTransformer;
use yii\behaviors\AttributeTypecastBehavior;

/**
 * Class CalendarEvent
 * @package simialbi\yii2\ews\models
 *
 * @property string $id => \jamesiarmes\PhpEws\Type\ItemIdType:ItemId.Id
 * @property string $changeKey => \jamesiarmes\PhpEws\Type\ItemIdType:ItemId.ChangeKey
 * @property string $parentFolderId => \jamesiarmes\PhpEws\Type\FolderIdType:ParentFolderId.Id
 * @property string $parentFolderChangeKey => \jamesiarmes\PhpEws\Type\FolderIdType:ParentFolderId.ChangeKey
 * @property string|\DateTime|integer $start => Start
 * @property string|\DateTime|integer $end => End
 * @property string $subject => Subject
 * @property string $body => \jamesiarmes\PhpEws\Type\BodyType:Body._
 * @property string $format => \jamesiarmes\PhpEws\Type\BodyType:Body.BodyType
 * @property string $location => Location
 * @property string $type => CalendarItemType
 * @property string|\Recurr\Rule $recurrence => \jamesiarmes\PhpEws\Type\RecurrenceType:Recurrence
 * @property-read boolean $isRecurring => IsRecurring
 * @property boolean $isAllDay => IsAllDayEvent
 * @property boolean $isCancelled => IsCancelled
 * @property boolean $isOnline => IsOnlineMeeting
 * @property string $status => LegacyFreeBusyStatus
 * @property-read string|\DateTime|integer $createdAt => DateTimeCreated
 * @property-read string|\DateTime|integer $updatedAt => LastModifiedTime
 * @property Attachment[] $attachments => \jamesiarmes\PhpEws\ArrayType\ArrayOfAttachmentsType:Attachments.FileAttachment
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
    public function rules(): array
    {
        return [
            [['id', 'changeKey', 'parentFolderId', 'parentFolderChangeKey', 'subject', 'body', 'location'], 'string'],
            ['start', 'datetime', 'format' => 'yyyy-MM-dd HH:mm xxx', 'timestampAttribute' => 'start'],
            ['end', 'datetime', 'format' => 'yyyy-MM-dd HH:mm xxx', 'timestampAttribute' => 'end'],
            [['isRecurring', 'isAllDay', 'isCancelled', 'isOnline'], 'boolean'],
            ['type', 'in', 'range' => [
                CalendarItemTypeType::EXCEPTION,
                CalendarItemTypeType::OCCURRENCE,
                CalendarItemTypeType::RECURRING_MASTER,
                CalendarItemTypeType::SINGLE
            ]],
            ['format', 'in', 'range' => [
                BodyTypeType::HTML,
                BodyTypeType::TEXT
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

            ['recurrence', 'safe'],

            ['status', 'default', 'value' => LegacyFreeBusyType::BUSY],
            ['format', 'default', 'value' => BodyTypeType::HTML]
        ];
    }

    /**
     * {@inheritDoc}
     */
    public function beforeSave($insert): bool
    {
        if ($this->isAttributeChanged('body', false)) {
            $this->markAttributeDirty('format');
        }

        return parent::beforeSave($insert);
    }

    /**
     * {@inheritDoc}
     */
    public function behaviors(): array
    {
        return [
            'typecast' => [
                'class' => AttributeTypecastBehavior::class,
                'typecastAfterSave' => false,
                'typecastAfterValidate' => false,
                'typecastBeforeSave' => false,
                'typecastAfterFind' => true,
                'attributeTypes' => [
                    'start' => [$this, 'typeCastDateTime'],
                    'end' => [$this, 'typeCastDateTime'],
                    'createdAt' => [$this, 'typeCastDateTime'],
                    'updatedAt' => [$this, 'typeCastDateTime'],
                    'recurrence' => function (string|RecurrenceType|null|array $value): ?Rule {
                        if (empty($value)) {
                            return null;
                        }

                        $transformer = new ExchangeTransformer();
                        return $transformer->transformRecurrenceFromEws($value);
                    }
                ]
            ]
        ];
    }
}
