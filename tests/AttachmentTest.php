<?php

namespace yiiunit\extensions\ews;

use simialbi\yii2\ews\models\Attachment;

class AttachmentTest extends TestCase
{
    /**
     * @throws \ReflectionException
     */
    public function testAttributeMapping()
    {
        $attributeMapping = Attachment::attributeMapping();
        $expectedSubset = [
            'id' => [
                'readOnly' => false,
                'dataType' => ['string'],
                'foreignModel' => '\jamesiarmes\PhpEws\Type\AttachmentIdType',
                'foreignField' => 'AttachmentId.Id'
            ],
            'rootId' => [
                'readOnly' => false,
                'dataType' => ['string'],
                'foreignModel' => '\jamesiarmes\PhpEws\Type\AttachmentIdType',
                'foreignField' => 'AttachmentId.RootItemId'
            ],
            'rootChangeKey' => [
                'readOnly' => false,
                'dataType' => ['string'],
                'foreignModel' => '\jamesiarmes\PhpEws\Type\AttachmentIdType',
                'foreignField' => 'AttachmentId.RootItemChangeKey'
            ],
            'contentId' => [
                'readOnly' => false,
                'dataType' => ['string'],
                'foreignModel' => null,
                'foreignField' => 'ContentId'
            ],
            'location' => [
                'readOnly' => false,
                'dataType' => ['string'],
                'foreignModel' => null,
                'foreignField' => 'ContentLocation'
            ],
            'mime' => [
                'readOnly' => false,
                'dataType' => ['string'],
                'foreignModel' => null,
                'foreignField' => 'ContentType'
            ],
            'isInline' => [
                'readOnly' => false,
                'dataType' => ['boolean'],
                'foreignModel' => null,
                'foreignField' => 'IsInline'
            ],
            'name' => [
                'readOnly' => false,
                'dataType' => ['string'],
                'foreignModel' => null,
                'foreignField' => 'Name'
            ],
            'size' => [
                'readOnly' => false,
                'dataType' => ['integer'],
                'foreignModel' => null,
                'foreignField' => 'Size'
            ],
            'content' => [
                'readOnly' => false,
                'dataType' => ['string'],
                'foreignModel' => '\jamesiarmes\PhpEws\Type\FileAttachmentType',
                'foreignField' => 'Content'
            ],
            'isContactPhoto' => [
                'readOnly' => false,
                'dataType' => ['boolean'],
                'foreignModel' => '\jamesiarmes\PhpEws\Type\FileAttachmentType',
                'foreignField' => 'IsContactPhoto'
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
        $file = b'This is a text file';
        $attachment = new Attachment([
            'name' => 'test',
            'content' => $file,
            'mime' => 'text/plain',
            'isInline' => false,
            'size' => strlen($file)
        ]);

        $this->assertTrue($attachment->validate());
    }
}
