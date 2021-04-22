<?php
/**
 * @package yii2-ews
 * @author Simon Karlen <simi.albi@gmail.com>
 */

namespace simialbi\yii2\ews;

use jamesiarmes\PhpEws\Client;
use jamesiarmes\PhpEws\Request\BaseRequestType;
use Yii;
use yii\base\Component;
use yii\caching\CacheInterface;
use yii\validators\EmailValidator;

/**
 * Class Service
 * @package simialbi\yii2\ews
 *
 * @property-read Client $client
 */
class Connection extends Component
{
    /**
     * @var string The url to the exchange server you wish to connect to, without the protocol. Example:
     *     mail.example.com.
     */
    public $server;
    /**
     * @var string The user to connect to the server with. This is usually the local portion of the users email address.
     * Example: "user" if the email address is "user@example.com"
     */
    public $username;
    /**
     * @var string The user's plain-text password.
     */
    public $password;
    /**
     * @var array The mailboxes to check
     */
    public $mailboxes = [];
    /**
     * @var bool whether to enable logging of database queries. Defaults to true.
     * You may want to disable this option in a production environment to gain performance
     * if you do not need the information being logged.
     * @see enableProfiling
     */
    public $enableLogging = true;
    /**
     * @var bool whether to enable profiling of opening database connection and database queries. Defaults to true.
     * You may want to disable this option in a production environment to gain performance
     * if you do not need the information being logged.
     * @see enableLogging
     */
    public $enableProfiling = true;
    /**
     * @var bool whether to enable query caching.
     * Note that in order to enable query caching, a valid cache component as specified
     * by [[queryCache]] must be enabled and [[enableQueryCache]] must be set true.
     * Also, only the results of the queries enclosed within [[cache()]] will be cached.
     * @see queryCache
     * @see cache()
     * @see noCache()
     */
    public $enableQueryCache = false;
    /**
     * @var int the default number of seconds that query results can remain valid in cache.
     * Defaults to 3600, meaning 3600 seconds, or one hour. Use 0 to indicate that the cached data will never expire.
     * The value of this property will be used when [[cache()]] is called without a cache duration.
     * @see enableQueryCache
     * @see cache()
     */
    public $queryCacheDuration = 3600;
    /**
     * @var CacheInterface|string the cache object or the ID of the cache application component
     * that is used for query caching.
     * @see enableQueryCache
     */
    public $queryCache = 'cache';

    /**
     * @var Client EWS Client instance.
     */
    private $_client;
    /**
     * @var array query cache parameters for the [[cache()]] calls
     */
    private $_queryCacheInfo = [];

    /**
     * {@inheritDoc}
     */
    public function init()
    {
        parent::init();

        array_unshift($this->mailboxes, $this->username);
        $this->mailboxes = array_unique($this->mailboxes);

        $validator = new EmailValidator(['enableIDN' => function_exists('idn_to_ascii')]);
        foreach ($this->mailboxes as $k => $mailbox) {
            if (!$validator->validate($mailbox)) {
                Yii::warning("Mailbox '$mailbox' is not a valid email address", __METHOD__);
                unset($this->mailboxes[$k]);
            }
        }
    }

    /**
     * @return Client
     */
    public function getClient(): Client
    {
        if (empty($this->_client) || !$this->_client instanceof Client) {
            $this->_client = new Client($this->server, $this->username, $this->password);
        }

        return $this->_client;
    }

    /**
     * Creates a command for execution.
     *
     * @param BaseRequestType|null $request
     * @param array $params
     * @return Command the DB command
     * @throws \yii\base\InvalidConfigException
     */
    public function createCommand($request = null, array $params = []): Command
    {
        $config = [
            'class' => Command::class,
            'db' => $this,
            'request' => $request,
            'params' => $params
        ];

        /** @var Command $command */
        $command = Yii::createObject($config);

        return $command;
    }


    /**
     * Returns the query builder for the current DB connection.
     * @return QueryBuilder the query builder for the current DB connection.
     */
    public function getQueryBuilder(): QueryBuilder
    {
        return new QueryBuilder($this);
    }


