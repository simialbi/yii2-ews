<?php
/**
 * @package yii2-ews
 * @author Simon Karlen <simi.albi@gmail.com>
 */

namespace simialbi\yii2\ews;

use jamesiarmes\PhpEws\ArrayType\ArrayOfFoldersType;
use jamesiarmes\PhpEws\ArrayType\NonEmptyArrayOfAllItemsType;
use jamesiarmes\PhpEws\ArrayType\NonEmptyArrayOfBaseFolderIdsType;
use jamesiarmes\PhpEws\ArrayType\NonEmptyArrayOfBaseItemIdsType;
use jamesiarmes\PhpEws\ArrayType\NonEmptyArrayOfFieldOrdersType;
use jamesiarmes\PhpEws\ArrayType\NonEmptyArrayOfItemChangeDescriptionsType;
use jamesiarmes\PhpEws\ArrayType\NonEmptyArrayOfItemChangesType;
use jamesiarmes\PhpEws\Enumeration\CalendarItemCreateOrDeleteOperationType;
use jamesiarmes\PhpEws\Enumeration\DefaultShapeNamesType;
use jamesiarmes\PhpEws\Enumeration\DisposalType;
use jamesiarmes\PhpEws\Enumeration\DistinguishedFolderIdNameType;
use jamesiarmes\PhpEws\Enumeration\MessageDispositionType;
use jamesiarmes\PhpEws\Enumeration\SortDirectionType;
use jamesiarmes\PhpEws\Enumeration\UnindexedFieldURIType;
use jamesiarmes\PhpEws\Request\BaseRequestType;
use jamesiarmes\PhpEws\Request\CreateFolderType;
use jamesiarmes\PhpEws\Request\CreateItemType;
use jamesiarmes\PhpEws\Request\DeleteItemType;
use jamesiarmes\PhpEws\Request\FindFolderType;
use jamesiarmes\PhpEws\Request\FindItemType;
use jamesiarmes\PhpEws\Request\GetItemType;
use jamesiarmes\PhpEws\Request\UpdateItemType;
use jamesiarmes\PhpEws\Type\AggregateOnType;
use jamesiarmes\PhpEws\Type\CalendarItemType;
use jamesiarmes\PhpEws\Type\DistinguishedFolderIdType;
use jamesiarmes\PhpEws\Type\EmailAddressType;
use jamesiarmes\PhpEws\Type\FieldOrderType;
use jamesiarmes\PhpEws\Type\FolderIdType;
use jamesiarmes\PhpEws\Type\FolderResponseShapeType;
use jamesiarmes\PhpEws\Type\FolderType;
use jamesiarmes\PhpEws\Type\FractionalPageViewType;
use jamesiarmes\PhpEws\Type\GroupByType;
use jamesiarmes\PhpEws\Type\ItemChangeType;
use jamesiarmes\PhpEws\Type\ItemIdType;
use jamesiarmes\PhpEws\Type\ItemResponseShapeType;
use jamesiarmes\PhpEws\Type\MessageType;
use jamesiarmes\PhpEws\Type\PathToUnindexedFieldType;
use jamesiarmes\PhpEws\Type\RestrictionType;
use jamesiarmes\PhpEws\Type\SetItemFieldType;
use jamesiarmes\PhpEws\Type\TargetFolderIdType;
use jamesiarmes\PhpEws\Type\TaskType;
use simialbi\yii2\ews\models\CalendarEvent;
use simialbi\yii2\ews\models\Contact;
use simialbi\yii2\ews\models\Folder;
use simialbi\yii2\ews\models\Message;
use Yii;
use yii\base\NotSupportedException;
use yii\db\ExpressionInterface;
use yii\helpers\ArrayHelper;
use yii\helpers\StringHelper;

/**
 * Class QueryBuilder
 * @package simialbi\yii2\ews
 */
