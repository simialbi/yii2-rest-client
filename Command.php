<?php
/**
 * Created by PhpStorm.
 * User: simialbi
 * Date: 12.10.2017
 * Time: 10:34
 */

namespace simialbi\yii2\rest;

use yii\base\Component;
use yii\helpers\Inflector;

/**
 * Class Command class implements the API for accessing REST API.
 * @package apexwire\restclient
 */
class Command extends Component {
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
	 * @return mixed
	 */
	public function queryAll() {
		return $this->queryInternal();
	}

	/**
	 * @return mixed
	 */
	public function queryOne() {
		/* @var $class ActiveRecord */
		$class = $this->modelClass;

		if (!empty($class) && class_exists($class)) {
			$pks = $class::primaryKey();

			if (count($pks) === 1 && isset($this->queryParams['filter'])) {
				$primaryKey = current($pks);
				if (isset($this->queryParams['filter'][$primaryKey])) {
					$this->pathInfo .= '/'.$this->queryParams['filter'][$primaryKey];
				}
			}
		}

		return $this->queryInternal();
	}

	/**
	 * Performs the actual get statment
	 *
	 * @param string $method
	 *
	 * @return mixed
	 */
	protected function queryInternal($method = 'get') {
		if (strpos($this->pathInfo, '/') === false) {
			$this->pathInfo = Inflector::pluralize($this->pathInfo);
		}

		return $this->db->$method($this->pathInfo, $this->queryParams);
	}

	/**
	 * Make request and check for error.
	 *
	 * @param string $method
	 *
	 * @return mixed
	 */
	public function execute($method = 'get') {
		return $this->queryInternal($method);
	}

	/**
	 * Creates a new record
	 *
	 * @param string $model
	 * @param array $columns
	 *
	 * @return mixed
	 */
	public function insert($model, $columns) {
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
	 */
	public function update($model, $data = [], $id = null) {
		$this->pathInfo = $model;
		if ($id) {
			$this->pathInfo .= '/'.$id;
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
	 */
	public function delete($model, $id = null) {
		$this->pathInfo = $model;
		if ($id) {
			$this->pathInfo .= '/'.$id;
		}

		return $this->db->delete($this->pathInfo);
	}
}
