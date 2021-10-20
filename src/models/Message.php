<?php
/**
 * @package yii2-ews
 * @author Simon Karlen <simi.albi@gmail.com>
 */

namespace simialbi\yii2\ews\models;

use jamesiarmes\PhpEws\Enumeration\BodyTypeType;
use jamesiarmes\PhpEws\Enumeration\ImportanceChoicesType;
use jamesiarmes\PhpEws\Enumeration\SensitivityChoicesType;
use jamesiarmes\PhpEws\Type\MessageType;
use simialbi\yii2\ews\ActiveRecord;
use Yii;
use yii\behaviors\AttributeTypecastBehavior;

/**
 * Class Message
 * @package simialbi\yii2\ews\models
 *
 * @property string $id => \jamesiarmes\PhpEws\Type\ItemIdType:ItemId.Id
 * @property string $changeKey => \jamesiarmes\PhpEws\Type\ItemIdType:ItemId.ChangeKey
 * @property string $parentFolderId => \jamesiarmes\PhpEws\Type\FolderIdType:ParentFolderId.Id
 * @property string $sensitivity => Sensitivity
 * @property string $importance => Importance
 * @property string $subject => Subject
 * @property string $body => \jamesiarmes\PhpEws\Type\BodyType:Body._
 * @property string $format => \jamesiarmes\PhpEws\Type\BodyType:Body.BodyType
 * @property string $messageId => InternetMessageId
 * @property boolean $isRead => IsRead
 * @property string|\DateTime|integer $sentAt => DateTimeSent
 * @property string|\DateTime|integer $createdAt => DateTimeCreated
 * @property string|\DateTime|integer $updatedAt => LastModifiedTime
 *
 * @property Contact $from => \jamesiarmes\PhpEws\Type\SingleRecipientType:From
 * @property Contact $sender => \jamesiarmes\PhpEws\Type\SingleRecipientType:Sender
 * @property Contact[] $to => \jamesiarmes\PhpEws\Type\ArrayOfRecipientsType:ToRecipients.Mailbox
 * @property Contact[] $cc => \jamesiarmes\PhpEws\Type\ArrayOfRecipientsType:CcRecipients.Mailbox
 * @property Contact[] $bcc => \jamesiarmes\PhpEws\Type\ArrayOfRecipientsType:BccRecipients.Mailbox
 */
class Message extends ActiveRecord
{
    /**
     * {@inheritDoc}
     */
    public static function modelName(): string
    {
        return MessageType::class;
    }

    /**
     * {@inheritDoc}
     */
    public function rules(): array
    {
        return [
            [['id', 'changeKey', 'sensitivity', 'subject', 'body', 'format', 'messageId'], 'string'],
            [['isRead'], 'boolean'],
            ['sensitivity', 'in', 'range' => [
                SensitivityChoicesType::NORMAL,
                SensitivityChoicesType::CONFIDENTIAL,
                SensitivityChoicesType::PERSONAL,
                SensitivityChoicesType::PRIVATE_ITEM]
            ],
            ['format', 'in', 'range' => [
                BodyTypeType::HTML,
                BodyTypeType::TEXT
            ]],
            ['importance', 'in', 'range' => [
                ImportanceChoicesType::NORMAL,
                ImportanceChoicesType::HIGH,
                ImportanceChoicesType::LOW
            ]],

            [['from', 'sender', 'to', 'cc', 'bcc'], 'safe'],

            ['sensitivity', 'default', 'value' => SensitivityChoicesType::NORMAL],
            ['format', 'default', 'value' => BodyTypeType::HTML],
            ['isRead', 'default', 'value' => false],
            ['importance', 'default', 'value' => ImportanceChoicesType::NORMAL]
        ];
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
                    'sentAt' => [$this, 'typeCastDateTime'],
                    'createdAt' => [$this, 'typeCastDateTime'],
                    'updatedAt' => [$this, 'typeCastDateTime']
                ]
            ]
        ];
    }
}
