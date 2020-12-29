<?php
/**
 * Created by PhpStorm.
 * User: simialbi
 * Date: 12.10.2017
 * Time: 10:34
 */

namespace simialbi\yii2\rest;

use Yii;
use yii\base\Component;
use yii\helpers\ArrayHelper;
use yii\helpers\Inflector;

/**
 * Class Command class implements the API for accessing REST API.
 *
 * @property string $rawUrl The raw URL with parameter values inserted into the corresponding placeholders.
 * This property is read-only.
 */
class Command extends Component
{
    /**
     * @var Connection
     */
    public $db;

    /**
     * @var string the name of the ActiveRecord class.
     */
    public $modelClass;

    /**
     * @var string
     */
    public $pathInfo;

    /**
     * @var array
     */
    public $queryParams;

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
     * Enables query cache for this command.
     * @param int $duration the number of seconds that query result of this command can remain valid in the cache.
     * If this is not set, the value of [[Connection::queryCacheDuration]] will be used instead.
     * Use 0 to indicate that the cached data will never expire.
     * @param \yii\caching\Dependency $dependency the cache dependency associated with the cached query result.
     * @return $this the command object itself
     */
    public function cache($duration = null, $dependency = null)
    {
        $this->queryCacheDuration = $duration === null ? $this->db->queryCacheDuration : $duration;
        $this->queryCacheDependency = $dependency;
        return $this;
    }

    /**
     * Disables query cache for this command.
     * @return $this the command object itself
     */
    public function noCache()
    {
        $this->queryCacheDuration = -1;
        return $this;
    }

    /**
     * Returns the raw url by inserting parameter values into the corresponding placeholders.
     * Note that the return value of this method should mainly be used for logging purpose.
     * It is likely that this method returns an invalid URL due to improper replacement of parameter placeholders.
     * @return string the raw URL with parameter values inserted into the corresponding placeholders.
     */
    public function getRawUrl()
    {
        $rawUrl = $this->db->handler->get($this->pathInfo, $this->queryParams)->fullUrl;

        return $rawUrl;
    }

    /**
     * Executes the SQL statement and returns ALL rows at once.
     * @param int $fetchMode for compatibility with [[\yii\db\Command]]
     * @return array all rows of the query result. Each array element is an array representing a row of data.
     * An empty array is returned if the query results in nothing.
     * @throws \yii\base\InvalidConfigException
     */
    public function queryAll($fetchMode = null)
    {
        return $this->queryInternal();
    }

    /**
     * Executes the SQL statement and returns the first row of the result.
     * This method is best used when only the first row of result is needed for a query.
     * @param int $fetchMode for compatibility with [[\yii\db\Command]]
     * @return array|false the first row (in terms of an array) of the query result. False is returned if the query
     * results in nothing.
     */
    public function queryOne($fetchMode = null)
    {
        /* @var $class ActiveRecord */
        $class = $this->modelClass;

        if (!empty($class) && class_exists($class)) {
            $pks = $class::primaryKey();

            if (count($pks) === 1 && isset($this->queryParams['filter'])) {
                $primaryKey = current($pks);
                $currentKey = ArrayHelper::remove($this->queryParams['filter'], $primaryKey);
                if ($currentKey) {
                    $this->pathInfo .= '/' . $currentKey;
                }
            }
        }

        return $this->queryInternal();
    }

    /**
     * Make request and check for error.
     *
     * @param string $method
     *
     * @return mixed
     */
    public function execute($method = 'get')
    {
        return $this->queryInternal($method);
    }

    /**
     * Creates a new record
     *
     * @param string $model
     * @param array $columns
     *
     * @return mixed
     * @throws Exception
     */
    public function insert($model, $columns)
    {
        $this->pathInfo = $model;

        return $this->db->post($this->pathInfo, $columns);
    }

    /**
     * Updates an existing record
     *
     * @param string $model
     * @param array $data
     * @param string $id
     *
     * @return mixed
     * @throws Exception
     */
    public function update($model, $data = [], $id = null)
    {
        $this->pathInfo = $model;
        if ($id) {
            $this->pathInfo .= '/' . $id;
        }

        return $this->db->put($this->pathInfo, $data);
    }

    /**
     * Deletes a record
     *
     * @param string $model
     * @param string $id
     *
     * @return mixed
     * @throws Exception
     */
    public function delete($model, $id = null)
    {
        $this->pathInfo = $model;
        if ($id) {
            $this->pathInfo .= '/' . $id;
        }

        return $this->db->delete($this->pathInfo);
    }

    /**
     * Performs the actual statement
     *
     * @param string $method
     *
     * @return mixed
     */
    protected function queryInternal($method = 'get')
    {
        if ($this->db->usePluralisation && strpos($this->pathInfo, '/') === false) {
            $this->pathInfo = Inflector::pluralize($this->pathInfo);
        }
        if (!$this->db->useFilterKeyword) {
            $filter = ArrayHelper::remove($this->queryParams, 'filter', []);
            $this->queryParams = array_merge($this->queryParams, $filter);
        }
        $info = $this->db->getQueryCacheInfo($this->queryCacheDuration, $this->queryCacheDependency);
        if (is_array($info)) {
            /* @var $cache \yii\caching\CacheInterface */
            $cache = $info[0];
            $cacheKey = $this->getCacheKey($method);
            $result = $cache->get($cacheKey);
            if (is_array($result) && isset($result[0])) {
                Yii::debug('Query result served from cache', 'simialbi\yii2\rest\Command::query');
                return $result[0];
            }
        }

        $result = $this->db->$method($this->pathInfo, $this->queryParams);
        if ($this->db->itemsProperty) {
            $result = ArrayHelper::getValue($result, $this->db->itemsProperty, []);
        }
        if (isset($cache, $cacheKey, $info)) {
            $cache->set($cacheKey, [$result], $info[1], $info[2]);
            Yii::debug('Saved query result in cache', 'simialbi\yii2\rest\Command::query');
        }

        return $result;
    }

    /**
     * Returns the cache key for the query.
     *
     * @param string $method
     * @return array the cache key
     * @since 2.0.16
     */
    protected function getCacheKey($method)
    {
        return [
            __CLASS__,
            $method,
            $this->pathInfo,
            $this->queryParams
        ];
    }
}
