<?php

namespace simialbi\yii2\rest;

/**
 * Interface ModelInterface
 * 
 * @package chsergey\rest
 */
interface ActiveRecordInterface {
	/**
	 * Scenario for POST request
	 */
	const SCENARIO_CREATE = 'create';
	/**
	 * Scenario for PUT request
	 */
	const SCENARIO_UPDATE = 'update';

	/**
	 * Get REST API url
	 * @return string
	 */
	public static function getApiUrl();

	/**
	 * Get REST resource name
	 * @return string
	 */
	public static function getResourceName();

	/**
	 * Returns the primary key **name(s)** for this class.
	 *
	 * Note that an array should be returned even when the record only has a single primary key.
	 *
	 * For the primary key **value** see [[getPrimaryKey()]] instead.
	 *
	 * @return string[] the primary key name(s) for this AR class.
	 */
	public static function primaryKey();

	/**
	 * Create instance of QueryInterface
	 * @return ActiveActiveQuery
	 */
	public static function find();

	/**
	 * Perform request to get collection of resource
	 * with filter by conditions
	 *
	 * @param mixed $condition primary key value or a set of column values
	 *
	 * @return ActiveRecord[]
	 */
	public static function findAll(array $condition);

	/**
	 * Find record by primary key
	 * @see getPrimaryKey()
	 *
	 * @param mixed $condition primary key value or a set of column values
	 *
	 * @return ActiveRecord
	 */
	public static function findOne($condition);

	/**
	 * Returns the primary key value(s).
	 * @param bool $asArray whether to return the primary key value as an array. If true,
	 * the return value will be an array with attribute names as keys and attribute values as values.
	 * Note that for composite primary keys, an array will always be returned regardless of this parameter value.
	 * @return mixed the primary key value. An array (attribute name => attribute value) is returned if the primary key
	 * is composite or `$asArray` is true. A string is returned otherwise (`null` will be returned if
	 * the key value is `null`).
	 */
	public function getPrimaryKey($asArray = false);


	/**
	 * Returns the list of all attribute names of the record.
	 * @return array list of attribute names.
	 */
	public function attributes();

	/**
	 * Returns the named attribute value.
	 * If this record is the result of a query and the attribute is not loaded,
	 * `null` will be returned.
	 * @param string $name the attribute name
	 * @return mixed the attribute value. `null` if the attribute is not set or does not exist.
	 * @see hasAttribute()
	 */
	public function getAttribute($name);

	/**
	 * Sets the named attribute value.
	 * @param string $name the attribute name.
	 * @param mixed $value the attribute value.
	 * @see hasAttribute()
	 */
	public function setAttribute($name, $value);

	/**
	 * Returns a value indicating whether the record has an attribute with the specified name.
	 * @param string $name the name of the attribute
	 * @return bool whether the record has an attribute with the specified name.
	 */
	public function hasAttribute($name);

	/**
	 * Save model
	 * @return bool
	 */
	public function save();
}