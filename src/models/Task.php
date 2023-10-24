<?php

namespace simialbi\yii2\ews\models;

use jamesiarmes\PhpEws\Enumeration\BodyTypeType;
use jamesiarmes\PhpEws\Enumeration\SensitivityChoicesType;
use jamesiarmes\PhpEws\Enumeration\TaskDelegateStateType;
use jamesiarmes\PhpEws\Enumeration\TaskStatusType;
use jamesiarmes\PhpEws\Enumeration\ImportanceChoicesType;
use jamesiarmes\PhpEws\Type\RecurrenceType;
use jamesiarmes\PhpEws\Type\TaskType;
use Recurr\Rule;
use simialbi\yii2\ews\ActiveRecord;
use simialbi\yii2\ews\recurrence\transformers\ExchangeTransformer;
use yii\behaviors\AttributeTypecastBehavior;

/**
 * @property string $id => \jamesiarmes\PhpEws\Type\ItemIdType:ItemId.Id
 * @property string $changeKey => \jamesiarmes\PhpEws\Type\ItemIdType:ItemId.ChangeKey
 * @property string $parentFolderId => \jamesiarmes\PhpEws\Type\FolderIdType:ParentFolderId.Id
 * @property integer $actualWork => ActualWork
 * @property string $assignedTime => AssignedTime
 * @property string $billingInformation => BillingInformation
 * @property string $body => \jamesiarmes\PhpEws\Type\BodyType:Body._
 * @property integer $changeCount => ChangeCount
 * @property string|\DateTime|integer $completeDate => CompleteDate
 * @property-read string $delegationState => DelegationState
 * @property string|\DateTime|integer $dueDate => DueDate
 * @property string $format => \jamesiarmes\PhpEws\Type\BodyType:Body.BodyType
 * @property string $importance => Importance
 * @property-read integer $isAssignmentEditable => IsAssignmentEditable
 * @property boolean $isComplete => IsComplete
 * @property-read boolean $isRecurring => IsRecurring
 * @property string $mileage => Mileage
 * @property-read string $owner => Owner
 * @property float $percentComplete => PercentComplete
 * @property string|\Recurr\Rule $recurrence => \jamesiarmes\PhpEws\Type\TaskRecurrenceType:Recurrence
 * @property string $sensitivity => Sensitivity
 * @property string|\DateTime|integer $startDate => StartDate
 * @property string $status => Status
 * @property-read string $statusDescription => StatusDescription
 * @property string $subject => Subject
 * @property integer $totalWork => TotalWork
 * @property-read string|\DateTime|integer $createdAt => DateTimeCreated
 * @property-read string|\DateTime|integer $updatedAt => LastModifiedTime
 *
 * @property Attachment[] $attachments => \jamesiarmes\PhpEws\ArrayType\ArrayOfAttachmentsType:Attachments.FileAttachment
 */
class Task extends ActiveRecord
{
    /**
     * {@inheritDoc}
     */
    public static function modelName(): string
    {
        return TaskType::class;
    }

    /**
     * {@inheritDoc}
     */
    public function rules(): array
    {
        return [
            [
                [
                    'id',
                    'assignedTime',
                    'billingInformation',
                    'body',
                    'changeKey',
                    'delegationState',
                    'format',
                    'importance',
                    'mileage',
                    'owner',
                    'parentFolderId',
                    'statusDescription',
                    'sensitivity',
                    'status',
                    'subject'
                ],
                'string'
            ],

            [['actualWork', 'changeCount', 'isAssignmentEditable', 'totalWork'], 'integer'],

            [['percentComplete'], 'double'],

            [['startDate'], 'date', 'format' => 'yyyy-MM-dd HH:mm xxx', 'timestampAttribute' => 'startDate'],
            [['dueDate'], 'date', 'format' => 'yyyy-MM-dd HH:mm xxx', 'timestampAttribute' => 'dueDate'],
            [['completeDate'], 'date', 'format' => 'yyyy-MM-dd HH:mm xxx', 'timestampAttribute' => 'completeDate'],

            [['isComplete', 'isRecurring'], 'boolean'],

            [['subject', 'format'], 'required'],

            ['format', 'in', 'range' => [
                BodyTypeType::HTML,
                BodyTypeType::TEXT
            ]],
            [
                'delegationState',
                'in',
                'range' => [
                    TaskDelegateStateType::ACCEPTED,
                    TaskDelegateStateType::DECLINED,
                    TaskDelegateStateType::MAX,
                    TaskDelegateStateType::NO_MATCH,
                    TaskDelegateStateType::OWN_NEW,
                    TaskDelegateStateType::OWNED
                ]
            ],
            [
                'status',
                'in',
                'range' => [
                    TaskStatusType::COMPLETED,
                    TaskStatusType::DEFERRED,
                    TaskStatusType::IN_PROGRESS,
                    TaskStatusType::NOT_STARTED,
                    TaskStatusType::WAITING_ON_OTHERS
                ]
            ],
            [
                'importance',
                'in',
                'range' => [
                    ImportanceChoicesType::HIGH,
                    ImportanceChoicesType::LOW,
                    ImportanceChoicesType::NORMAL
                ]
            ],
            [
                'sensitivity',
                'in',
                'range' => [
                    SensitivityChoicesType::CONFIDENTIAL,
                    SensitivityChoicesType::NORMAL,
                    SensitivityChoicesType::PERSONAL,
                    SensitivityChoicesType::PRIVATE_ITEM
                ]
            ],

            ['recurrence', 'safe'],

            ['format', 'default', 'value' => BodyTypeType::HTML]
        ];
    }

    /**
     * {@inheritDoc}
     */
    public function beforeSave($insert): bool
    {
        if ($this->isAttributeChanged('body')) {
            $this->markAttributeDirty('format');
        }

        return parent::beforeSave($insert);
    }

    /**
     * {@inheritDoc}
     */
    public function behaviors(): array
    {
        return [
            'typecast' => [
                'class' => AttributeTypecastBehavior::class,
                'typecastAfterSave' => false,
                'typecastAfterValidate' => false,
                'typecastBeforeSave' => false,
                'typecastAfterFind' => true,
                'attributeTypes' => [
                    'assignedTime' => [$this, 'typeCastDateTime'],
                    'completeDate' => [$this, 'typeCastDateTime'],
                    'dueDate' => [$this, 'typeCastDateTime'],
                    'startDate' => [$this, 'typeCastDateTime'],
                    'createdAt' => [$this, 'typeCastDateTime'],
                    'updatedAt' => [$this, 'typeCastDateTime'],
                    'recurrence' => function (string|RecurrenceType|null|array $value): ?Rule {
                        if (empty($value)) {
                            return null;
                        }

                        $transformer = new ExchangeTransformer();
                        return $transformer->transformRecurrenceFromEws($value);
                    }
                ]
            ]
        ];
    }
}
