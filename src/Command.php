<?php
/**
 * @package yii2-ews
 * @author Simon Karlen <simi.albi@gmail.com>
 */

namespace simialbi\yii2\ews;

use jamesiarmes\PhpEws\Enumeration\ResponseCodeType;
use jamesiarmes\PhpEws\Request\BaseRequestType;
use phpDocumentor\Reflection\Types\False_;
use Yii;
use yii\base\Component;
use yii\helpers\ArrayHelper;
use yii\helpers\StringHelper;

/**
 * Class Command class implements the soap xml generation for .
 *
 *
 */
class Command extends Component
{
    /**
     * @var Connection
     */
    public $db;

    /**
     * @var array the parameters (name => value) that are injected to the request.
     */
    public $params = [];

    /**
     * @var \yii\caching\Dependency the dependency to be associated with the cached query result for this command
     * @see cache()
     */
    public $queryCacheDependency;
    /**
     * @var int the default number of seconds that query results can remain valid in cache.
     * Use 0 to indicate that the cached data will never expire. And use a negative number to indicate
     * query cache should not be used.
     * @see cache()
     */
    public $queryCacheDuration;

    /**
     * @var BaseRequestType
     */
    private $_request;

    /**
     * Returns the raw SQL by inserting parameter values into the corresponding placeholders in [[sql]].
     * Note that the return value of this method should mainly be used for logging purpose.
     * It is likely that this method returns an invalid SQL due to improper replacement of parameter placeholders.
     * @return BaseRequestType the raw SQL with parameter values inserted into the corresponding placeholders in [[sql]].
     */
    public function getRequest(): BaseRequestType
    {
        return $this->_request;
    }

    /**
     * Specifies the request to be executed.
     * The previous request (if any) will be discarded, and params will be cleared as well.
     * @param BaseRequestType|null $request
     * @return $this
     */
    public function setRequest(?BaseRequestType $request): Command
    {
        if ($request !== $this->_request) {
            $this->params = [];
            $this->_request = $request;
        }

        return $this;
    }

    /**
     * Enables query cache for this command.
     * @param int|null $duration the number of seconds that query result of this command can remain valid in the cache.
     * If this is not set, the value of [[Connection::queryCacheDuration]] will be used instead.
     * Use 0 to indicate that the cached data will never expire.
     * @param \yii\caching\Dependency|null $dependency the cache dependency associated with the cached query result.
     * @return $this the command object itself
     */
    public function cache(?int $duration = null, ?\yii\caching\Dependency $dependency = null): Command
    {
        $this->queryCacheDuration = $duration === null ? $this->db->queryCacheDuration : $duration;
        $this->queryCacheDependency = $dependency;
        return $this;
    }

    /**
     * Disables query cache for this command.
     * @return $this the command object itself
     */
    public function noCache(): Command
    {
        $this->queryCacheDuration = -1;
        return $this;
    }

    /**
     * Creates a new record
     *
     * @param string $model the model instance to create insert statement for
     * @param array $columns the column data (name => value) to be inserted
     * @param array $params the binding parameters that will be generated by this method.
     * They should be bound to the DB command later.
     *
     * @return $this the command object itself
     * @throws \yii\base\InvalidConfigException
     * @throws \yii\base\NotSupportedException
     */
    public function insert(string $model, array $columns, array $params = []): Command
    {
        $request = $this->db->getQueryBuilder()->insert($model, $columns, $params);

        return $this->setRequest($request);
    }

    /**
     * Creates an UPDATE command.
     *
     * The method will properly escape the column names and bind the values to be updated.
     *
     * Note that the created command is not executed until [[execute()]] is called.
     *
     * @param string $model the model instance to create insert statement for
     * @param array $columns the column data (name => value) to be updated.
     * @param array $condition the condition. Normally the [[changeKey]] and [[id]].
     * @return $this the command object itself
     * @throws \yii\base\InvalidConfigException
     * @throws \yii\base\NotSupportedException
     */
    public function update(string $model, array $columns, array $condition): Command
    {
        $params = [];
        $request = $this->db->getQueryBuilder()->update($model, $columns, $condition, $params);

        return $this->setRequest($request);
    }

    /**
     * Creates a DELETE SQL statement.
     *
     * The method will properly escape the column names.
     *
     * Note that the created command is not executed until [[execute()]] is called.
     *
     * @param string $model the model instance to create insert statement for
     * @param array $condition the condition. Normally the [[changeKey]] and [[id]].
     * @return $this
     * @throws \yii\base\InvalidConfigException
     */
    public function delete(string $model, $condition = ''): Command
    {
        $params = [];
        $request = $this->db->getQueryBuilder()->delete($model, $condition, $params);

        return $this->setRequest($request);
    }

    /**
     * Executes the statement and returns ALL rows at once.
     * @param int|null $fetchMode for compatibility with [[\yii\db\Command]]
     * @return array|\jamesiarmes\PhpEws\ArrayType\ArrayOfRealItemsType all rows of the query result. Each array element
     * is an array representing a row of data. An empty array is returned if the query results in nothing.
     * @throws \yii\base\InvalidConfigException
     * @throws Exception
     */
    public function queryAll(?int $fetchMode = null)
    {
        return $this->queryInternal();
    }

    /**
     * Executes the SQL statement and returns the first row of the result.
     * This method is best used when only the first row of result is needed for a query.
     * @param int|null $fetchMode the result fetch mode. Please refer to [PHP manual](https://secure.php.net/manual/en/pdostatement.setfetchmode.php)
     * for valid fetch modes. If this parameter is null, the value set in [[fetchMode]] will be used.
     * @return array|false the first row (in terms of an array) of the query result. False is returned if the query
     * results in nothing.
     * @throws Exception execution failed
     */
    public function queryOne(?int $fetchMode = null)
    {
        return $this->queryInternal();
    }

