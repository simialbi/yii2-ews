<?php
/**
 * @package yii2-ews
 * @author Simon Karlen <simi.albi@gmail.com>
 */

namespace yiiunit\extensions\ews;

use jamesiarmes\PhpEws\Enumeration\BodyTypeType;
use jamesiarmes\PhpEws\Enumeration\DefaultShapeNamesType;
use jamesiarmes\PhpEws\Enumeration\DisposalType;
use jamesiarmes\PhpEws\Enumeration\DistinguishedFolderIdNameType;
use jamesiarmes\PhpEws\Enumeration\LegacyFreeBusyType;
use jamesiarmes\PhpEws\Enumeration\SortDirectionType;
use jamesiarmes\PhpEws\Enumeration\UnindexedFieldURIType;
use simialbi\yii2\ews\models\Attachment;
use simialbi\yii2\ews\models\Attendee;
use simialbi\yii2\ews\models\CalendarEvent;
use Yii;
use yii\base\InvalidConfigException;
use yii\base\NotSupportedException;

class CalendarEventTest extends TestCase
{
    /**
     * @throws \ReflectionException
     */
    public function testAttributeMapping()
    {
        $attributeMapping = CalendarEvent::attributeMapping();
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
            'start' => [
                'readOnly' => false,
                'dataType' => ['string', '\DateTime', 'integer'],
                'foreignModel' => null,
                'foreignField' => 'Start'
            ],
            'end' => [
                'readOnly' => false,
                'dataType' => ['string', '\DateTime', 'integer'],
                'foreignModel' => null,
                'foreignField' => 'End'
            ],
            'subject' => [
                'readOnly' => false,
                'dataType' => ['string'],
                'foreignModel' => null,
                'foreignField' => 'Subject'
            ],
            'body' => [
                'readOnly' => false,
                'dataType' => ['string'],
                'foreignModel' => '\jamesiarmes\PhpEws\Type\BodyType',
                'foreignField' => 'Body._'
            ],
            'format' => [
                'readOnly' => false,
                'dataType' => ['string'],
                'foreignModel' => '\jamesiarmes\PhpEws\Type\BodyType',
                'foreignField' => 'Body.BodyType'
            ],
            'location' => [
                'readOnly' => false,
                'dataType' => ['string'],
                'foreignModel' => null,
                'foreignField' => 'Location'
            ],
            'type' => [
                'readOnly' => false,
                'dataType' => ['string'],
                'foreignModel' => null,
                'foreignField' => 'CalendarItemType'
            ],
            'isRecurring' => [
                'readOnly' => true,
                'dataType' => ['boolean'],
                'foreignModel' => null,
                'foreignField' => 'IsRecurring'
            ],
            'isAllDay' => [
                'readOnly' => false,
                'dataType' => ['boolean'],
                'foreignModel' => null,
                'foreignField' => 'IsAllDayEvent'
            ],
            'isCancelled' => [
                'readOnly' => false,
                'dataType' => ['boolean'],
                'foreignModel' => null,
                'foreignField' => 'IsCancelled'
            ],
            'isOnline' => [
                'readOnly' => false,
                'dataType' => ['boolean'],
                'foreignModel' => null,
                'foreignField' => 'IsOnlineMeeting'
            ],
            'status' => [
                'readOnly' => false,
                'dataType' => ['string'],
                'foreignModel' => null,
                'foreignField' => 'LegacyFreeBusyStatus'
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
            'recurrence' => [
                'readOnly' => false,
                'dataType' => ['string', '\Recurr\Rule'],
                'foreignModel' => '\jamesiarmes\PhpEws\Type\RecurrenceType',
                'foreignField' => 'Recurrence'
            ],
            'organizer' => [
                'readOnly' => false,
                'dataType' => ['Contact'],
                'foreignModel' => '\jamesiarmes\PhpEws\Type\SingleRecipientType',
                'foreignField' => 'Organizer'
            ],
            'requiredAttendees' => [
                'readOnly' => false,
                'dataType' => ['Attendee[]'],
                'foreignModel' => '\jamesiarmes\PhpEws\ArrayType\NonEmptyArrayOfAttendeesType',
                'foreignField' => 'RequiredAttendees.Attendee'
            ],
            'optionalAttendees' => [
                'readOnly' => false,
                'dataType' => ['Attendee[]'],
                'foreignModel' => '\jamesiarmes\PhpEws\ArrayType\NonEmptyArrayOfAttendeesType',
                'foreignField' => 'OptionalAttendees.Attendee'
            ],
            'attachments' => [
                'readOnly' => false,
                'dataType' => ['Attachment[]'],
                'foreignModel' => '\jamesiarmes\PhpEws\ArrayType\ArrayOfAttachmentsType',
                'foreignField' => 'Attachments.FileAttachment'
            ]
        ];

        foreach ($attributeMapping as $key => $value) {
            $this->assertArrayHasKey($key, $expectedSubset);
        }

        foreach ($expectedSubset as $key => $value) {
            $this->assertArrayHasKey($key, $attributeMapping);
            $this->assertSame($value, $attributeMapping[$key]);
        }
    }