class QueryBuilder extends \yii\db\QueryBuilder
{
    /**
     * @var Connection the database connection.
     */
    public $db;

    /**
     * @var array config object to build request from
     */
    private $_config;

    /**
     * @var string
     */
    private $_modelClass;

    /**
     * QueryBuilder constructor.
     *
     * @param mixed $connection the database connection.
     * @param array $config name-value pairs that will be used to initialize the object properties
     */
    public function __construct($connection, array $config = [])
    {
        parent::__construct($connection, $config);
        $this->_config = [];
    }

    /**
     * Generates a ews Request instance from a Query object.
     *
     * @param Query $query the [[Query]] object from which the Request will be generated.
     * @param array $params the parameters to be injected to the generated request. These parameters will
     * be included in the result with the additional parameters generated during the query building process.
     *
     * @return array the generated Request (the first array element) and the corresponding parameters
     * injected to the request (the second array element).  The parameters returned
     * include those provided in `$params`.
     * @throws \yii\base\InvalidConfigException
     */
    public function build($query, $params = []): array
    {
        $this->_config = [
            'class' => FindItemType::class
        ];
        if (isset($query->where['id'], $query->where['changeKey'])) {
            $this->_config = [
                'class' => GetItemType::class,
                'ItemShape' => Yii::createObject([
                    'class' => ItemResponseShapeType::class,
                    'BaseShape' => DefaultShapeNamesType::ALL_PROPERTIES
                ]),
                'ItemIds' => Yii::createObject([
                    'class' => NonEmptyArrayOfBaseItemIdsType::class,
                    'ItemId' => [
                        Yii::createObject([
                            'class' => ItemIdType::class,
                            'Id' => $query->where['id'],
                            'ChangeKey' => $query->where['changeKey']
                        ])
                    ]
                ])
            ];
        } else {
            if (empty($params['folderId'])) {
                $params['folderId'] = DistinguishedFolderIdNameType::INBOX;
                if ($query instanceof ActiveQuery) {
                    /** @var ActiveQuery $query */
                    $this->_modelClass = $query->modelClass;

                    switch ($this->_modelClass) {
                        case Folder::class:
                            $this->_config['class'] = FindFolderType::class;
                            break;
                        case CalendarEvent::class:
                            $params['folderId'] = DistinguishedFolderIdNameType::CALENDAR;
                            break;
                        case Contact::class:
                            $params['folderId'] = DistinguishedFolderIdNameType::CONTACTS;
                            break;
                    }
                }
            }

            $this->_config = ArrayHelper::merge(
                $this->_config,
                $this->buildSelect($query->select, $params, $query->distinct, $query->selectOption),
                $this->buildFrom($query->from, $params),
                $this->buildWhere($query->where, $params),
                $this->buildOrderBy($query->orderBy),
                $this->buildLimit($query->limit, $query->offset),
                $this->buildGroupBy($query->groupBy)
            );
        }

        return [Yii::createObject($this->_config), $params];
    }

    /**
     * {@inheritDoc}
     *
     * @return array
     * @throws \yii\base\InvalidConfigException
     */
    public function buildSelect($columns, &$params, $distinct = false, $selectOption = null): array
    {
        $config = [];
        $found = false;
        if (!empty($selectOption)) {
            $r = new \ReflectionClass(DefaultShapeNamesType::class);
            foreach ($r->getConstants() as $constant) {
                if ($selectOption === $constant) {
                    $found = true;
                    break;
                }
            }
        }
        if (!$found) {
            $selectOption = DefaultShapeNamesType::ALL_PROPERTIES;
        }
        if ($this->_config['class'] === FindFolderType::class) {
            $config['FolderShape'] = Yii::createObject([
                'class' => FolderResponseShapeType::class,
                'BaseShape' => $selectOption
            ]);
        } else {
            $config['ItemShape'] = Yii::createObject([
                'class' => ItemResponseShapeType::class,
                'BaseShape' => $selectOption,
                'ConvertHtmlCodePageToUTF8' => true
            ]);
        }

        return $config;
    }

