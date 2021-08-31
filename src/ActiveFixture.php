<?php
/**
 * @package yii2-rest-client
 * @author Simon Karlen <simi.albi@outlook.com>
 * @copyright Copyright © 2019 Simon Karlen
 */

namespace simialbi\yii2\rest;

use yii\base\InvalidConfigException;
use yii\helpers\Inflector;
use yii\helpers\StringHelper;
use yii\test\BaseActiveFixture;

class ActiveFixture extends BaseActiveFixture
{
    /**
     * @var Connection|array|string the DB connection object or the application component ID of the DB connection.
     * After the DbFixture object is created, if you want to change this property, you should only assign it
     * with a DB connection object.
     * Starting from version 2.0.2, this can also be a configuration array for creating the object.
     */
    public $db = 'rest';
    /**
     * @var string the name of the model that this fixture is about. If this property is not set,
     * the model name will be determined via [[modelClass]].
     * @see modelClass
     */
    public $modelName;

    /**
     * @var \yii\db\ActiveRecord[] the loaded AR models
     */
    private $_models = [];


    /**
     * {@inheritDoc}
     *
     * @throws InvalidConfigException
     */
    public function init()
    {
        parent::init();
        if ($this->modelClass === null && $this->modelName === null) {
            throw new InvalidConfigException('Either "modelClass" or "modelName" must be set.');
        }
        if ($this->modelName === null) {
            $this->modelName = Inflector::camel2id(StringHelper::basename($this->modelClass), '-');
        }
    }

    /**
     * {@inheritDoc}
     *
     * @throws InvalidConfigException
     * @throws \ReflectionException
     */
    public function load()
    {
        $this->data = [];
        foreach ($this->getData() as $alias => $row) {
            $this->data[$alias] = $row;
        }
    }

    /**
     * {@inheritDoc}
     *
     * @throws InvalidConfigException
     * @throws \ReflectionException
     */
    protected function getData(): array
    {
        if ($this->dataFile === null) {
            if ($this->dataDirectory !== null) {
                $dataFile = $this->modelName . '.php';
            } else {
                $class = new \ReflectionClass($this);
                $dataFile = dirname($class->getFileName()) . '/data/' . $this->modelName . '.php';
            }

            return $this->loadData($dataFile, false);
        }
        return parent::getData();
    }

    /**
     * {@inheritDoc}
     */
    public function getModel($name)
    {
        if (!isset($this->data[$name])) {
            return null;
        }
        if (array_key_exists($name, $this->_models)) {
            return $this->_models[$name];
        }

        if ($this->modelClass === null) {
            throw new InvalidConfigException('The "modelClass" property must be set.');
        }
        $row = $this->data[$name];
        /* @var $modelClass ActiveRecord */
        $modelClass = $this->modelClass;
        $keys = [];
        foreach ($modelClass::primaryKey() as $key) {
            $keys[$key] = isset($row[$key]) ? $row[$key] : null;
        }

        /* @var $model ActiveRecord */
        $model = new $modelClass();
        $model->setOldAttributes($row);
        $model->setAttributes($row, false);

        return $this->_models[$name] = $model;
    }
}
