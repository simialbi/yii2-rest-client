<?php

namespace simialbi\yii2\rest;

use yii\base\Component;
use yii\helpers\ArrayHelper;
use yii\httpclient\Client;
use yii\httpclient\Exception;
use yii\httpclient\Request;
use yii\httpclient\Response;
use yii\web\HttpException;
use yii\web\ServerErrorHttpException;

/**
 * Class Query
 * HTTP transport by GuzzleHTTP
 *
 * @package chsergey\rest
 */
class ActiveQuery extends Component implements ActiveQueryInterface {
	/**
	 * Data type for requests and responses
	 * Required.
	 * @var string
	 */
	public $dataType = Client::FORMAT_JSON;
	/**
	 * Headers for requests
	 * @var array
	 */
	public $requestHeaders = [];
	/**
	 * Wildcard for response headers object
	 * @see Query::count()
	 * @var array
	 */
	public $responseHeaders = [
		'totalCount'   => 'X-Pagination-Total-Count',
		'pageCount'    => 'X-Pagination-Page-Count',
		'currPage'     => 'X-Pagination-Current-Page',
		'perPageCount' => 'X-Pagination-Per-Page',
		'links'        => 'Link',
	];
	/**
	 * Response unserializer class
	 * @var array|object
	 */
	public $unserializers = [
		Client::FORMAT_JSON => [
			'class' => 'simialbi\yii2\rest\JsonUnserializer'
		]
	];
	/**
	 * HTTP client that performs HTTP requests
	 * @var Client
	 */
	public $httpClient;
	/**
	 * Model class
	 * @var ActiveRecord
	 */
	public $modelClass;
	/**
	 * Get param name for select fields
	 * @var string
	 */
	public $selectFieldsKey = 'fields';
	/**
	 * Request LIMIT param name
	 * @var string
	 */
	public $limitKey;
	/**
	 * Request OFFSET param name
	 * @var string
	 */
	public $offsetKey;
	/**
	 * Model class envelope
	 * @var string
	 */
	private $_collectionEnvelope;
	/**
	 * Model class pagination envelope
	 * @var string
	 */
	private $_paginationEnvelope;
	/**
	 * Model class pagination envelope keys mapping
	 * @var array
	 */
	private $_paginationEnvelopeKeys;
	/**
	 * Pagination data from pagination envelope in GET request
	 * @var array
	 */
	private $_pagination;
	/**
	 * Array of fields to select from REST
	 * @var array
	 */
	private $_select = [];
	/**
	 * Conditions
	 * @var array
	 */
	private $_where;
	/**
	 * Query limit
	 * @var int
	 */
	private $_limit;
	/**
	 * Query offset
	 * @var int
	 */
	private $_offset;
	/**
	 * Flag Is this query is sub-query
	 * to prevent recursive requests
	 * for get enveloped pagination
	 * @see Query::count()
	 * @var bool
	 */
	private $_subQuery = false;


	/**
	 * Constructor. Really.
	 *
	 * @param ActiveRecord $modelClass
	 * @param array $config
	 */
	public function __construct($modelClass, $config = []) {
//		$modelClass::staticInit();
		$this->modelClass              = $modelClass;
		$this->_collectionEnvelope     = $modelClass::$collectionEnvelope;
		$this->_paginationEnvelope     = $modelClass::$paginationEnvelope;
		$this->_paginationEnvelopeKeys = $modelClass::$paginationEnvelopeKeys;
		$this->offsetKey               = $modelClass::$offsetKey;
		$this->limitKey                = $modelClass::$limitKey;

		$this->httpClient = new Client([
			'baseUrl'        => $this->getUrl(self::URL_API),
			'requestConfig'  => [
				'class'  => 'yii\httpclient\Request',
				'format' => Client::FORMAT_JSON
			],
			'responseConfig' => [
				'class'  => 'yii\httpclient\Response',
				'format' => Client::FORMAT_JSON
			]
		]);

		parent::__construct($config);
	}