    /**
     * {@inheritDoc}
     *
     * @return array
     * @throws \yii\base\InvalidConfigException
     */
    public function buildFrom($tables, &$params): array
    {
        $config = [
            'class' => NonEmptyArrayOfBaseFolderIdsType::class
        ];
        $mailbox = ArrayHelper::remove($tables, 'mailbox');
        if (empty($tables)) {
            if ($mailbox) {
                if (!is_array($mailbox)) {
                    $mailbox = [$mailbox];
                }
                foreach ($mailbox as $mb) {
                    $config['DistinguishedFolderId'][] = Yii::createObject([
                        'class' => DistinguishedFolderIdType::class,
                        'Id' => $params['folderId'],
                        'Mailbox' => Yii::createObject([
                            'class' => EmailAddressType::class,
                            'EmailAddress' => $mb
                        ])
                    ]);
                }
            } else {
                $config['DistinguishedFolderId'][] = Yii::createObject([
                    'class' => DistinguishedFolderIdType::class,
                    'Id' => $params['folderId']
                ]);
            }
        } else {
            foreach ($tables as $from) {
                $config['FolderId'][] = Yii::createObject([
                    'class' => FolderIdType::class,
                    'Id' => $from
                ]);
            }
        }

        return ['ParentFolderIds' => Yii::createObject($config)];
    }

    /**
     * {@inheritDoc}
     * @return array
     * @throws \yii\base\InvalidConfigException
     */
    public function buildOrderBy($columns): array
    {
        if (empty($columns)) {
            return [];
        }
        $config = [
            'class' => NonEmptyArrayOfFieldOrdersType::class,
            'FieldOrder' => []
        ];
        foreach ($columns as $column => $direction) {
            if (null !== ($uri = $this->getUriFromProperty($column))) {
                $config['FieldOrder'][] = Yii::createObject([
                    'class' => FieldOrderType::class,
                    'FieldURI' => Yii::createObject([
                        'class' => PathToUnindexedFieldType::class,
                        'FieldURI' => $uri
                    ]),
                    'Order' => ($direction === SORT_ASC) ? SortDirectionType::ASCENDING : SortDirectionType::DESCENDING
                ]);
            }
        }

        return ['SortOrder' => Yii::createObject($config)];
    }

    /**
     * {@inheritDoc}
     *
     * @param string $table active record class name
     *
     * @return BaseRequestType|object
     * @throws NotSupportedException|\yii\base\InvalidConfigException
     */
    public function insert($table, $columns, &$params)
    {
        /** @var ActiveRecord $table */
        $config = [];

        $mailbox = ArrayHelper::remove($params, 'mailbox');
        if ($table::modelName() === FolderType::class) {
            $config['class'] = CreateFolderType::class;
            $config['Folders'] = Yii::createObject([
                'class' => ArrayOfFoldersType::class,
                'Folder' => []
            ]);
        } else {
            $config['class'] = CreateItemType::class;
            switch ($table::modelName()) {
                case CalendarItemType::class:
                    $config['SendMeetingInvitations'] = CalendarItemCreateOrDeleteOperationType::SEND_TO_ALL_AND_SAVE_COPY;
                    $config['SavedItemFolderId'] = Yii::createObject([
                        'class' => TargetFolderIdType::class,
                        'DistinguishedFolderId' => Yii::createObject([
                            'class' => DistinguishedFolderIdType::class,
                            'Id' => DistinguishedFolderIdNameType::CALENDAR
                        ])
                    ]);

                    if ($mailbox) {
                        $config['SavedItemFolderId']->DistinguishedFolderId->Mailbox = Yii::createObject([
                            'class' => EmailAddressType::class,
                            'EmailAddress' => $mailbox
                        ]);
                    }
                    break;
                case MessageType::class:
                    $config['MessageDisposition'] = MessageDispositionType::SEND_AND_SAVE_COPY;
                    break;
            }
            $property = substr(StringHelper::basename($table::modelName()), 0, -4);
            $config['Items'] = Yii::createObject([
                'class' => NonEmptyArrayOfAllItemsType::class,
                $property => $this->prepareInsertValues($table, $columns, $params)
            ]);
        }

        return Yii::createObject($config);
    }

