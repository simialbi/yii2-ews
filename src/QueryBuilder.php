<?php
/**
 * @package yii2-ews
 * @author Simon Karlen <simi.albi@gmail.com>
 */

namespace simialbi\yii2\ews;

use jamesiarmes\PhpEws\ArrayType\NonEmptyArrayOfBaseFolderIdsType;
use jamesiarmes\PhpEws\ArrayType\NonEmptyArrayOfFieldOrdersType;
use jamesiarmes\PhpEws\Enumeration\DefaultShapeNamesType;
use jamesiarmes\PhpEws\Enumeration\DistinguishedFolderIdNameType;
use jamesiarmes\PhpEws\Enumeration\SortDirectionType;
use jamesiarmes\PhpEws\Enumeration\UnindexedFieldURIType;
use jamesiarmes\PhpEws\Request\FindFolderType;
use jamesiarmes\PhpEws\Request\FindItemType;
use jamesiarmes\PhpEws\Type\DistinguishedFolderIdType;
use jamesiarmes\PhpEws\Type\FieldOrderType;
use jamesiarmes\PhpEws\Type\FolderResponseShapeType;
use jamesiarmes\PhpEws\Type\ItemResponseShapeType;
use jamesiarmes\PhpEws\Type\PathToUnindexedFieldType;
use jamesiarmes\PhpEws\Type\RestrictionType;
use simialbi\yii2\ews\models\CalendarEvent;
use simialbi\yii2\ews\models\Contact;
use simialbi\yii2\ews\models\Folder;
use Yii;
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
     * @return array the generated Request (the first array element) and the corresponding parameters
     * injected to the request (the second array element).  The parameters returned
     * include those provided in `$params`.
     */
    public function build($query, $params = [])
    {
        $this->_config = [
            'class' => FindItemType::class,
            'ParentFolderIds' => [
                'class' => NonEmptyArrayOfBaseFolderIdsType::class
            ]
        ];
        if (empty($params['folderId'])) {
            $params['folderId'] = DistinguishedFolderIdNameType::INBOX;
        }
        if ($query instanceof ActiveQuery) {
            /** @var ActiveQuery $query */
            $this->_modelClass = $query->modelClass;

            switch ($query->modelClass) {
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

        if ($this->_config['class'] === FindFolderType::class) {
            $config['FolderShape'] = [
                'class' => FolderResponseShapeType::class,
                'BaseShape' => DefaultShapeNamesType::ALL_PROPERTIES
            ];
        } else {
            $config['ItemShape'] = [
                'class' => ItemResponseShapeType::class,
                'BaseShape' => DefaultShapeNamesType::ALL_PROPERTIES,
                'ConvertHtmlCodePageToUTF8' => true
            ];
        }

        $this->_config = ArrayHelper::merge(
            $this->_config,
            $this->buildFrom($query->from, $params),
            $this->buildWhere($query->where, $params),
            $this->buildOrderBy($query->orderBy)
        );


        return [Yii::createObject($config), $params];
    }

    /**
     * {@inheritDoc}
     *
     * @return array
     */
    public function buildFrom($tables, &$params)
    {
        $config = [];
        if (empty($tables)) {
            $config['ParentFolderIds']['DistinguishedFolderId'] = [
                'class' => DistinguishedFolderIdType::class,
                'Id' => $params['folderId']
            ];
        } else {
            foreach ($tables as $from) {
                $config['ParentFolderIds']['FolderId'][] = $from;
            }
        }

        return $config;
    }

    /**
     * {@inheritDoc}
     * @return array
     */
    public function buildOrderBy($columns)
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
                $config['SortOrder']['FieldOrder'][] = [
                    'class' => FieldOrderType::class,
                    'FieldURI' => [
                        'class' => PathToUnindexedFieldType::class,
                        'FieldURI' => $uri
                    ],
                    'Order' => ($direction === SORT_ASC) ? SortDirectionType::ASCENDING : SortDirectionType::DESCENDING
                ];
            }
        }

        return ['SortOrder' => $config];
    }

    /**
     * {@inheritDoc}
     * @return array
     */
    public function buildWhere($condition, &$params)
    {
        $config = [
            'class' => RestrictionType::class
        ];
        $conditions = $this->buildCondition($condition, $params);
        $r = new \ReflectionClass(RestrictionType::class);
        foreach ($conditions as $condition) {
            $property = substr(StringHelper::basename($condition['class']), -4);
            if (isset($condition['class']) && $r->hasProperty($condition)) {
                $config[$property] = $condition;
            }
        }
        return ['Restriction' => $config];
    }

    /**
     * {@inheritDoc}
     * @return array
     */
    public function buildCondition($condition, &$params)
    {
        if (empty($condition)) {
            return [];
        }

        if (is_array($condition)) {
            if (isset($condition['class'])) {
                return $condition;
            }

            $condition = $this->createConditionFromArray($condition);
        }

        return $this->buildExpression($condition, $params);
    }

    /**
     * {@inheritDoc}
     * @return array
     */
    public function buildExpression(ExpressionInterface $expression, &$params = [])
    {
        return (array)parent::buildExpression($expression, $params);
    }

    /**
     * {@inheritDoc}
     */
    protected function defaultExpressionBuilders()
    {
        return [
//            'yii\db\Query' => 'yii\db\QueryExpressionBuilder',
//            'yii\db\PdoValue' => 'yii\db\PdoValueBuilder',
//            'yii\db\Expression' => 'yii\db\ExpressionBuilder',
            'yii\db\conditions\ConjunctionCondition' => 'simialbi\yii2\ews\conditions\ConjunctionConditionBuilder',
            'yii\db\conditions\NotCondition' => 'simialbi\yii2\ews\conditions\NotConditionBuilder',
            'yii\db\conditions\AndCondition' => 'simialbi\yii2\ews\conditions\ConjunctionConditionBuilder',
            'yii\db\conditions\OrCondition' => 'simialbi\yii2\ews\conditions\ConjunctionConditionBuilder',
//            'yii\db\conditions\BetweenCondition' => 'simialbi\yii2\ews\conditions\BetweenConditionBuilder',
//            'yii\db\conditions\InCondition' => 'simialbi\yii2\ews\conditions\InConditionBuilder',
            'yii\db\conditions\LikeCondition' => 'simialbi\yii2\ews\conditions\LikeConditionBuilder',
//            'yii\db\conditions\ExistsCondition' => 'simialbi\yii2\ews\conditions\ExistsConditionBuilder',
            'yii\db\conditions\SimpleCondition' => 'simialbi\yii2\ews\conditions\SimpleConditionBuilder',
            'yii\db\conditions\HashCondition' => 'simialbi\yii2\ews\conditions\HashConditionBuilder',
//            'yii\db\conditions\BetweenColumnsCondition' => 'yii\db\conditions\BetweenColumnsConditionBuilder'
        ];
    }


    /**
     * Get field URI from property
     * @param string $property
     * @return string|null
     */
    public function getUriFromProperty($property): ?string
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
}