	/**
	 * GET resource collection request
	 * @inheritdoc
	 */
	public function all() {
		return $this->populate(
			$this->request('get',
				$this->getUrl(self::URL_COLLECTION),
				[
					'query' => $this->buildQueryParams()
				]
			)
		);
	}

	/**
	 * GET resource element request
	 * @inheritdoc
	 */
	public function one() {
		$model = $this->populate(
			$this->request('get',
				$this->getUrl(self::URL_COLLECTION),
				[
					'query'         => $this->buildQueryParams(),
					$this->limitKey => 1
				]
			),
			false
		);

		return $model;
	}

	/**
	 * Get collection count
	 * If $this->_pagination isset (from get request before call this method) return count from it
	 * else execute HEAD request to collection and get count from X-Pagination-Total-Count(default) response header
	 * If header is empty and isset pagination envelope - do get collection request with limit 1 to get pagination data
	 * @see Query::$_subQuery
	 * @inheritdoc
	 */
	public function count() {
		if ($this->_pagination) {
			return isset($this->_pagination['totalCount']) ? (int) $this->_pagination['totalCount'] : 0;
		}

		if ($this->_subQuery) {
			return 0;
		}

		// try to get count by HEAD request
		$count = $this->request('head', $this->getUrl(self::URL_COLLECTION), [
			'query' => $this->buildQueryParams()
		])->headers->get($this->responseHeaders['totalCount']);

		// REST server not allow HEAD query and X-Total header is empty
		if ($count === '' && $this->_paginationEnvelope) {
			$query = clone $this;
			$query->setSubQueryFlag()->offset(0)->limit(1)->all();

			return $query->count();
		}

		return (int) $count;
	}

	/**
	 * POST request
	 * @inheritdoc
	 */
	public function create(ActiveRecord $model) {
		return $this->populate(
			$this->request('post', $this->getUrl(self::URL_COLLECTION), $model->getAttributes()),
			false
		);
	}

	/**
	 * PUT request
	 * @inheritdoc
	 */
	public function update(ActiveRecord $model) {
		return $this->populate(
			$this->request('put', $this->getUrl(self::URL_ELEMENT, $model->getPrimaryKey()), $model->getAttributes()),
			false
		);
	}

	/**
	 * @inheritdoc
	 */
	public function select(array $fields) {
		$this->_select = $fields;

		return $this;
	}

	/**
	 * @inheritdoc
	 */
	public function where(array $conditions) {
		$this->_where = $conditions;

		return $this;
	}

	/**
	 * @inheritdoc
	 */
	public function limit($limit) {
		$this->_limit = (int) $limit;

		return $this;
	}

	/**
	 * @inheritdoc
	 */
	public function offset($offset) {
		$this->_offset = (int) $offset;

		return $this;
	}

	/**
	 * HTTP request
	 *
	 * @param string $method
	 * @param string $url
	 * @param array $data
	 * @param array $headers
	 * @param array $options
	 *
	 * @return Response
	 */
	private function request($method, $url, array $data = [], array $headers = [], array $options = []) {
		try {
			$request = $this->httpClient->{$method}($url, $data, $headers, $options);
			/* @var $request Request */
			$response = $request->send();
		} catch (Exception $e) {
			return $this->throwServerError($e);
		} catch (\Exception $e) {
			return $this->throwServerError($e);
		}

		return $response;
	}

	/**
	 * Throw 500 error exception
	 *
	 * @param \Exception $e
	 *
	 * @throws ServerErrorHttpException
	 */
	private function throwServerError(\Exception $e) {
		$uri = (string) $this->httpClient->baseUrl;

		throw new ServerErrorHttpException(get_class($e).': url='.$uri.' '.$e->getMessage(), 500);
	}

