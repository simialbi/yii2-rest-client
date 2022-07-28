<?php
/**
 * Created by PhpStorm.
 * User: simialbi
 * Date: 12.10.2017
 * Time: 10:34
 */

namespace simialbi\yii2\rest;

use Closure;
use Yii;
use yii\base\Component;
use yii\base\InvalidConfigException;
use yii\caching\CacheInterface;
use yii\httpclient\Client;
use yii\httpclient\Response;
use yii\web\HeaderCollection;
use simialbi\yii2\rest\Exception as RestException;

/**
 * Class Connection
 *
 * Example configuration:
 * ```php
 * 'components' => [
 *     'restclient' => [
 *         'class' => 'simialbi\yii2\rest\Connection',
 *         'baseUrl' => 'https://api.site.com/',
 *     ],
 * ],
 * ```
 *
 * @property Client $handler
 * @property-read array $queryCacheInfo
 * @property-write string|Closure $auth
 */
class Connection extends Component
{
    /**
     * @event Event an event that is triggered after a DB connection is established
     */
    const EVENT_AFTER_OPEN = 'afterOpen';

    /**
     * @var Client
     */
    protected static $_handler = null;

    /**
     * @var string base request URL.
     */
    public $baseUrl = '';

    /**
     * @var array request object configuration.
     */
    public $requestConfig = [];

    /**
     * @var array response config configuration.
     */
    public $responseConfig = [];

    /**
     * @var boolean Whether to use pluralisation or not
     */
    public $usePluralisation = true;

    /**
     * @var boolean Whether to use filter keyword or not
     */
    public $useFilterKeyword = true;

    /**
     * @var string The method to use for update operations. Defaults to [[put]].
     */
    public $updateMethod = 'put';

    /**
     * @var boolean Whether the connection should throw an exception if response is not 200 or not
     */
    public $enableExceptions = false;

    /**
     * @var boolean Whether we are in test mode or not (prevent execution)
     */
    public $isTestMode = false;

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
     * @var string|null the name of the property in the response where the items are wrapped. If not set, there is no
     * wrapping property.
     */
    public $itemsProperty;

    /**
     * @var string|Closure authorization config
     */
    protected $_auth;

    /**
     * @var Response
     */
    protected $_response;

