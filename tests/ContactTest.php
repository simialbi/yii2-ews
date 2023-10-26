<?php

namespace yiiunit\extensions\ews;

use jamesiarmes\PhpEws\Type\EmailAddressType;
use jamesiarmes\PhpEws\Type\SingleRecipientType;
use simialbi\yii2\ews\models\Contact;

class ContactTest extends TestCase
{
    /**
     * @throws \ReflectionException
     */
    public function testAttributeMapping()
    {
        $attributeMapping = Contact::attributeMapping();
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
                'foreignModel' => '\jamesiarmes\PhpEws\Type\EmailAddressType',
                'foreignField' => 'Mailbox.Name'
            ],
            'email' => [
                'readOnly' => false,
                'dataType' => ['string'],
                'foreignModel' => '\jamesiarmes\PhpEws\Type\EmailAddressType',
                'foreignField' => 'Mailbox.EmailAddress'
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
        $contact = new Contact([
            'name' => 'John Doe',
            'email' => 'john.doe@example.com'
        ]);
        $this->assertTrue($contact->validate());

        $recipient = new SingleRecipientType();
        $recipient->Mailbox = new EmailAddressType();
        $recipient->Mailbox->EmailAddress = 'john.doe@example.com';
        $recipient->Mailbox->Name = 'John Doe';
        $contact = Contact::fromSingleRecipient($recipient);
        self::assertTrue($contact->validate());
    }
}
