<?php
/**
 * Created by PhpStorm.
 * User: simialbi
 * Date: 12.10.2017
 * Time: 10:34
 */

namespace simialbi\yii2\rest;

use Yii;
use yii\base\InvalidArgumentException;
use yii\base\InvalidConfigException;
use yii\base\NotSupportedException;
use yii\db\ActiveQueryInterface;
use yii\db\BaseActiveRecord;
use yii\helpers\Inflector;
use yii\helpers\StringHelper;

/**
 * Class ActiveRecord
 */
class ActiveRecord extends BaseActiveRecord
{
    /**
     * @var array records that are related and where the data can be fetched by using join/joinWith
     */
    private $_relatedRecords = [];

    /**
     * Constructors.
     *
     * @param array $attributes the dynamic attributes (name-value pairs, or names) being defined
     * @param array $config the configuration array to be applied to this object.
     */
    public function __construct(array $attributes = [], $config = [])
    {
        $setOld = true;
        $keys = $this->primaryKey();
        foreach ($keys as $key) {
            if (!isset($attributes[$key])) {
                $setOld = false;
                break;
            }
        }
        foreach ($attributes as $name => $value) {
            if (is_int($name)) {
                $this->setAttribute($value, null);
            } else {
                $this->setAttribute($name, $value);
            }
        }
        if ($setOld) {
            $this->setOldAttributes($attributes);
        }
        parent::__construct($config);
    }

    /**
     * {@inheritdoc}
     * @throws InvalidConfigException
     */
    public static function populateRecord($record, $row)
    {
        /* @var $record static */
        parent::populateRecord($record, $row);
        $relatedRecords = $record->relatedRecords();
        foreach ($relatedRecords as $name) {
            if (isset($row[$name])) {
                $value = $row[$name];
                if ($record->canGetProperty($name)) {
                    $getter = 'get' . $name;
                    /* @var $relation ActiveQuery */
                    $relation = $record->$getter();
                    if ($relation instanceof ActiveQueryInterface) {
                        $model = $relation->modelClass;
                        /* @var $model ActiveRecord */
                        $models = $model::find()->populate($relation->multiple ? $value : [$value]);
                        $record->populateRelation($name, $relation->multiple ? $models : reset($models));
                    }
                }
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public static function primaryKey()
    {
        new InvalidConfigException('The primaryKey() method of RestClient ActiveRecord has to be implemented by child classes.');
    }

    /**
     * {@inheritdoc}
     */
    public static function instantiate($row)
    {
        return new static($row);
    }

    /**
     * TODO
     */
    public function relatedRecords()
    {
        return $this->_relatedRecords;
    }

    /**
     * {@inheritdoc}
     */
    public function setAttribute($name, $value)
    {
        try {
            parent::setAttribute($name, $value);
        } catch (InvalidArgumentException $e) {
            // do nothing
        }
    }

    /**
     * {@inheritdoc}
     *
     * @return ActiveQuery
     * @throws InvalidConfigException
     */
    public static function find($options = [])
    {
        $config = [
            'class' => 'simialbi\yii2\rest\ActiveQuery',
            'options' => $options
        ];

        /* @var $query ActiveQuery */
        $query = Yii::createObject($config, [get_called_class()]);

        return $query;
    }

    /**
     * @return null|Connection
     * @throws InvalidConfigException
     */
    public static function getDb()
    {
        $connection = Yii::$app->get(Connection::getDriverName());

        /* @var $connection Connection */
        return $connection;
    }

    /**
     * Declares the name of the url path associated with this AR class.
     *
     * By default this method returns the class name as the path by calling [[Inflector::camel2id()]].
     * For example:
     * `Customer` becomes `customer`, and `OrderItem` becomes `order-item`. You may override this method
     * if the path is not named after this convention.
     *
     * @return string the url path
     */
    public static function modelName()
    {
        return Inflector::pluralize(Inflector::camel2id(StringHelper::basename(get_called_class()), '-'));
    }


    /**
     * {@inheritdoc}
     * @throws InvalidConfigException
     */
    public function insert($runValidation = true, $attributes = null)
    {
        if ($runValidation && !$this->validate($attributes)) {
            Yii::info('Model not inserted due to validation error.', __METHOD__);

            return false;
        }

        return $this->insertInternal($attributes);
    }

    /**
     * Inserts an ActiveRecord.
     *
     * @param array $attributes list of attributes that need to be saved. Defaults to `null`,
     * meaning all attributes that are loaded from DB will be saved.
     *
     * @return boolean whether the record is inserted successfully.
     * @throws InvalidConfigException
     */
    protected function insertInternal($attributes)
    {
        if (!$this->beforeSave(true)) {
            return false;
        }
        $values = $this->getDirtyAttributes($attributes);
        if (false === ($data = static::getDb()->createCommand()->insert(static::modelName(), $values))) {
            return false;
        }
        foreach ($data as $name => $value) {
            $this->setAttribute($name, $value);
        }

        $changedAttributes = array_fill_keys(array_keys($values), null);
        $this->setOldAttributes($values);
        $this->afterSave(true, $changedAttributes);

        return true;
    }

    /**
     * {@inheritdoc}
     * @throws InvalidConfigException
     */
    public function update($runValidation = true, $attributeNames = null)
    {
        if ($runValidation && !$this->validate($attributeNames)) {
            Yii::info('Model not inserted due to validation error.', __METHOD__);

            return false;
        }

        return $this->updateInternal($attributeNames);
    }

    /**
     * {@inheritdoc}
     * @throws InvalidConfigException
     * @throws \yii\db\Exception
     */
    protected function updateInternal($attributes = null)
    {
        if (!$this->beforeSave(false)) {
            return false;
        }
        $values = $this->getDirtyAttributes($attributes);
        if (empty($values)) {
            $this->afterSave(false, $values);

            return 0;
        }

        $command = static::getDb()->createCommand();
        $rows = $command->update(static::modelName(), $values, $this->getOldPrimaryKey(false));

        $changedAttributes = [];
        foreach ($values as $name => $value) {
            $changedAttributes[$name] = $this->getOldAttribute($name);
            $this->setOldAttribute($name, $value);
        }
        $this->afterSave(false, $changedAttributes);

        return $rows;
    }


    /**
     * {@inheritdoc}
     * @throws InvalidConfigException
     */
    public function delete()
    {
        $result = false;
        if ($this->beforeDelete()) {
            $command = static::getDb()->createCommand();
            $result = $command->delete(static::modelName(), $this->getOldPrimaryKey());

            $this->setOldAttributes(null);
            $this->afterDelete();
        }

        return $result;
    }

    /**
     * {@inheritdoc}
     * @throws NotSupportedException
     */
    public function unlinkAll($name, $delete = false)
    {
        throw new NotSupportedException('unlinkAll() is not supported by RestClient, use unlink() instead.');
    }
}
