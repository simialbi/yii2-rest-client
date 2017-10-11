<?php

namespace simialbi\yii2\rest;

use yii\base\InvalidConfigException;
use yii\base\InvalidParamException;
use yii\base\Model;
use yii\helpers\ArrayHelper;
use yii\helpers\Inflector;
use yii\helpers\StringHelper;

/**
 * Class Model
 *
 * @package chsergey\rest
 */
abstract class ActiveRecord extends Model implements ActiveRecordInterface {
	/**
	 * Url to REST API without resource name with trailing slash
	 * Resource name will be added as postfix
	 * @var string
	 */
	public static $apiUrl;
	/**
	 * REST response data envelope, i.e. 'data'
	 *
	 * @var string
	 */
	public static $collectionEnvelope;
	/**
	 * REST response pagination envelope, i.e. 'pagination'
	 * @var array
	 */
	public static $paginationEnvelope;
	/**
	 * REST response pagination envelope keys mapping
	 * @var array
	 */
	public static $paginationEnvelopeKeys = [
		'totalCount'   => 'total',
		'pageCount'    => 'pages',
		'currPage'     => 'offset',
		'perPageCount' => 'limit',
		'links'        => 'links',
	];
	/**
	 * Request LIMIT param name
	 * @var string
	 */
	public static $limitKey = 'per-page';
	/**
	 * Request OFFSET param name
	 * @var string
	 */
	public static $offsetKey = 'page';

	/**
	 * @var array primary key name(s)
	 */
	protected static $_primaryKey = [];

	/**
	 * @var array allowed attributes
	 */
	protected static $_validAttributes = [];

	/**
	 * Model errors
	 * @var array
	 */
	protected $_errors = [];

	/**
	 * Model attributes with values
	 * @var array
	 */
	private $_attributes = [];

	/**
	 * @inheritdoc
	 * @return string[]
	 */
	public static function primaryKey() {
		return static::$_primaryKey;
	}

	/**
	 * @inheritdoc
	 * @return string
	 */
	public static function getApiUrl() {
		return static::$apiUrl;
	}

	/**
	 * @inheritdoc
	 * @return string
	 */
	public static function getResourceName() {
		return Inflector::camel2id(StringHelper::basename(get_called_class()), '_');
	}

	/**
	 * @inheritdoc
	 * @throws \yii\base\InvalidConfigException
	 */
	public static function find() {
		return \Yii::createObject(ActiveActiveQuery::className(), [get_called_class()]);
	}

	/**
	 * @inheritdoc
	 */
	public static function findAll(array $condition) {
		return static::findByCondition($condition)->all();
	}

	/**
	 * @inheritdoc
	 */
	public static function findOne($condition) {
		return static::findByCondition($condition)->all();
	}

	/**
	 * Finds ActiveRecord instance(s) by the given condition.
	 * This method is internally called by [[findOne()]] and [[findAll()]].
	 *
	 * @param mixed $condition please refer to [[findOne()]] for the explanation of this parameter
	 *
	 * @return ActiveQueryInterface the newly created [[ActiveQueryInterface|ActiveQuery]] instance.
	 * @throws InvalidConfigException if there is no primary key defined
	 * @internal
	 */
	protected static function findByCondition($condition) {
		$query = static::find();

		if (!ArrayHelper::isAssociative($condition)) {
			// query by primary key
			$primaryKey = static::primaryKey();
			if (isset($primaryKey[0])) {
				$condition = [$primaryKey[0] => $condition];
			} else {
				throw new InvalidConfigException('"'.get_called_class().'" must have a primary key.');
			}
		}

		return $query->andWhere($condition);
	}

	/**
	 * Constructor.
	 * The default implementation does two things:
	 *
	 * - Initializes the object with the given configuration `$config`.
	 * - Call [[init()]].
	 *
	 * If this method is overridden in a child class, it is recommended that
	 *
	 * - the last parameter of the constructor is a configuration array, like `$config` here.
	 * - call the parent implementation at the end of the constructor.
	 *
	 * @param array $attributes name-value pairs initial attribute values
	 * @param array $config name-value pairs that will be used to initialize the object properties
	 */
	public function __construct(array $attributes = [], array $config = []) {
		foreach ($attributes as $key => $value) {
			if ($this->hasAttribute($key)) {
				$this->setAttribute($key, $value);
			}
		}
		parent::__construct($config);
	}