    /**
     * {@inheritDoc}
     *
     * @param string $table active record class name
     *
     * @return BaseRequestType|object|false
     * @throws NotSupportedException|\yii\base\InvalidConfigException
     */
    public function update($table, $columns, $condition, &$params)
    {
        /** @var ActiveRecord $table */
        if (!isset($condition['id']) || !isset($condition['changeKey'])) {
            // TODO warning
            return false;
        }

        if ($table::modelName() === TaskType::class) {
            throw new NotSupportedException('Update is not supported on task type.');
//        } elseif ($table::modelName() === ContactItemType::class) {
            // TODO
        } else {
            $changes = $this->prepareUpdateSets($table, $columns, $params);
        }

        $config = [
            'class' => UpdateItemType::class,
            'ItemChanges' => Yii::createObject([
                'class' => NonEmptyArrayOfItemChangesType::class,
                'ItemChange' => [
                    Yii::createObject([
                        'class' => ItemChangeType::class,
                        'ItemId' => Yii::createObject([
                            'class' => ItemIdType::class,
                            'Id' => $condition['id'],
                            'ChangeKey' => $condition['changeKey']
                        ]),
                        'Updates' => Yii::createObject([
                            'class' => NonEmptyArrayOfItemChangeDescriptionsType::class,
                            'SetItemField' => $changes
                        ])
                    ])
                ]
            ])
        ];

        if ($table::modelName() === MessageType::class) {
            $config['MessageDisposition'] = MessageDispositionType::SAVE_ONLY;
        }

        return Yii::createObject($config);
    }

    /**
     * {@inheritDoc}
     *
     * @param string $table active record class name
     *
     * @return BaseRequestType|object|false
     * @throws \yii\base\InvalidConfigException
     */
    public function delete($table, $condition, &$params)
    {
        if (!isset($condition['id']) || !isset($condition['changeKey'])) {
            // TODO warning
            return false;
        }

        $config = [
            'class' => DeleteItemType::class,
            'DeleteType' => DisposalType::MOVE_TO_DELETED_ITEMS,
            'ItemIds' => Yii::createObject([
                'class' => NonEmptyArrayOfBaseItemIdsType::class,
                'ItemId' => [
                    Yii::createObject([
                        'class' => ItemIdType::class,
                        'Id' => $condition['id'],
                        'ChangeKey' => $condition['changeKey']
                    ])
                ]
            ])
        ];

        return Yii::createObject($config);
    }

    /**
     * {@inheritDoc}
     * @return array
     * @throws \yii\base\InvalidConfigException
     */
    public function buildWhere($condition, &$params): array
    {
        $config = [
            'class' => RestrictionType::class
        ];
        $conditions = $this->buildCondition($condition, $params);
        if (is_object($conditions)) {
            $conditions = [$conditions];
        }
        $r = new \ReflectionClass(RestrictionType::class);
        foreach ($conditions as $condition) {
            $property = substr(StringHelper::basename(get_class($condition)), 0, -4);
            if ($r->hasProperty($property)) {
                $config[$property] = $condition;
            }
        }

        return empty($conditions) ? [] : ['Restriction' => Yii::createObject($config)];
    }