    /**
     * Uses query cache for the queries performed with the callable.
     *
     * When query caching is enabled ([[enableQueryCache]] is true and [[queryCache]] refers to a valid cache),
     * queries performed within the callable will be cached and their results will be fetched from cache if available.
     * For example,
     *
     * ```php
     * // The customer will be fetched from cache if available.
     * // If not, the query will be made against DB and cached for use next time.
     * $customer = $db->cache(function (Connection $db) {
     *     return $db->createCommand([])->queryOne();
     * });
     * ```
     *
     * Note that query cache is only meaningful for queries that return results. For queries performed with
     * [[Command::execute()]], query cache will not be used.
     *
     * @param callable $callable a PHP callable that contains EWS operations which will make use of query cache.
     * The signature of the callable is `function (Connection $db)`.
     * @param int $duration the number of seconds that query results can remain valid in the cache. If this is
     * not set, the value of [[queryCacheDuration]] will be used instead.
     * Use 0 to indicate that the cached data will never expire.
     * @param \yii\caching\Dependency $dependency the cache dependency associated with the cached query results.
     * @return mixed the return result of the callable
     * @throws \Exception|\Throwable if there is any exception during query
     * @see enableQueryCache
     * @see queryCache
     * @see noCache()
     */
    public function cache(callable $callable, $duration = null, $dependency = null)
    {
        $this->_queryCacheInfo[] = [$duration === null ? $this->queryCacheDuration : $duration, $dependency];
        try {
            $result = call_user_func($callable, $this);
            array_pop($this->_queryCacheInfo);
            return $result;
        } catch (\Exception | \Throwable $e) {
            array_pop($this->_queryCacheInfo);
            throw $e;
        }
    }

    /**
     * Disables query cache temporarily.
     *
     * Queries performed within the callable will not use query cache at all. For example,
     *
     * ```php
     * $db->cache(function (Connection $db) {
     *
     *     // ... queries that use query cache ...
     *
     *     return $db->noCache(function (Connection $db) {
     *         // this query will not use query cache
     *         return $db->createCommand([])->queryOne();
     *     });
     * });
     * ```
     *
     * @param callable $callable a PHP callable that contains EWS operations which should not use query cache.
     * The signature of the callable is `function (Connection $db)`.
     * @return mixed the return result of the callable
     * @throws \Exception|\Throwable if there is any exception during query
     * @see enableQueryCache
     * @see queryCache
     * @see cache()
     */
    public function noCache(callable $callable)
    {
        $this->_queryCacheInfo[] = false;
        try {
            $result = call_user_func($callable, $this);
            array_pop($this->_queryCacheInfo);
            return $result;
        } catch (\Exception | \Throwable $e) {
            array_pop($this->_queryCacheInfo);
            throw $e;
        }
    }

    /**
     * Returns the current query cache information.
     * This method is used internally by [[Command]].
     * @param int|null $duration the preferred caching duration. If null, it will be ignored.
     * @param \yii\caching\Dependency|null $dependency the preferred caching dependency. If null, it will be ignored.
     * @return array the current query cache information, or null if query cache is not enabled.
     * @throws \yii\base\InvalidConfigException
     * @internal
     */
    public function getQueryCacheInfo(int $duration = null, \yii\caching\Dependency $dependency = null): ?array
    {
        if (!$this->enableQueryCache) {
            return null;
        }

        $info = end($this->_queryCacheInfo);
        if (is_array($info)) {
            if ($duration === null) {
                $duration = $info[0];
            }
            if ($dependency === null) {
                $dependency = $info[1];
            }
        }

        if ($duration === 0 || $duration > 0) {
            if (is_string($this->queryCache) && Yii::$app) {
                $cache = Yii::$app->get($this->queryCache, false);
            } else {
                $cache = $this->queryCache;
            }
            if ($cache instanceof CacheInterface) {
                return [$cache, $duration, $dependency];
            }
        }

        return null;
    }
}