	/**
	 * @inheritdoc
	 */
	public function getPrimaryKey($asArray = false) {
		$keys = $this->primaryKey();
		if (!$asArray && count($keys) === 1) {
			return isset($this->_attributes[$keys[0]]) ? $this->_attributes[$keys[0]] : null;
		} else {
			$values = [];
			foreach ($keys as $name) {
				$values[$name] = isset($this->_attributes[$name]) ? $this->_attributes[$name] : null;
			}

			return $values;
		}
	}

	/**
	 * @inheritdoc
	 */
	public function save() {
		if (static::SCENARIO_CREATE === $this->getScenario()) {
			return static::find()->create($this);
		}

		if (static::SCENARIO_UPDATE === $this->getScenario()) {
			return static::find()->update($this);
		}

		return false;
	}

	/**
	 * @inheritdoc
	 */
	public function attributes() {
		return static::$_validAttributes;
	}

	/**
	 * Returns the named attribute value.
	 * If this record is the result of a query and the attribute is not loaded,
	 * `null` will be returned.
	 *
	 * @param string $name the attribute name
	 *
	 * @return mixed the attribute value. `null` if the attribute is not set or does not exist.
	 * @see hasAttribute()
	 */
	public function getAttribute($name) {
		return isset($this->_attributes[$name]) ? $this->_attributes[$name] : null;
	}

	/**
	 * Sets the named attribute value.
	 *
	 * @param string $name the attribute name
	 * @param mixed $value the attribute value.
	 *
	 * @throws InvalidParamException if the named attribute does not exist.
	 * @see hasAttribute()
	 */
	public function setAttribute($name, $value) {
		if ($this->hasAttribute($name)) {
			$this->_attributes[$name] = $value;
		} else {
			throw new InvalidParamException(get_class($this).' has no attribute named "'.$name.'".');
		}
	}

	/**
	 * Returns a value indicating whether the model has an attribute with the specified name.
	 *
	 * @param string $name the name of the attribute
	 *
	 * @return bool whether the model has an attribute with the specified name.
	 */
	public function hasAttribute($name) {
		return isset($this->_attributes[$name]) || in_array($name, $this->attributes(), true);
	}

	/**
	 * PHP getter magic method.
	 * This method is overridden so that attributes and related objects can be accessed like properties.
	 *
	 * @param string $name property name
	 *
	 * @throws \yii\base\InvalidParamException if relation name is wrong
	 * @return mixed property value
	 * @see getAttribute()
	 */
	public function __get($name) {
		if (isset($this->_attributes[$name]) || array_key_exists($name, $this->_attributes)) {
			return $this->_attributes[$name];
		} elseif ($this->hasAttribute($name)) {
			return null;
		} else {
			return parent::__get($name);
		}
	}

	/**
	 * PHP setter magic method.
	 * This method is overridden so that AR attributes can be accessed like properties.
	 *
	 * @param string $name property name
	 * @param mixed $value property value
	 */
	public function __set($name, $value) {
		if ($this->hasAttribute($name)) {
			$this->_attributes[$name] = $value;
		} else {
			parent::__set($name, $value);
		}
	}

	/**
	 * Checks if a property value is null.
	 * This method overrides the parent implementation by checking if the named attribute is `null` or not.
	 *
	 * @param string $name the property name or the event name
	 *
	 * @return bool whether the property value is null
	 */
	public function __isset($name) {
		try {
			return $this->__get($name) !== null;
		} catch (\Exception $e) {
			return false;
		}
	}

	/**
	 * Sets a component property to be null.
	 * This method overrides the parent implementation by clearing
	 * the specified attribute value.
	 *
	 * @param string $name the property name or the event name
	 */
	public function __unset($name) {
		if ($this->hasAttribute($name)) {
			unset($this->_attributes[$name]);
		} else {
			parent::__unset($name);
		}
	}
}