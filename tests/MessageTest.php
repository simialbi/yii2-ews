<?php
/**
 * @package yii2-ews
 * @author Simon Karlen <simi.albi@gmail.com>
 */

namespace yiiunit\extensions\ews;

use simialbi\yii2\ews\models\Message;

class MessageTest extends TestCase
{
    public function testAttributeMapping()
    {
        $attributeMapping = Message::attributeMapping();
        $this->assertArraySubset([
            'id' => [
                'dataType' => ['string'],
                'foreignModel' => '\jamesiarmes\PhpEws\Type\ItemIdType',
                'foreignField' => 'ItemId.Id'
            ],
            'changeKey' => [
                'dataType' => ['string'],
                'foreignModel' => '\jamesiarmes\PhpEws\Type\ItemIdType',
                'foreignField' => 'ItemId.ChangeKey'
            ],
            'parentFolderId' => [
                'dataType' => ['string'],
                'foreignModel' => '\jamesiarmes\PhpEws\Type\FolderIdType',
                'foreignField' => 'ParentFolderId.Id'
            ],
            'sensitivity' => [
                'dataType' => ['string'],
                'foreignModel' => null,
                'foreignField' => 'Sensitivity'
            ],
            'importance' => [
                'dataType' => ['string'],
                'foreignModel' => null,
                'foreignField' => 'Importance'
            ],
            'subject' => [
                'dataType' => ['string'],
                'foreignModel' => null,
                'foreignField' => 'Subject'
            ],
            'format' => [
                'dataType' => ['string'],
                'foreignModel' => '\jamesiarmes\PhpEws\Type\BodyType',
                'foreignField' => 'Body.BodyType'
            ],
            'body' => [
                'dataType' => ['string'],
                'foreignModel' => '\jamesiarmes\PhpEws\Type\BodyType',
                'foreignField' => 'Body._'
            ],
            'messageId' => [
                'dataType' => ['string'],
                'foreignModel' => null,
                'foreignField' => 'InternetMessageId'
            ],
            'isRead' => [
                'dataType' => ['boolean'],
                'foreignModel' => null,
                'foreignField' => 'IsRead'
            ],
            'sentAt' => [
                'dataType' => ['string', '\DateTime', 'integer'],
                'foreignModel' => null,
                'foreignField' => 'DateTimeSent'
            ],
            'createdAt' => [
                'dataType' => ['string', '\DateTime', 'integer'],
                'foreignModel' => null,
                'foreignField' => 'DateTimeCreated'
            ],
            'updatedAt' => [
                'dataType' => ['string', '\DateTime', 'integer'],
                'foreignModel' => null,
                'foreignField' => 'LastModifiedTime'
            ],
        ], $attributeMapping);
    }
}