    /**
     * @throws InvalidConfigException
     */
    public function testQueryBuilderFind()
    {
        $query = CalendarEvent::find();
        $this->assertInstanceOf('simialbi\yii2\ews\ActiveQuery', $query);

        $startDate = date('c', strtotime('-2 weeks'));
        $endDate = date('c', strtotime('+2 weeks'));
        $query->from(['mailbox' => 'john.doe@example.com']);
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
        $this->assertIsArray($request->ParentFolderIds->DistinguishedFolderId);
        $this->assertCount(1, $request->ParentFolderIds->DistinguishedFolderId);
        $this->assertInstanceOf('jamesiarmes\PhpEws\Type\DistinguishedFolderIdType', $request->ParentFolderIds->DistinguishedFolderId[0]);
        $this->assertEquals(DistinguishedFolderIdNameType::CALENDAR, $request->ParentFolderIds->DistinguishedFolderId[0]->Id);
        $this->assertInstanceOf('jamesiarmes\PhpEws\Type\EmailAddressType', $request->ParentFolderIds->DistinguishedFolderId[0]->Mailbox);
        $this->assertEquals('john.doe@example.com', $request->ParentFolderIds->DistinguishedFolderId[0]->Mailbox->EmailAddress);
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

    /**
     * @throws NotSupportedException
     * @throws InvalidConfigException
     * @throws \ReflectionException
     */
    public function testQueryBuilderInsert()
    {
        $startDate = Yii::$app->formatter->asDate('+2 hours', 'yyyy-MM-dd HH:mm xxx');
        $endDate = Yii::$app->formatter->asDate('+2.5 hours', 'yyyy-MM-dd HH:mm xxx');
        $event = new CalendarEvent();

        $event->subject = 'Test';
        $event->body = '<p>This is a test</p>';
        $event->format = BodyTypeType::HTML;
        $event->start = $startDate;
        $event->end = $endDate;
        $event->requiredAttendees = [
            new Attendee(['name' => 'John Doe', 'email' => 'john.doe@example.com']),
            new Attendee(['name' => 'Jane Doe', 'email' => 'jane.doe@example.com'])
        ];
        $event->recurrence = 'FREQ=YEARLY;INTERVAL=1;BYMONTHDAY=20;BYMONTH=11';

        $file = b'This is a text file';
        $attachments = [
            new Attachment([
                'name' => 'test',
                'content' => $file,
                'mime' => 'text/plain',
                'isInline' => false,
                'size' => strlen($file)
            ])
        ];
        $event->attachments = $attachments;

        $event->beforeSave(true);

        $this->assertTrue($event->validate());

        $values = $event->getDirtyAttributes();
        $params = [
            'mailbox' => 'test@example.com'
        ];
        /** @var \jamesiarmes\PhpEws\Request\CreateItemType $request */
        $request = $event::getDb()->getQueryBuilder()->insert(get_class($event), $values, $params);

        $this->assertInstanceOf('jamesiarmes\PhpEws\Request\CreateItemType', $request);
        $this->assertInstanceOf('jamesiarmes\PhpEws\Type\TargetFolderIdType', $request->SavedItemFolderId);
        $this->assertInstanceOf('jamesiarmes\PhpEws\Type\DistinguishedFolderIdType', $request->SavedItemFolderId->DistinguishedFolderId);
        $this->assertEquals('calendar', $request->SavedItemFolderId->DistinguishedFolderId->Id);
        $this->assertInstanceOf('jamesiarmes\PhpEws\Type\EmailAddressType', $request->SavedItemFolderId->DistinguishedFolderId->Mailbox);
        $this->assertEquals('test@example.com', $request->SavedItemFolderId->DistinguishedFolderId->Mailbox->EmailAddress);
        $this->assertInstanceOf('jamesiarmes\PhpEws\ArrayType\NonEmptyArrayOfAllItemsType', $request->Items);
        $calendarItem = $request->Items->CalendarItem[0];
        $this->assertInstanceOf('jamesiarmes\PhpEws\Type\CalendarItemType', $calendarItem);
//        $this->assertEquals('Single', $calendarItem->CalendarItemType);
        $this->assertEquals(date('c', strtotime($startDate)), $calendarItem->Start);
        $this->assertEquals(date('c', strtotime($endDate)), $calendarItem->End);
        $this->assertEquals(false, $calendarItem->IsAllDayEvent);
        $this->assertEquals(false, $calendarItem->IsOnlineMeeting);
        $this->assertEquals(false, $calendarItem->IsRecurring);
        $this->assertEquals('Busy', $calendarItem->LegacyFreeBusyStatus);
        $this->assertInstanceOf('jamesiarmes\PhpEws\Type\BodyType', $calendarItem->Body);
        $this->assertEquals('HTML', $calendarItem->Body->BodyType);
        $this->assertEquals('<p>This is a test</p>', $calendarItem->Body->_);
        $this->assertEquals('Test', $calendarItem->Subject);
        $this->assertInstanceOf('jamesiarmes\PhpEws\ArrayType\NonEmptyArrayOfAttendeesType', $calendarItem->RequiredAttendees);

        // Attendee
        $attendee = $calendarItem->RequiredAttendees->Attendee[0];
        $this->assertInstanceOf('jamesiarmes\PhpEws\Type\AttendeeType', $attendee);
        $this->assertInstanceOf('jamesiarmes\PhpEws\Type\EmailAddressType', $attendee->Mailbox);
        $this->assertEquals('john.doe@example.com', $attendee->Mailbox->EmailAddress);
        $this->assertEquals('John Doe', $attendee->Mailbox->Name);

        // Recurrence
        $this->assertInstanceOf('jamesiarmes\PhpEws\Type\RecurrenceType', $calendarItem->Recurrence);
        $this->assertIsObject($calendarItem->Recurrence);
        $this->assertIsObject($calendarItem->Recurrence->NoEndRecurrence);

        // Attachments
        $attachment = $calendarItem->Attachments->FileAttachment[0];
        $this->assertObjectHasProperty('Attachments', $calendarItem);
        $this->assertInstanceOf('jamesiarmes\PhpEws\ArrayType\ArrayOfAttachmentsType', $calendarItem->Attachments);
        $this->assertObjectHasProperty('FileAttachment', $calendarItem->Attachments);
        $this->assertInstanceOf('jamesiarmes\PhpEws\Type\AttachmentType', $attachment);
        $this->assertEquals('text/plain', $attachment->ContentType);
        $this->assertEquals(19, $attachment->Size);
    }

    public function testQueryBuilderUpdate()
    {
        $startDate = Yii::$app->formatter->asDate('+2 hours', 'yyyy-MM-dd HH:mm xxx');
        $endDate = Yii::$app->formatter->asDate('+2.5 hours', 'yyyy-MM-dd HH:mm xxx');
        $event = new CalendarEvent();

        $event->subject = 'Test';
        $event->body = '<p>This is a test</p>';
        $event->format = 'HTML';
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
        $request = $event::getDb()->getQueryBuilder()->update(get_class($event), $values, [], $params);
        $this->assertEquals(false, $request);

        $request = $event::getDb()->getQueryBuilder()->update(get_class($event), $values, [
            'id' => 'AAajslgkha32394isdg==',
            'changeKey' => '7007ACC7-3202-11D1-AAD2-00805FC1270E'
        ], $params);

        $this->assertInstanceOf('jamesiarmes\PhpEws\Request\UpdateItemType', $request);
        $this->assertInstanceOf('jamesiarmes\PhpEws\ArrayType\NonEmptyArrayOfItemChangesType', $request->ItemChanges);
        $this->assertIsArray($request->ItemChanges->ItemChange);
        $this->assertCount(1, $request->ItemChanges->ItemChange);
        $this->assertInstanceOf('jamesiarmes\PhpEws\Type\ItemChangeType', $request->ItemChanges->ItemChange[0]);
        $this->assertInstanceOf('jamesiarmes\PhpEws\Type\ItemIdType', $request->ItemChanges->ItemChange[0]->ItemId);
        $this->assertEquals('AAajslgkha32394isdg==', $request->ItemChanges->ItemChange[0]->ItemId->Id);
        $this->assertEquals('7007ACC7-3202-11D1-AAD2-00805FC1270E', $request->ItemChanges->ItemChange[0]->ItemId->ChangeKey);
        $updates = $request->ItemChanges->ItemChange[0]->Updates;
        $this->assertInstanceOf('jamesiarmes\PhpEws\ArrayType\NonEmptyArrayOfItemChangeDescriptionsType', $updates);
        $this->assertIsArray($updates->SetItemField);
        $this->assertCount(5, $updates->SetItemField);
        foreach ($updates->SetItemField as $update) {
            $this->assertInstanceOf('jamesiarmes\PhpEws\Type\SetItemFieldType', $update);
            $this->assertInstanceOf('jamesiarmes\PhpEws\Type\PathToUnindexedFieldType', $update->FieldURI);
            $this->assertInstanceOf('jamesiarmes\PhpEws\Type\CalendarItemType', $update->CalendarItem);
        }
        $this->assertEquals(UnindexedFieldURIType::ITEM_SUBJECT, $updates->SetItemField[0]->FieldURI->FieldURI);
        $this->assertEquals($event->subject, $updates->SetItemField[0]->CalendarItem->Subject);
        $this->assertEquals(UnindexedFieldURIType::ITEM_BODY, $updates->SetItemField[1]->FieldURI->FieldURI);
        $this->assertEquals($event->body, $updates->SetItemField[1]->CalendarItem->Body->_);
        $this->assertEquals(UnindexedFieldURIType::CALENDAR_START, $updates->SetItemField[2]->FieldURI->FieldURI);
        $this->assertEquals(date('c', strtotime($startDate)), $updates->SetItemField[2]->CalendarItem->Start);
        $this->assertEquals(UnindexedFieldURIType::CALENDAR_END, $updates->SetItemField[3]->FieldURI->FieldURI);
        $this->assertEquals(date('c', strtotime($endDate)), $updates->SetItemField[3]->CalendarItem->End);
        $this->assertEquals(UnindexedFieldURIType::CALENDAR_LEGACY_FREE_BUSY_STATUS, $updates->SetItemField[4]->FieldURI->FieldURI);
        $this->assertEquals(LegacyFreeBusyType::BUSY, $updates->SetItemField[4]->CalendarItem->LegacyFreeBusyStatus);
        $this->assertObjectHasProperty('SendMeetingInvitationsOrCancellations', $request);
    }

    public function testQueryBuilderDelete()
    {
        $event = new CalendarEvent();
        $params = [];
        $request = $event::getDb()->getQueryBuilder()->delete(get_class($event), [
            'id' => 'AAajslgkha32394isdg==',
            'changeKey' => '7007ACC7-3202-11D1-AAD2-00805FC1270E'
        ], $params);

        $this->assertInstanceOf('jamesiarmes\PhpEws\Request\DeleteItemType', $request);
        $this->assertEquals(DisposalType::MOVE_TO_DELETED_ITEMS, $request->DeleteType);
        $this->assertInstanceOf('jamesiarmes\PhpEws\ArrayType\NonEmptyArrayOfBaseItemIdsType', $request->ItemIds);
        $this->assertIsArray($request->ItemIds->ItemId);
        $this->assertInstanceOf('jamesiarmes\PhpEws\Type\ItemIdType', $request->ItemIds->ItemId[0]);
        $this->assertEquals('7007ACC7-3202-11D1-AAD2-00805FC1270E', $request->ItemIds->ItemId[0]->ChangeKey);
        $this->assertEquals('AAajslgkha32394isdg==', $request->ItemIds->ItemId[0]->Id);
        $this->assertObjectHasProperty('SendMeetingCancellations', $request);
    }
}