	/**
	 * Unserialize and create models
	 *
	 * @param Response $response
	 * @param boolean $asCollection
	 *
	 * @return ActiveRecord|ActiveRecord[]
	 * @throws HttpException
	 */
	private function populate(Response $response, $asCollection = true) {
		$models     = [];
		$statusCode = $response->statusCode;
		$data       = $response->data;

		// errors
		if ($statusCode >= 400) {
			throw new HttpException(
				$statusCode,
				is_string($data) ? $data : $data['message'],
				$statusCode
			);
		}

		// array of objects or arrays - probably resource collection
		if (is_array($data)) {
			if (!ArrayHelper::isAssociative($data)) {
				$models = $this->createModels($data);
			} else {
				if ($asCollection) {
					if ($this->_collectionEnvelope) {
						$elements = ArrayHelper::remove($data, $this->_collectionEnvelope, []);
					} else {
						$elements = [];
					}
					if ($this->_paginationEnvelope && isset($data[$this->_paginationEnvelope])) {
						$this->setPagination(
							$this->getProps($data[$this->_paginationEnvelope])
						);
					}
					$models = $this->createModels($elements);
				} else {
					$models = $this->createModels([$data])[0];
				}
			}
		}

		return $models;
	}

	/**
	 * Create models from array of elements
	 *
	 * @param array $elements
	 *
	 * @return array
	 */
	private function createModels(array $elements) {
		$modelClass = $this->modelClass;
		$models     = [];
		foreach ($elements as $element) {
			$model    = new $modelClass(ArrayHelper::toArray($element));
			$models[] = $model;
		}

		return $models;
	}

	/**
	 * Pagination data setter
	 * If pagination data isset in GET request result
	 *
	 * @param array $pagination
	 *
	 * @return $this
	 */
	private function setPagination(array $pagination) {
		foreach ($this->_paginationEnvelopeKeys as $key => $name) {
			$this->_pagination[$key] = isset($pagination[$name])
				? $pagination[$name]
				: null;
		}

		return $this;
	}

	/**
	 * Get array of properties from object
	 *
	 * @param $object
	 *
	 * @return array
	 */
	private function getProps($object) {
		return is_object($object) ? get_object_vars($object) : $object;
	}

	/**
	 * Build query params
	 * @return array
	 */
	private function buildQueryParams() {
		$query = [];

		$this->_where = is_array($this->_where) ? $this->_where : [];
		foreach ($this->_where as $key => $val) {
			$query[$key] = is_numeric($val) ? (int) $val : $val;
		}

		if (count($this->_select)) {
			$query[$this->selectFieldsKey] = implode(',', $this->_select);
		}
		if ($this->_limit !== null) {
			$query[$this->limitKey] = $this->_limit;
		}
		if ($this->_offset !== null) {
			$query[$this->offsetKey] = $this->_offset;
		}

		return $query;
	}

	/**
	 * Get url to collection or element of resource
	 * with check base url trailing slash
	 *
	 * @param string $type api|collection|element
	 * @param string $id
	 *
	 * @return string
	 */
	private function getUrl($type = 'base', $id = null) {
		$modelClass = $this->modelClass;
		$collection = $modelClass::getResourceName();

		switch ($type) {
			case self::URL_API:
				return $this->trailingSlash($modelClass::getApiUrl());
				break;
			case self::URL_COLLECTION:
				return $this->trailingSlash($collection, false);
				break;
			case self::URL_ELEMENT:
				return $this->trailingSlash($collection).$this->trailingSlash($id, false);
				break;
		}

		return '';
	}

	/**
	 * Check trailing slash
	 * if $add - add trailing slash
	 * if not $add - remove trailing slash
	 *
	 * @param $string
	 * @param boolean $add
	 *
	 * @return string
	 */
	private function trailingSlash($string, $add = true) {

		return substr($string, -1) === '/'
			? ($add ? $string : substr($string, 0, strlen($string) - 1))
			: ($add ? $string.'/' : $string);
	}

	/**
	 * Mark query as subquery to prevent queries recursion
	 * @see count()
	 * @return ActiveQuery
	 */
	private function setSubQueryFlag() {
		$this->_subQuery = true;

		return $this;
	}
}