    /**
     * Executes the request
     * This method should only be used for executing non-query operations, such as `INSERT`, `DELETE`, `UPDATE` etc.
     * No result set will be returned.
     * @return \jamesiarmes\PhpEws\Type\ItemIdType|boolean true if the request was successful, otherwise false
     * @throws Exception execution failed
     */
    public function execute()
    {
        /** @var \jamesiarmes\PhpEws\Request\CreateItemType $request */
        $request = $this->getRequest();
        if (!$request || !is_object($request)) {
            return false;
        }
        $method = substr(StringHelper::basename(get_class($request)), 0, -4);
        $key = $method . ': ' . serialize($request);
        $return = false;

        try {
            if ($this->db->enableProfiling) {
                Yii::beginProfile($key, __METHOD__);
            }
            if ($this->db->enableLogging) {
                Yii::info($key, __METHOD__);
            }

            /** @var \jamesiarmes\PhpEws\Response\BaseResponseMessageType $response */
            $response = call_user_func([$this->db->getClient(), $method], $request);

            /** @var \jamesiarmes\PhpEws\Response\ItemInfoResponseMessageType $message */
            $message = ArrayHelper::getValue($response, "ResponseMessages.{$method}ResponseMessage");
            if (is_array($message)) {
                $message = array_shift($message);
            }

            if ($message->ResponseCode !== ResponseCodeType::NO_ERROR) {
                throw new Exception($message->MessageText, [
                    'responseCode' => $message->ResponseCode,
                    'responseClass' => $message->ResponseClass
                ]);
            }

            switch ($method) {
                case 'CreateItem':
                case 'UpdateItem':
                    foreach ($message->Items as $item) {
                        /** @var \jamesiarmes\PhpEws\Type\ItemType[] $item */
                        $return = $item[0]->ItemId;
                        break;
                    }
                    break;
                case 'DeleteItem':
                    return true;
            }
        } catch (Exception $e) {
            throw $e;
        } catch (\Exception $e) {
            throw new Exception($e->getMessage());
        } finally {
            if ($this->db->enableProfiling) {
                Yii::endProfile($key, __METHOD__);
            }
        }

        return !$return ? true : $return;
    }

    /**
     * Performs the actual statement
     *
     * @param string|null $method method of the [[\jamesiarmes\PhpEws\Client]] to be called
     *
     * @return mixed
     * @throws Exception
     */
    protected function queryInternal(?string $method = null)
    {
        $request = $this->getRequest();
        if (null === $method) {
            $method = substr(StringHelper::basename(get_class($request)), 0, -4);
        }
        $key = $method . ': ' . serialize($request);
        if ($method !== '') {
            $info = $this->db->getQueryCacheInfo($this->queryCacheDuration, $this->queryCacheDependency);
            if (is_array($info)) {
                /* @var $cache \yii\caching\CacheInterface */
                $cache = $info[0];
                $result = $cache->get($key);
                if (is_array($result) && isset($result[0])) {
                    Yii::debug('Query result served from cache', __METHOD__);
                    return $result[0];
                }
            }
        }

        try {
            if ($this->db->enableProfiling) {
                Yii::beginProfile($key, __METHOD__);
            }
            if ($this->db->enableLogging) {
                Yii::info($key, __METHOD__);
            }

            /** @var \jamesiarmes\PhpEws\Response\ResponseMessageType $response */
            $response = call_user_func([$this->db->getClient(), $method], $request);

            /** @var \jamesiarmes\PhpEws\Response\FindItemResponseMessageType $message */
            $message = ArrayHelper::getValue($response, "ResponseMessages.{$method}ResponseMessage");

            if (is_array($message)) {
                $message = array_shift($message);
            }

            if ($message->ResponseCode !== ResponseCodeType::NO_ERROR) {
                throw new Exception($message->MessageText, [
                    'responseCode' => $message->ResponseCode,
                    'responseClass' => $message->ResponseClass
                ]);
            }

            switch ($method) {
                case 'FindItem':
                    $result = ArrayHelper::getValue($message, 'RootFolder.Items');
                    break;
                case 'FindFolder':
                    $result = ArrayHelper::getValue($message, 'RootFolder.Folders');
                    break;
                case 'GetItem':
                    $result = ArrayHelper::getValue($message, 'Items');
                    break;
                default:
                    $result = [];
                    break;
            }

            // TODO: Find better solution
            if (!is_array($result)) {
                $r = new \ReflectionClass($result);
                foreach ($r->getProperties() as $property) {
                    if ($property->isPublic() && is_array($result->{$property->name}) && !empty($result->{$property->name})) {
                        $result = $result->{$property->name};
                        break;
                    }
                }
            }
            if ($result instanceof \jamesiarmes\PhpEws\ArrayType\ArrayOfRealItemsType) {
                $result = [];
            }
        } catch (Exception $e) {
            throw $e;
        } catch (\Exception $e) {
            throw new Exception($e->getMessage());
        } finally {
            if ($this->db->enableProfiling) {
                Yii::endProfile($key, __METHOD__);
            }
        }

        if (isset($cache, $key, $info)) {
            $cache->set($key, [$result], $info[1], $info[2]);
            Yii::debug('Saved query result in cache', __METHOD__);
        }

        return $result;
    }
}