    /**
     * @var array query cache parameters for the [[cache()]] calls
     */
    private $_queryCacheInfo = [];

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
     *     return $db->createCommand('SELECT * FROM customer WHERE id=1')->queryOne();
     * });
     * ```
     *
     * Note that query cache is only meaningful for queries that return results. For queries performed with
     * [[Command::execute()]], query cache will not be used.
     *
     * @param callable $callable a PHP callable that contains DB queries which will make use of query cache.
     * The signature of the callable is `function (Connection $db)`.
     * @param int|null $duration the number of seconds that query results can remain valid in the cache. If this is
     * not set, the value of [[queryCacheDuration]] will be used instead.
     * Use 0 to indicate that the cached data will never expire.
     * @param \yii\caching\Dependency|null $dependency the cache dependency associated with the cached query results.
     * @return mixed the return result of the callable
     * @throws \Exception|\Throwable if there is any exception during query
     * @see enableQueryCache
     * @see queryCache
     * @see noCache()
     */
    public function cache(callable $callable, int $duration = null, \yii\caching\Dependency $dependency = null)
    {
        $this->_queryCacheInfo[] = [$duration === null ? $this->queryCacheDuration : $duration, $dependency];
        try {
            $result = call_user_func($callable, $this);
            array_pop($this->_queryCacheInfo);
            return $result;
        } catch (\Exception $e) {
            array_pop($this->_queryCacheInfo);
            throw $e;
        } catch (\Throwable $e) {
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
     *         return $db->createCommand('SELECT * FROM customer WHERE id=1')->queryOne();
     *     });
     * });
     * ```
     *
     * @param callable $callable a PHP callable that contains DB queries which should not use query cache.
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
        } catch (\Exception $e) {
            array_pop($this->_queryCacheInfo);
            throw $e;
        } catch (\Throwable $e) {
            array_pop($this->_queryCacheInfo);
            throw $e;
        }
    }

    /**
     * Returns the current query cache information.
     * This method is used internally by [[Command]].
     * @param int $duration the preferred caching duration. If null, it will be ignored.
     * @param \yii\caching\Dependency $dependency the preferred caching dependency. If null, it will be ignored.
     * @return array the current query cache information, or null if query cache is not enabled.
     * @throws InvalidConfigException
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

    /**
     * Returns the name of the DB driver. Based on the the current [[dsn]], in case it was not set explicitly
     * by an end user.
     * @return string name of the DB driver
     */
    public static function getDriverName(): string
    {
        return 'rest';
    }

    /**
     * {@inheritdoc}
     * @throws InvalidConfigException
     */
    public function init()
    {
        if (!$this->baseUrl) {
            throw new InvalidConfigException('The `baseUrl` config option must be set');
        }

        $this->baseUrl = rtrim($this->baseUrl, '/');

        parent::init();
    }

    /**
     * Returns the authorization config.
     *
     * @return string authorization config
     */
    protected function getAuth()
    {
        if ($this->_auth instanceof Closure) {
            $this->_auth = call_user_func($this->_auth, $this);
        }

        return $this->_auth;
    }

    /**
     * Changes the current authorization config.
     *
     * @param string|Closure $auth authorization config
     */
    public function setAuth($auth)
    {
        $this->_auth = $auth;
    }

    /**
     * Closes the connection when this component is being serialized.
     * @return array
     */
    public function __sleep()
    {
        return array_keys(get_object_vars($this));
    }

    /**
     * Creates a command for execution.
     *
     * @param array $config the configuration for the Command class
     *
     * @return Command the DB command
     */
    public function createCommand(array $config = []): Command
    {
        $config['db'] = $this;
        return new Command($config);
    }

    /**
     * Creates new query builder instance.
     *
     * @return QueryBuilder
     */
    public function getQueryBuilder(): QueryBuilder
    {
        return new QueryBuilder($this);
    }

    /**
     * Performs GET HTTP request.
     *
     * @param string|array $url URL
     * @param array $data request body
     *
     * @return array|false response
     * @throws Exception
     */
    public function get($url, array $data = [])
    {
        array_unshift($data, $url);
        return $this->request('get', $data);
    }

    /**
     * Performs HEAD HTTP request.
     *
     * @param string|array $url URL
     * @param array $data request body
     *
     * @return HeaderCollection response
     * @throws Exception
     */
    public function head($url, array $data = []): HeaderCollection
    {
        array_unshift($data, $url);
        $this->request('head', $data);

        return $this->_response->headers;
    }

    /**
     * Performs POST HTTP request.
     *
     * @param string|array $url URL
     * @param array $data request body
     *
     * @return array|false response
     * @throws Exception
     */
    public function post($url, array $data = [])
    {
        return $this->request('post', $url, $data);
    }

    /**
     * Performs PUT HTTP request.
     *
     * @param string|array $url URL
     * @param array $data request body
     *
     * @return array|false response
     * @throws Exception
     */
    public function put($url, array $data = [])
    {
        return $this->request('put', $url, $data);
    }

    /**
     * Performs DELETE HTTP request.
     *
     * @param string|array $url URL
     * @param array $data request body
     *
     * @return array|false response
     * @throws Exception
     */
    public function delete($url, array $data = [])
    {
        return $this->request('delete', $url, $data);
    }

    /**
     * Returns the request handler (Guzzle client for the moment).
     * Creates and setups handler if not set.
     * @return Client
     */
    public function getHandler(): Client
    {
        if (static::$_handler === null) {
            $requestConfig = $this->requestConfig;
            $responseConfig = array_merge([
                'class' => 'yii\httpclient\Response',
                'format' => Client::FORMAT_JSON
            ], $this->responseConfig);
            static::$_handler = new Client([
                'baseUrl' => $this->baseUrl,
                'requestConfig' => $requestConfig,
                'responseConfig' => $responseConfig
            ]);
        }

        return static::$_handler;
    }

    /**
     * Handles the request with handler.
     * Returns array or raw response content, if $raw is true.
     *
     * @param string $method POST, GET, etc
     * @param string|array $url the URL for request, not including proto and site
     * @param array $data the request data
     *
     * @return array|false
     * @throws Exception
     */
    protected function request(string $method, $url, array $data = [])
    {
        if (is_array($url)) {
            $path = array_shift($url);
            $query = http_build_query($url);

            array_unshift($url, $path);

            $path .= '?' . $query;
        } else {
            $path = $url;
        }

        $headers = [];
        $method = strtoupper($method);
        $profile = $method . ' ' . $this->handler->baseUrl . '/' . $path . '#' . (is_array($data) ? http_build_query($data) : $data);

        if ($auth = $this->getAuth()) {
            $headers['Authorization'] = $auth;
        }
        if ($method === 'head') {
            $data = $headers;
            $headers = [];
        }

        Yii::beginProfile($profile, __METHOD__);
        /* @var $request \yii\httpclient\Request */

        Yii::debug($method, __METHOD__ . '-method');
        Yii::debug($this->handler->baseUrl . '/' . $path, __METHOD__ . '-url');
        Yii::debug($data, __METHOD__ . '-data');
        Yii::debug($headers, __METHOD__ . '-headers');

        $request = call_user_func([$this->handler, $method], $url, $data, $headers);
        try {
            $this->_response = $this->isTestMode ? [] : $request->send();
        } catch (\yii\httpclient\Exception $e) {
            throw new RestException('Request failed', [], 1, $e);
        }
        Yii::endProfile($profile, __METHOD__);

        if (!$this->isTestMode && !$this->_response->isOk) {
            if ($this->enableExceptions) {
                throw new RestException($this->_response->content, $this->_response->headers->toArray());
            }
            return false;
        }

        return $this->isTestMode ? [] : $this->_response->data;
    }
}