    /**
     * {@inheritDoc}
     *
     * @return array
     * @throws \yii\base\InvalidConfigException
     */
    public function buildGroupBy($columns): array
    {
        if (empty($columns)) {
            return [];
        }

        $config = [];
        foreach ($columns as $column) {
            if (null !== ($uri = $this->getUriFromProperty($column))) {
                $config['FieldURI'] = Yii::createObject([
                    'class' => PathToUnindexedFieldType::class,
                    'FieldURI' => $uri
                ]);
                $config['AggregateOn'] = Yii::createObject([
                    'class' => AggregateOnType::class,
                    'FieldURI' => Yii::createObject([
                        'class' => PathToUnindexedFieldType::class,
                        'FieldURI' => $uri
                    ])
                ]);
                break;
            }
        }

        if (empty($config)) {
            return [];
        }
        $config['class'] = GroupByType::class;

        return ['GroupBy' => Yii::createObject($config)];
    }

    /**
     * {@inheritDoc}
     *
     * @return array
     * @throws \yii\base\InvalidConfigException
     */
    public function buildLimit($limit, $offset): array
    {
        if (ctype_digit($limit) && ctype_digit($offset)) {
            return [
                'FractionalPageItemView' => Yii::createObject([
                    'class' => FractionalPageViewType::class,
                    'MaxEntriesReturned' => $limit,
                    'Numerator' => $offset,
                    'Denominator' => $limit
                ])
            ];
        }

        return [];
    }

    /**
     * {@inheritDoc}
     * @return array|object
     * @throws \yii\base\InvalidConfigException
     */
    public function buildCondition($condition, &$params)
    {
        if (empty($condition)) {
            return [];
        }

        if (is_array($condition)) {
            if (isset($condition['class'])) {
                return Yii::createObject($condition);
            }

            $condition = $this->createConditionFromArray($condition);
        }

        return $this->buildExpression($condition, $params);
    }

    /**
     * {@inheritDoc}
     * @return object|array
     */
    public function buildExpression(ExpressionInterface $expression, &$params = [])
    {
        $expression = parent::buildExpression($expression, $params);

        /** @var object $expression */

        return $expression;
    }

    /**
     * Get field URI from property
     *
     * @param string $property
     *
     * @return string|null
     */
    public function getUriFromProperty(string $property): ?string
    {
        switch ($this->_modelClass) {
            case null:
                switch ($property) {
                    case 'id':
                        return UnindexedFieldURIType::ITEM_ID;
                    case 'subject':
                        return UnindexedFieldURIType::ITEM_SUBJECT;
                    case 'body':
                        return UnindexedFieldURIType::ITEM_BODY;
                }
                break;
            case CalendarEvent::class:
                switch ($property) {
                    case 'start':
                        return UnindexedFieldURIType::CALENDAR_START;
                    case 'end':
                        return UnindexedFieldURIType::CALENDAR_END;
                    case 'subject':
                        return UnindexedFieldURIType::ITEM_SUBJECT;
                    case 'body':
                        return UnindexedFieldURIType::ITEM_BODY;
                    case 'type':
                        return UnindexedFieldURIType::CALENDAR_ITEM_TYPE;
                    case 'isRecurring':
                        return UnindexedFieldURIType::CALENDAR_IS_RECURRING;
                    case 'isAllDay':
                        return UnindexedFieldURIType::CALENDAR_IS_ALL_DAY_EVENT;
                    case 'isCancelled':
                        return UnindexedFieldURIType::CALENDAR_IS_CANCELLED;
                    case 'isOnline':
                        return UnindexedFieldURIType::CALENDAR_IS_ONLINE_MEETING;
                    case 'status':
                        return UnindexedFieldURIType::CALENDAR_LEGACY_FREE_BUSY_STATUS;
                    case 'updatedAt':
                        return UnindexedFieldURIType::ITEM_LAST_MODIFIED_TIME;
                    case 'createdAt':
                        return UnindexedFieldURIType::ITEM_DATE_TIME_CREATED;
                }
                break;
            case Message::class:
                switch ($property) {
                    case 'id':
                        return UnindexedFieldURIType::ITEM_ID;
                    case 'parentFolderId':
                        return UnindexedFieldURIType::ITEM_PARENT_FOLDER_ID;
                    case 'sensitivity':
                        return UnindexedFieldURIType::ITEM_SENSITIVITY;
                    case 'importance':
                        return UnindexedFieldURIType::ITEM_IMPORTANCE;
                    case 'subject':
                        return UnindexedFieldURIType::ITEM_SUBJECT;
                    case 'body':
                        return UnindexedFieldURIType::ITEM_BODY;
                    case 'messageId':
                        return UnindexedFieldURIType::MESSAGE_INTERNET_MESSAGE_ID;
                    case 'isRead':
                        return UnindexedFieldURIType::MESSAGE_IS_READ;
                    case 'sentAt':
                        return UnindexedFieldURIType::ITEM_DATE_TIME_SENT;
                    case 'createdAt':
                        return UnindexedFieldURIType::ITEM_DATE_TIME_CREATED;
                    case 'updatedAt':
                        return UnindexedFieldURIType::ITEM_LAST_MODIFIED_TIME;
                }
                break;
            case Folder::class:
                switch ($property) {
                    case 'id':
                        return UnindexedFieldURIType::FOLDER_FOLDER_ID;
                    case 'name':
                        return UnindexedFieldURIType::FOLDER_DISPLAY_NAME;
                    case 'unreadCount':
                        return UnindexedFieldURIType::FOLDER_UNREAD_COUNT;
                    case 'totalCount':
                        return UnindexedFieldURIType::FOLDER_TOTAL_COUNT;
                    case 'childrenCount':
                        return UnindexedFieldURIType::FOLDER_CHILD_FOLDER_COUNT;
                }
                break;
        }

        return null;
    }

