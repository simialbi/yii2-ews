<?php
/**
 * @package yii2-ews
 * @author Simon Karlen <simi.albi@gmail.com>
 */

namespace simialbi\yii2\ews\models;

use jamesiarmes\PhpEws\Type\ContactItemType;
use jamesiarmes\PhpEws\Type\SingleRecipientType;
use simialbi\yii2\ews\ActiveRecord;
use yii\helpers\ArrayHelper;

/**
 * Class Contact
 * @package simialbi\yii2\ews\models
 *
 * @property string $id => \jamesiarmes\PhpEws\Type\ItemIdType:ItemId.Id
 * @property string $changeKey => \jamesiarmes\PhpEws\Type\ItemIdType:ItemId.ChangeKey
 * @property string $parentFolderId => \jamesiarmes\PhpEws\Type\FolderIdType:ParentFolderId.Id
 * @property string $parentFolderChangeKey => \jamesiarmes\PhpEws\Type\FolderIdType:ParentFolderId.ChangeKey
 * @property string $email => Mailbox.EmailAddress
 * @property string $name => Mailbox.Name
 */
class Contact extends ActiveRecord
{
    /**
     * {@inheritDoc}
     */
    public static function modelName(): string
    {
        return ContactItemType::class;
    }

    /**
     * Convert SingleRecipientType to Contact
     *
     * @param SingleRecipientType $recipient
     *
     * @return static
     * @throws \Exception
     */
    public static function fromSingleRecipient(SingleRecipientType $recipient): Contact
    {
        return new static([
            'id' => ArrayHelper::getValue($recipient->Mailbox->ItemId, 'Id'),
            'changeKey' => ArrayHelper::getValue($recipient->Mailbox->ItemId, 'ChangeKey'),
            'email' => $recipient->Mailbox->EmailAddress,
            'name' => $recipient->Mailbox->Name
        ]);
    }

    /**
     * {@inheritDoc}
     */
    public function rules(): array
    {
        return [
            [['name', 'changeKey', 'id'], 'string'],
            ['email', 'email', 'enableIDN' => function_exists('idn_to_ascii')]
        ];
    }
}
