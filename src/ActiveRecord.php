<?php
/**
 * Created by PhpStorm.
 * User: simialbi
 * Date: 12.10.2017
 * Time: 10:34
 */

namespace simialbi\yii2\rest;

use Yii;
use yii\base\InvalidConfigException;
use yii\base\NotSupportedException;
use yii\db\BaseActiveRecord;
use yii\helpers\Inflector;
use yii\helpers\StringHelper;

/**
 * Class ActiveRecord
 */
class ActiveRecord extends BaseActiveRecord
{
    /**
     * @var array
     */
    private $_attributeFields = [];

    /**
     * @return array
     * @throws \ReflectionException
     */
    public function attributes()
    {
        if (empty($this->_attributeFields)) {
            $regex = '#^@property(?:-(read|write))?(?:\s+([^\s]+))?\s+\$([a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*)#';
            $typeRegex = '#^(bool(ean)?|int(eger)?|float|double|string|array)$#';
            $reflection = new \ReflectionClass($this);
            $docLines = preg_split('~\R~u', $reflection->getDocComment());
            foreach ($docLines as $docLine) {
                $matches = [];
                $docLine = ltrim($docLine, "\t* ");
                if (preg_match($regex, $docLine, $matches) && isset($matches[3])) {
                    if ($matches[1] === 'read' || (!empty($matches[2]) && !preg_match($typeRegex, $matches[2]))) {
                        continue;
                    }
                    $this->_attributeFields[] = $matches[3];
                }
            }
        }

        return $this->_attributeFields;
    }

    /**
     * {@inheritdoc}
     * @throws InvalidConfigException
     */
    public static function primaryKey()
    {
        throw new InvalidConfigException('The primaryKey() method of RestClient ActiveRecord has to be implemented by child classes.');
    }

    /**
     * {@inheritdoc}
     *
     * @return ActiveQuery
     * @throws InvalidConfigException
     */
    public static function find()
    {
        /* @var $query ActiveQuery */
        $query = Yii::createObject(ActiveQuery::class, [get_called_class()]);

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
     * @throws InvalidConfigException
     */
    public static function modelName()
    {
        $path = Inflector::camel2id(StringHelper::basename(get_called_class()), '-');
        return static::getDb()->usePluralisation ? Inflector::pluralize($path) : $path;
    }


    /**
     * {@inheritdoc}
     * @throws InvalidConfigException
     * @throws Exception
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
     * @throws Exception
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
     * @throws \yii\httpclient\Exception
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

    /**
     * {@inheritDoc}
     * @return \simialbi\yii2\rest\ActiveQuery|\yii\db\ActiveQuery|\yii\db\ActiveQueryInterface
     */
    public function hasOne($class, $link)
    {
        return parent::hasOne($class, $link);
    }

    /**
     * {@inheritDoc}
     * @return \simialbi\yii2\rest\ActiveQuery|\yii\db\ActiveQuery|\yii\db\ActiveQueryInterface
     */
    public function hasMany($class, $link)
    {
        return parent::hasMany($class, $link);
    }
}