    /**
     * {@inheritDoc}
     * @throws NotSupportedException|\yii\base\InvalidConfigException
     */
    protected function prepareInsertValues($table, $columns, $params = []): array
    {
        /** @var ActiveRecord $table */
        $mapping = $table::attributeMapping();

        $items = [];
        if ($columns instanceof Query) {
            throw new NotSupportedException('Nesting is not supported by EWS');
        } else {
            $val = [
                'class' => $table::modelName()
            ];
            foreach ($columns as $name => $value) {
                if (!isset($mapping[$name]) || !isset($mapping[$name]['foreignField']) || $mapping[$name]['readOnly']) {
                    continue;
                }

                $value = $this->castDataType($mapping[$name]['dataType'], $value, true, $params);

                if ($mapping[$name]['foreignModel']) {
                    $tmp = explode('.', $mapping[$name]['foreignField']);
                    $field = array_shift($tmp);
                    if (isset($val[$field]) && is_object($val[$field])) {
                        $val[$field]->{$tmp[0]} = $value;
                    } else {
                        if (isset($tmp[0])) {
                            $val[$field] = Yii::createObject(ArrayHelper::merge(
                                ['class' => $mapping[$name]['foreignModel']],
                                [$tmp[0] => $value]
                            ));
                        } else {
                            $val[$field] = $value;
                        }
                    }
                } else {
                    $val[$mapping[$name]['foreignField']] = $value;
                }
            }
            $items[] = Yii::createObject($val);
        }

        return $items;
    }

