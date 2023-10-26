<?php
/**
 * @package yii2-ews
 * @author Simon Karlen <simi.albi@gmail.com>
 */

namespace yiiunit\extensions\ews;

use jamesiarmes\PhpEws\Enumeration\BodyTypeType;
use jamesiarmes\PhpEws\Enumeration\SensitivityChoicesType;
use simialbi\yii2\ews\models\Message;

class MessageTest extends TestCase
{
    /**
     * @throws \ReflectionException
     */
    public function testAttributeMapping()
    {
        $attributeMapping = Message::attributeMapping();
        $expectedSubset = [
            'id' => [
                'readOnly' => false,
                'dataType' => ['string'],
                'foreignModel' => '\jamesiarmes\PhpEws\Type\ItemIdType',
                'foreignField' => 'ItemId.Id'
            ],
            'changeKey' => [
                'readOnly' => false,
                'dataType' => ['string'],
                'foreignModel' => '\jamesiarmes\PhpEws\Type\ItemIdType',
                'foreignField' => 'ItemId.ChangeKey'
            ],
            'parentFolderId' => [
                'readOnly' => false,
                'dataType' => ['string'],
                'foreignModel' => '\jamesiarmes\PhpEws\Type\FolderIdType',
                'foreignField' => 'ParentFolderId.Id'
            ],
            'attachments' => [
                'readOnly' => false,
                'dataType' => ['Attachment[]'],
                'foreignModel' => '\jamesiarmes\PhpEws\ArrayType\ArrayOfAttachmentsType',
                'foreignField' => 'Attachments.FileAttachment'
            ],
            'sensitivity' => [
                'readOnly' => false,
                'dataType' => ['string'],
                'foreignModel' => null,
                'foreignField' => 'Sensitivity'
            ],
            'importance' => [
                'readOnly' => false,
                'dataType' => ['string'],
                'foreignModel' => null,
                'foreignField' => 'Importance'
            ],
            'subject' => [
                'readOnly' => false,
                'dataType' => ['string'],
                'foreignModel' => null,
                'foreignField' => 'Subject'
            ],
            'format' => [
                'readOnly' => false,
                'dataType' => ['string'],
                'foreignModel' => '\jamesiarmes\PhpEws\Type\BodyType',
                'foreignField' => 'Body.BodyType'
            ],
            'body' => [
                'readOnly' => false,
                'dataType' => ['string'],
                'foreignModel' => '\jamesiarmes\PhpEws\Type\BodyType',
                'foreignField' => 'Body._'
            ],
            'messageId' => [
                'readOnly' => false,
                'dataType' => ['string'],
                'foreignModel' => null,
                'foreignField' => 'InternetMessageId'
            ],
            'isRead' => [
                'readOnly' => false,
                'dataType' => ['boolean'],
                'foreignModel' => null,
                'foreignField' => 'IsRead'
            ],
            'sentAt' => [
                'readOnly' => true,
                'dataType' => ['string', '\DateTime', 'integer'],
                'foreignModel' => null,
                'foreignField' => 'DateTimeSent'
            ],
            'createdAt' => [
                'readOnly' => true,
                'dataType' => ['string', '\DateTime', 'integer'],
                'foreignModel' => null,
                'foreignField' => 'DateTimeCreated'
            ],
            'updatedAt' => [
                'readOnly' => true,
                'dataType' => ['string', '\DateTime', 'integer'],
                'foreignModel' => null,
                'foreignField' => 'LastModifiedTime'
            ],
            'from' => [
                'readOnly' => false,
                'dataType' => ['Contact'],
                'foreignModel' => '\jamesiarmes\PhpEws\Type\SingleRecipientType',
                'foreignField' => 'From'
            ],
            'sender' => [
                'readOnly' => false,
                'dataType' => ['Contact'],
                'foreignModel' => '\jamesiarmes\PhpEws\Type\SingleRecipientType',
                'foreignField' => 'Sender'
            ],
            'to' => [
                'readOnly' => false,
                'dataType' => ['Contact[]'],
                'foreignModel' => '\jamesiarmes\PhpEws\Type\ArrayOfRecipientsType',
                'foreignField' => 'ToRecipients.Mailbox'
            ],
            'cc' => [
                'readOnly' => false,
                'dataType' => ['Contact[]'],
                'foreignModel' => '\jamesiarmes\PhpEws\Type\ArrayOfRecipientsType',
                'foreignField' => 'CcRecipients.Mailbox'
            ],
            'bcc' => [
                'readOnly' => false,
                'dataType' => ['Contact[]'],
                'foreignModel' => '\jamesiarmes\PhpEws\Type\ArrayOfRecipientsType',
                'foreignField' => 'BccRecipients.Mailbox'
            ],
        ];

        foreach ($attributeMapping as $key => $value) {
            $this->assertArrayHasKey($key, $expectedSubset);
        }

        foreach ($expectedSubset as $key => $value) {
            $this->assertArrayHasKey($key, $attributeMapping);
            $this->assertSame($value, $attributeMapping[$key]);
        }
    }

    public function testModel()
    {
        $message = new Message([
            'subject' => 'Test subject',
            'body' => 'Test body',
            'format' => BodyTypeType::TEXT,
            'isRead' => false,
            'sensitivity' => SensitivityChoicesType::CONFIDENTIAL,
            'from' => [
                'name' => 'Jane Doe',
                'email' => 'jane.doe@example.com'
            ],
            'to' => [
                'name' => 'John Doe',
                'email' => 'john.doe@example.com'
            ]
        ]);

        $this->assertTrue($message->validate());
    }
}
