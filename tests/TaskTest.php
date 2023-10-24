<?php

namespace yiiunit\extensions\ews;

use jamesiarmes\PhpEws\Enumeration\BodyTypeType;
use jamesiarmes\PhpEws\Enumeration\TaskStatusType;
use simialbi\yii2\ews\models\Task;

class TaskTest extends TestCase
{
    /**
     * @throws \ReflectionException
     */
    public function testAttributeMapping()
    {
        $attributeMapping = Task::attributeMapping();
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
            'actualWork' => [
                'readOnly' => false,
                'dataType' => ['integer'],
                'foreignModel' => null,
                'foreignField' => 'ActualWork'
            ],
            'assignedTime' => [
                'readOnly' => false,
                'dataType' => ['string'],
                'foreignModel' => null,
                'foreignField' => 'AssignedTime'
            ],
            'billingInformation' => [
                'readOnly' => false,
                'dataType' => ['string'],
                'foreignModel' => null,
                'foreignField' => 'BillingInformation'
            ],
            'changeCount' => [
                'readOnly' => false,
                'dataType' => ['integer'],
                'foreignModel' => null,
                'foreignField' => 'ChangeCount'
            ],
            'completeDate' => [
                'readOnly' => false,
                'dataType' => ['string', '\DateTime', 'integer'],
                'foreignModel' => null,
                'foreignField' => 'CompleteDate'
            ],
            'delegationState' => [
                'readOnly' => true,
                'dataType' => ['string'],
                'foreignModel' => null,
                'foreignField' => 'DelegationState'
            ],
            'dueDate' => [
                'readOnly' => false,
                'dataType' => ['string', '\DateTime', 'integer'],
                'foreignModel' => null,
                'foreignField' => 'DueDate'
            ],
            'isAssignmentEditable' => [
                'readOnly' => true,
                'dataType' => ['integer'],
                'foreignModel' => null,
                'foreignField' => 'IsAssignmentEditable'
            ],
            'isComplete' => [
                'readOnly' => false,
                'dataType' => ['boolean'],
                'foreignModel' => null,
                'foreignField' => 'IsComplete'
            ],
            'isRecurring' => [
                'readOnly' => true,
                'dataType' => ['boolean'],
                'foreignModel' => null,
                'foreignField' => 'IsRecurring'
            ],
            'mileage' => [
                'readOnly' => false,
                'dataType' => ['string'],
                'foreignModel' => null,
                'foreignField' => 'Mileage'
            ],
            'owner' => [
                'readOnly' => true,
                'dataType' => ['string'],
                'foreignModel' => null,
                'foreignField' => 'Owner'
            ],
            'percentComplete' => [
                'readOnly' => false,
                'dataType' => ['float'],
                'foreignModel' => null,
                'foreignField' => 'PercentComplete'
            ],
            'recurrence' => [
                'readOnly' => false,
                'dataType' => ['string', '\Recurr\Rule'],
                'foreignModel' => '\jamesiarmes\PhpEws\Type\TaskRecurrenceType',
                'foreignField' => 'Recurrence'
            ],
            'startDate' => [
                'readOnly' => false,
                'dataType' => ['string', '\DateTime', 'integer'],
                'foreignModel' => null,
                'foreignField' => 'StartDate'
            ],
            'status' => [
                'readOnly' => false,
                'dataType' => ['string'],
                'foreignModel' => null,
                'foreignField' => 'Status'
            ],
            'statusDescription' => [
                'readOnly' => true,
                'dataType' => ['string'],
                'foreignModel' => null,
                'foreignField' => 'StatusDescription'
            ],
            'totalWork' => [
                'readOnly' => false,
                'dataType' => ['integer'],
                'foreignModel' => null,
                'foreignField' => 'TotalWork'
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
        $startDate = \Yii::$app->formatter->asDate('+2 hours', 'yyyy-MM-dd HH:mm xxx');
        $endDate = \Yii::$app->formatter->asDate('+2.5 hours', 'yyyy-MM-dd HH:mm xxx');

        $task = new Task([
            'body' => 'Test',
            'subject' => 'Test',
            'startDate' => $startDate,
            'dueDate' => $endDate,
            'percentComplete' => 0,
            'format' => BodyTypeType::HTML,
            'status' => TaskStatusType::NOT_STARTED
        ]);
        $this->assertTrue($task->validate());
    }

    public function testDelete()
    {
        $event = new Task();
        $params = [];
        $request = $event::getDb()->getQueryBuilder()->delete(get_class($event), [
            'id' => 'AAajslgkha32394isdg==',
            'changeKey' => '7007ACC7-3202-11D1-AAD2-00805FC1270E'
        ], $params);

        $this->assertObjectHasProperty('AffectedTaskOccurrences', $request);
    }
}