    /**
     * {@inheritDoc}
     * @param ActiveRecord $table
     * @throws \yii\base\InvalidConfigException
     */
    protected function prepareUpdateSets($table, $columns, $params = []): array
    {
        $this->_modelClass = $table;
        $mapping = $table::attributeMapping();
        $property = substr(StringHelper::basename($table::modelName()), 0, -4);
        $changes = [];
        foreach ($columns as $name => $value) {
            if (!isset($mapping[$name]) || !isset($mapping[$name]['foreignField']) || $mapping[$name]['readOnly']) {
                continue;
            }
            if (null === ($uri = $this->getUriFromProperty($name))) {
                continue;
            }

            try {
                $value = $this->castDataType($mapping[$name]['dataType'], $value, false, $params);
            } catch (NotSupportedException $e) {
                continue;
            }

            $val = [
                'class' => $table::modelName()
            ];
            if ($mapping[$name]['foreignModel']) {
                $tmp = explode('.', $mapping[$name]['foreignField']);
                $field = array_shift($tmp);

                $val[$field] = Yii::createObject(ArrayHelper::merge(
                    ['class' => $mapping[$name]['foreignModel']],
                    [$tmp[0] => $value]
                ));
            } else {
                $val[$mapping[$name]['foreignField']] = $value;
            }

            $changes[] = Yii::createObject([
                'class' => SetItemFieldType::class,
                'FieldURI' => Yii::createObject([
                    'class' => PathToUnindexedFieldType::class,
                    'FieldURI' => $uri
                ]),
                $property => Yii::createObject($val)
            ]);
        }

        return $changes;
    }

    /**
     * @param array $dataType
     * @param mixed $value
     * @param boolean $isInsert
     * @param array $params
     * @return array|bool|float|int|ActiveRecord|string
     * @throws NotSupportedException|\yii\base\InvalidConfigException
     */
    protected function castDataType(array $dataType, $value, bool $isInsert = true, array $params = [])
    {
        if (count($dataType) > 1) {
            if (false !== in_array('\\DateTime', $dataType)) {
                $dataType = 'DateTime';
            } else {
                $dataType = 'string';
            }
        } else {
            $dataType = $dataType[0];
        }

        if (substr($dataType, -2) === '[]') {
            $dataType = substr($dataType, 0, -2);
            if (!is_array($value)) {
                $value = [$value];
            }
        }

        // Typecast
        switch ($dataType) {
            case 'int':
            case 'integer':
                $value = (int)$value;
                break;
            case 'boolean':
            case 'bool':
                $value = (bool)$value;
                break;
            case 'double':
            case 'float':
                $value = (float)$value;
                break;
            case 'string':
                if (is_float($value)) {
                    $value = StringHelper::floatToString($value);
                }
                $value = (string)$value;
                break;
            case 'DateTime':
                $value = Yii::$app->formatter->asDatetime($value, 'yyyy-MM-dd\'T\'HH:mm:ssxxx');
                break;
            default:
                if (!$isInsert) {
                    throw new NotSupportedException();
                }
                if (class_exists("simialbi\\yii2\\ews\\models\\$dataType")) {
                    if (is_array($value)) {
                        foreach ($value as $k => $item) {
                            /** @var ActiveRecord $item */
                            $value[$k] = $this->prepareInsertValues(get_class($item), $item->getDirtyAttributes(), $params)[0];
                        }
                    } else {
                        /** @var ActiveRecord $value */
                        $value = $this->prepareInsertValues(get_class($value), $value->getDirtyAttributes(), $params);
                    }
                }
                break;
        }

        return $value;
    }

    /**
     * {@inheritDoc}
     */
    protected function defaultExpressionBuilders(): array
    {
        return [
            'yii\db\conditions\ConjunctionCondition' => 'simialbi\yii2\ews\conditions\ConjunctionConditionBuilder',
            'yii\db\conditions\NotCondition' => 'simialbi\yii2\ews\conditions\NotConditionBuilder',
            'yii\db\conditions\AndCondition' => 'simialbi\yii2\ews\conditions\ConjunctionConditionBuilder',
            'yii\db\conditions\OrCondition' => 'simialbi\yii2\ews\conditions\ConjunctionConditionBuilder',
            'yii\db\conditions\LikeCondition' => 'simialbi\yii2\ews\conditions\LikeConditionBuilder',
            'yii\db\conditions\SimpleCondition' => 'simialbi\yii2\ews\conditions\SimpleConditionBuilder',
            'yii\db\conditions\HashCondition' => 'simialbi\yii2\ews\conditions\HashConditionBuilder',
        ];
    }
}
