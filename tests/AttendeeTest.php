<?php

namespace yiiunit\extensions\ews;

use simialbi\yii2\ews\models\Attendee;

class AttendeeTest extends TestCase
{
    /**
     * @throws \ReflectionException
     */
    public function testAttributeMapping()
    {
        $attributeMapping = Attendee::attributeMapping();
        $expectedSubset = [
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
        $attendee = new Attendee([
            'name' => 'John Doe',
            'email' => 'john.doe@example.com'
        ]);
        $this->assertTrue(true, $attendee->validate());
        $this->assertEquals('jamesiarmes\PhpEws\Type\AttendeeType', Attendee::modelName());
    }
}
