<?php

namespace simialbi\yii2\rest;

/**
 * Interface QueryInterface
 * Query to REST interface
 * 
 * @package chsergey\rest
 */
interface ActiveQueryInterface {
	/**
	 * api URL
	 */
	const URL_API = 'api';
	/**
	 * collection URL
	 */
	const URL_COLLECTION = 'collection';
	/**
	 * element URL
	 */
	const URL_ELEMENT = 'element';


	/**
	 * GET request to collection
	 * @return ActiveRecord[]
	 */
	public function all();

	/**
	 * OPTIONS request to collection
	 * @return int
	 */
	public function count();

	/**
	 * GET request to resource element by id
	 * @return ActiveRecord
	 */
	public function one();

	/**
	 * POST request
	 *
	 * @param ActiveRecord $model
	 *
	 * @return ActiveRecord
	 * @internal param Model $payload
	 */
	public function create(ActiveRecord $model);

	/**
	 * PUT request
	 * @param ActiveRecord $model
	 * @return ActiveRecord
	 * @internal param Model $payload
	 */
	public function update(ActiveRecord $model);

	/**
	 * Set fields to select
	 *
	 * @param array $fields
	 *
	 * @return ActiveActiveQuery
	 */
	public function select(array $fields);

	/**
	 * Add conditions to filter in request to collection
	 *
	 * @param array $conditions
	 *
	 * @return ActiveActiveQuery
	 */
	public function where(array $conditions);

	/**
	 * Set limit to request to collection
	 *
	 * @param int $limit
	 *
	 * @return ActiveActiveQuery
	 */
	public function limit($limit);

	/**
	 * Set offset to request to collection
	 *
	 * @param int $offset
	 *
	 * @return ActiveActiveQuery
	 */
	public function offset($offset);
}