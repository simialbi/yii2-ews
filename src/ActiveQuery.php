<?php
/**
 * @package yii2-ews
 * @author Simon Karlen <simi.albi@gmail.com>
 */

namespace simialbi\yii2\ews;

use DateTime;
use DateTimeZone;
use jamesiarmes\PhpEws\Request\BaseRequestType;
use yii\db\ActiveQueryInterface;
use yii\db\ActiveQueryTrait;
use yii\db\ActiveRelationTrait;
use yii\helpers\ArrayHelper;
use yii\helpers\StringHelper;

/**
 * Class ActiveQuery
 *
 * @method setCommandCache(Command $command)
 */
class ActiveQuery extends Query implements ActiveQueryInterface
{
    use ActiveQueryTrait;
    use ActiveRelationTrait;

    /**
     * @event Event an event that is triggered when the query is initialized via [[init()]].
     */
    const EVENT_INIT = 'init';

    /**
     * @var BaseRequestType
     */
    public $request;

    /**
     * Constructor.
     * @param string $modelClass the model class associated with this query
     * @param array $config configurations to be applied to the newly created query object
     */
    public function __construct($modelClass, $config = [])
    {
        $this->modelClass = $modelClass;
        parent::__construct($config);
    }

    /**
     * Initializes the object.
     * This method is called at the end of the constructor. The default implementation will trigger
     * an [[EVENT_INIT]] event. If you override this method, make sure you call the parent implementation at the end
     * to ensure triggering of the event.
     */
    public function init()
    {
        parent::init();
        $this->trigger(self::EVENT_INIT);
    }

    /**
     * {@inheritDoc}
     * @param Connection|null $db the ews connection used to create the command.
     * If null, the ews connection returned by [[modelClass]] will be used.
     * @return array|ActiveRecord[]
     */
    public function all($db = null)
    {
        return parent::all($db);
    }

    /**
     * {@inheritDoc}
     */
    public function populate($rows)
    {
        if (empty($rows)) {
            return [];
        }

        $models = $this->createModels($this->mapAttributes($rows));
//        if (!empty($this->join) && $this->indexBy === null) {
//            $models = $this->removeDuplicatedModels($models);
//        }
        if (!empty($this->with)) {
            $this->findWith($this->with, $models);
        }

        if ($this->inverseOf !== null) {
            $this->addInverseRelations($models);
        }

        if (!$this->asArray) {
            foreach ($models as $model) {
                $model->afterFind();
            }
        }

        return parent::populate($models);
    }

    /**
     * Executes query and returns a single row of result.
     * @param Connection|null $db the ews connection used to create the command.
     * If null, the ews connection returned by [[modelClass]] will be used.
     * @return ActiveRecord|array|null a single row of query result. Depending on the setting of [[asArray]],
     * the query result may be either an array or an ActiveRecord object. `null` will be returned
     * if the query results in nothing.
     */
    public function one($db = null)
    {
        $row = parent::one($db);
        if ($row !== false) {
            $models = $this->populate([$row]);
            return reset($models) ?: null;
        }

        return null;
    }

    /**
     * Creates a ews command that can be used to execute this query.
     * @param Connection|null $db the ews connection used to create the command.
     * If null, the ews connection returned by [[modelClass]] will be used.
     * @return Command the created DB command instance.
     * @throws \yii\base\InvalidConfigException
     */
    public function createCommand($db = null): Command
    {
        /* @var $modelClass ActiveRecord */
        $modelClass = $this->modelClass;
        if ($db === null) {
            $db = $modelClass::getDb();
        }

        if ($this->request === null) {
            list($request, $params) = $db->getQueryBuilder()->build($this);
        } else {
            $request = $this->request;
            $params = $this->params;
        }

        $command = $db->createCommand($request, $params);
        $this->setCommandCache($command);

        return $command;
    }

    /**
     * Map attributes in rows from EWS classes to AR classes
     *
     * @return array
     */
    protected function mapAttributes($rows): array
    {
        $mapping = call_user_func([$this->modelClass, 'attributeMapping']);
        $rows = ArrayHelper::toArray($rows);
        $mapped = [];

        foreach ($rows as $row) {
            $item = [];
            foreach ($mapping as $attributeName => $attribute) {
                if (!isset($attribute['foreignField'])) {
                    continue;
                }

                $value = ArrayHelper::getValue($row, $attribute['foreignField']);
                // Typecast
                if (count($attribute['dataType']) === 1 && $value !== null) {
                    $dataType = ltrim($attribute['dataType'][0], '\\');
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
                            if (!is_numeric($value)) {
                                $value = strtotime($value);
                            }
                            $value = new DateTime('@' . $value, new DateTimeZone('UTC'));
                            break;
                        default:
                            if (isset($attribute['foreignModel'])) {
                                //todo
                                continue 2;
                            }
                    }
                }

                $item[$attributeName] = $value;
            }
            $mapped[] = $item;
        }

        return $mapped;
    }
}
