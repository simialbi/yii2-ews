<?php
/**
 * @package yii2-ews
 * @author Simon Karlen <simi.albi@gmail.com>
 */

namespace yiiunit\extensions\ews;

use jamesiarmes\PhpEws\Enumeration\DefaultShapeNamesType;
use jamesiarmes\PhpEws\Enumeration\DistinguishedFolderIdNameType;
use jamesiarmes\PhpEws\Enumeration\SortDirectionType;
use simialbi\yii2\ews\models\Attendee;
use simialbi\yii2\ews\models\CalendarEvent;
use Yii;

class ActiveRecordTest extends TestCase
{
    public function testAttributeMapping()
    {
        $attributeMapping = CalendarEvent::attributeMapping();
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
            'parentFolderChangeKey' => [
                'dataType' => ['string'],
                'foreignModel' => '\jamesiarmes\PhpEws\Type\FolderIdType',
                'foreignField' => 'ParentFolderId.ChangeKey'
            ],
            'subject' => [
                'dataType' => ['string'],
                'foreignModel' => null,
                'foreignField' => 'Subject'
            ],
            'body' => [
                'dataType' => ['string'],
                'foreignModel' => '\jamesiarmes\PhpEws\Type\BodyType',
                'foreignField' => 'Body._'
            ],
            'type' => [
                'dataType' => ['string'],
                'foreignModel' => null,
                'foreignField' => 'CalendarItemType'
            ],
            'isRecurring' => [
                'dataType' => ['boolean'],
                'foreignModel' => null,
                'foreignField' => 'IsRecurring'
            ],
            'isAllDay' => [
                'dataType' => ['boolean'],
                'foreignModel' => null,
                'foreignField' => 'IsAllDayEvent'
            ],
            'isCancelled' => [
                'dataType' => ['boolean'],
                'foreignModel' => null,
                'foreignField' => 'IsCancelled'
            ],
            'isOnline' => [
                'dataType' => ['boolean'],
                'foreignModel' => null,
                'foreignField' => 'IsOnlineMeeting'
            ],
            'status' => [
                'dataType' => ['string'],
                'foreignModel' => null,
                'foreignField' => 'LegacyFreeBusyStatus'
            ]
        ], $attributeMapping);
    }

    public function testQueryBuilderFind()
    {
        $query = CalendarEvent::find();
        $this->assertInstanceOf('simialbi\yii2\ews\ActiveQuery', $query);

        $startDate = date('c', strtotime('-2 weeks'));
        $endDate = date('c', strtotime('+2 weeks'));
        $query->where(['>=', 'start', $startDate]);
        $query->andWhere(['<=', 'end', $endDate]);
        $query->orderBy(['start' => SORT_ASC]);
        $command = $query->createCommand();

        $this->assertInstanceOf('simialbi\yii2\ews\Command', $command);
        /** @var \jamesiarmes\PhpEws\Request\FindItemType $request */
        $request = $command->getRequest();
//        var_dump($request);
        $this->assertInstanceOf('jamesiarmes\PhpEws\Request\FindItemType', $request);
        $this->assertInstanceOf('jamesiarmes\PhpEws\Type\ItemResponseShapeType', $request->ItemShape);
        $this->assertEquals(true, $request->ItemShape->ConvertHtmlCodePageToUTF8);
        $this->assertEquals(DefaultShapeNamesType::ALL_PROPERTIES, $request->ItemShape->BaseShape);
        $this->assertInstanceOf('jamesiarmes\PhpEws\ArrayType\NonEmptyArrayOfBaseFolderIdsType', $request->ParentFolderIds);
        $this->assertInstanceOf('jamesiarmes\PhpEws\Type\DistinguishedFolderIdType', $request->ParentFolderIds->DistinguishedFolderId);
        $this->assertEquals(DistinguishedFolderIdNameType::CALENDAR, $request->ParentFolderIds->DistinguishedFolderId->Id);
        $this->assertInstanceOf('jamesiarmes\PhpEws\Type\RestrictionType', $request->Restriction);
        $this->assertInstanceOf('jamesiarmes\PhpEws\Type\AndType', $request->Restriction->And);
        $this->assertInstanceOf('jamesiarmes\PhpEws\Type\IsGreaterThanOrEqualToType', $request->Restriction->And->IsGreaterThanOrEqualTo);
        $this->assertInstanceOf('jamesiarmes\PhpEws\Type\FieldURIOrConstantType', $request->Restriction->And->IsGreaterThanOrEqualTo->FieldURIOrConstant);
        $this->assertInstanceOf('jamesiarmes\PhpEws\Type\ConstantValueType', $request->Restriction->And->IsGreaterThanOrEqualTo->FieldURIOrConstant->Constant);
        $this->assertEquals($startDate, $request->Restriction->And->IsGreaterThanOrEqualTo->FieldURIOrConstant->Constant->Value);
        $this->assertInstanceOf('jamesiarmes\PhpEws\Type\PathToUnindexedFieldType', $request->Restriction->And->IsGreaterThanOrEqualTo->FieldURI);
        $this->assertEquals('calendar:Start', $request->Restriction->And->IsGreaterThanOrEqualTo->FieldURI->FieldURI);
        $this->assertInstanceOf('jamesiarmes\PhpEws\Type\IsLessThanOrEqualToType', $request->Restriction->And->IsLessThanOrEqualTo);
        $this->assertInstanceOf('jamesiarmes\PhpEws\Type\FieldURIOrConstantType', $request->Restriction->And->IsLessThanOrEqualTo->FieldURIOrConstant);
        $this->assertInstanceOf('jamesiarmes\PhpEws\Type\ConstantValueType', $request->Restriction->And->IsLessThanOrEqualTo->FieldURIOrConstant->Constant);
        $this->assertEquals($endDate, $request->Restriction->And->IsLessThanOrEqualTo->FieldURIOrConstant->Constant->Value);
        $this->assertInstanceOf('jamesiarmes\PhpEws\Type\PathToUnindexedFieldType', $request->Restriction->And->IsLessThanOrEqualTo->FieldURI);
        $this->assertEquals('calendar:End', $request->Restriction->And->IsLessThanOrEqualTo->FieldURI->FieldURI);
        $this->assertInstanceOf('jamesiarmes\PhpEws\ArrayType\NonEmptyArrayOfFieldOrdersType', $request->SortOrder);
        $this->assertInstanceOf('jamesiarmes\PhpEws\Type\FieldOrderType', $request->SortOrder->FieldOrder[0]);
        $this->assertInstanceOf('jamesiarmes\PhpEws\Type\PathToUnindexedFieldType', $request->SortOrder->FieldOrder[0]->FieldURI);
        $this->assertEquals('calendar:Start', $request->SortOrder->FieldOrder[0]->FieldURI->FieldURI);
        $this->assertEquals(SortDirectionType::ASCENDING, $request->SortOrder->FieldOrder[0]->Order);
    }

    public function testQueryBuilderInsert()
    {
        $startDate = Yii::$app->formatter->asDate('+2 hours', 'yyyy-MM-dd HH:mm');
        $endDate = Yii::$app->formatter->asDate('+2.5 hours', 'yyyy-MM-dd HH:mm');
        $event = new CalendarEvent();

        $event->subject = 'Test';
        $event->body = '<p>This is a test</p>';
        $event->start = $startDate;
        $event->end = $endDate;
        $event->requiredAttendees = [
            new Attendee(['name' => 'John Doe', 'email' => 'john.doe@example.com']),
            new Attendee(['name' => 'Jane Doe', 'email' => 'jane.doe@example.com'])
        ];

        $this->assertEquals(true, $event->validate());

        $values = $event->getDirtyAttributes();
        $params = [];
        /** @var \jamesiarmes\PhpEws\Request\CreateItemType $request */
        $request = $event::getDb()->getQueryBuilder()->insert(get_class($event), $values, $params);

        $this->assertInstanceOf('jamesiarmes\PhpEws\Request\CreateItemType', $request);
        $this->assertInstanceOf('jamesiarmes\PhpEws\ArrayType\NonEmptyArrayOfAllItemsType', $request->Items);
        $calendarItem = $request->Items->CalendarItem[0];
        $this->assertInstanceOf('jamesiarmes\PhpEws\Type\CalendarItemType', $calendarItem);
        $this->assertEquals('Single', $calendarItem->CalendarItemType);
        $this->assertEquals(date('c', strtotime($startDate)), $calendarItem->Start);
        $this->assertEquals(date('c', strtotime($endDate)), $calendarItem->End);
        $this->assertEquals(false, $calendarItem->IsAllDayEvent);
        $this->assertEquals(false, $calendarItem->IsOnlineMeeting);
        $this->assertEquals(false, $calendarItem->IsRecurring);
        $this->assertEquals('Busy', $calendarItem->LegacyFreeBusyStatus);
        $this->assertInstanceOf('jamesiarmes\PhpEws\Type\BodyType', $calendarItem->Body);
        $this->assertEquals('<p>This is a test</p>', $calendarItem->Body->_);
        $this->assertEquals('Test', $calendarItem->Subject);
        $this->assertInstanceOf('jamesiarmes\PhpEws\ArrayType\NonEmptyArrayOfAttendeesType', $calendarItem->RequiredAttendees);
        $attendee = $calendarItem->RequiredAttendees->Attendee[0];
        $this->assertInstanceOf('jamesiarmes\PhpEws\Type\AttendeeType', $attendee);
        $this->assertInstanceOf('jamesiarmes\PhpEws\Type\EmailAddressType', $attendee->Mailbox);
        $this->assertEquals('john.doe@example.com', $attendee->Mailbox->EmailAddress);
        $this->assertEquals('John Doe', $attendee->Mailbox->Name);
    }
}
