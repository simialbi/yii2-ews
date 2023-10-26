<?php

namespace yiiunit\extensions\ews;

use simialbi\yii2\ews\models\Folder;

class FolderTest extends TestCase
{
    /**
     * @throws \ReflectionException
     */
    public function testAttributeMapping()
    {
        $attributeMapping = Folder::attributeMapping();
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
            'parentFolderChangeKey' => [
                'readOnly' => false,
                'dataType' => ['string'],
                'foreignModel' => '\jamesiarmes\PhpEws\Type\FolderIdType',
                'foreignField' => 'ParentFolderId.ChangeKey'
            ],
            'name' => [
                'readOnly' => false,
                'dataType' => ['string'],
                'foreignModel' => null,
                'foreignField' => 'DisplayName'
            ],
            'unreadCount' => [
                'readOnly' => false,
                'dataType' => ['integer'],
                'foreignModel' => null,
                'foreignField' => 'UnreadCount'
            ],
            'totalCount' => [
                'readOnly' => false,
                'dataType' => ['integer'],
                'foreignModel' => null,
                'foreignField' => 'TotalCount'
            ],
            'childrenCount' => [
                'readOnly' => false,
                'dataType' => ['integer'],
                'foreignModel' => null,
                'foreignField' => 'ChildFolderCount'
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
        $folder = new Folder([
            'name' => 'Test Folder',
            'totalCount' => 3
        ]);
        $this->assertTrue($folder->validate());
    }
}
