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
use yii\helpers\Url;
use yii\httpclient\Client;
use yii\httpclient\Response;
use yii\web\HeaderCollection;

/**
 * Class Connection
 *
 * Example configuration:
 * ```php
 * 'components' => [
 *     'restclient' => [
 *         'class' => 'simialbi\yii2\rest\Connection',
 *         'config' => [
 *             'base_uri' => 'https://api.site.com/',
 *         ],
 *     ],
 * ],
 * ```
 *
 * @property Client $handler
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
     * @var boolean Whether to user pluralisation or not
     */
    public $usePluralisation = true;
    /**
     * @var boolean Whether we are in test mode or not (prevent execution)
     */
    public $isTestMode = false;

    /**
     * @var string|Closure authorization config
     */
    protected $_auth;

    /**
     * @var Closure Callback to test if API response has error
     * The function signature: `function ($response)`
     * Must return `null`, if the response does not contain an error.
     */
    protected $_errorChecker;

    /**
     * @var Response
     */
    protected $_response;

    /**
     * Returns the name of the DB driver. Based on the the current [[dsn]], in case it was not set explicitly
     * by an end user.
     * @return string name of the DB driver
     */
    public static function getDriverName()
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
    public function createCommand($config = [])
    {
        $config['db'] = $this;
        $command = new Command($config);

        return $command;
    }

    /**
     * Creates new query builder instance.
     *
     * @return QueryBuilder
     */
    public function getQueryBuilder()
    {
        return new QueryBuilder($this);
    }

    /**
     * Performs GET HTTP request.
     *
     * @param string $url URL
     * @param array $data request body
     *
     * @return mixed response
     * @throws \yii\httpclient\Exception
     */
    public function get($url, $data = [])
    {
        return $this->request('get', $url, $data);
    }

    /**
     * Performs HEAD HTTP request.
     *
     * @param string $url URL
     * @param array $data request body
     *
     * @return HeaderCollection response
     * @throws \yii\httpclient\Exception
     */
    public function head($url, $data = [])
    {
        array_unshift($data, $url);
        $this->request('head', $data);

        return $this->_response->headers;
    }

    /**
     * Performs POST HTTP request.
     *
     * @param string $url URL
     * @param array $data request body
     *
     * @return mixed response
     * @throws \yii\httpclient\Exception
     */
    public function post($url, $data = [])
    {
        return $this->request('post', $url, $data);
    }

    /**
     * Performs PUT HTTP request.
     *
     * @param string $url URL
     * @param array $data request body
     *
     * @return mixed response
     * @throws \yii\httpclient\Exception
     */
    public function put($url, $data = [])
    {
        return $this->request('put', $url, $data);
    }

    /**
     * Performs DELETE HTTP request.
     *
     * @param string $url URL
     * @param array $data request body
     *
     * @return mixed response
     * @throws \yii\httpclient\Exception
     */
    public function delete($url, $data = [])
    {
        return $this->request('delete', $url, $data);
    }

    /**
     * Returns the request handler (Guzzle client for the moment).
     * Creates and setups handler if not set.
     * @return Client
     */
    public function getHandler()
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
     * @return mixed|false
     * @throws \yii\httpclient\Exception
     */
    protected function request($method, $url, $data = [])
    {
        $headers = [];
        $method = strtoupper($method);
        $profile = $method . ' ' . Url::to($url) . '#' . (is_array($data) ? http_build_query($data) : $data);

        if ($auth = $this->getAuth()) {
            $headers['Authorization'] = $auth;
        }

        Yii::beginProfile($profile, __METHOD__);
        /* @var $request \yii\httpclient\Request */

        Yii::debug($method, __METHOD__ . '-method');
        Yii::debug($this->handler->baseUrl . '/' . $url, __METHOD__ . '-url');
        Yii::debug($data, __METHOD__ . '-data');
        Yii::debug($headers, __METHOD__ . '-headers');

        $request = call_user_func([$this->handler, $method], $url, $data, $headers);
        $this->_response = $this->isTestMode ? [] : $request->send();
        Yii::endProfile($profile, __METHOD__);

        if (!$this->isTestMode && !$this->_response->isOk) {
            return false;
        }

        return $this->isTestMode ? [] : $this->_response->data;
    }
}