<?php
/**
 * Created by PhpStorm.
 * User: simialbi
 * Date: 12.10.2017
 * Time: 10:34
 */

namespace simialbi\yii2\rest;

use yii\base\InvalidConfigException;
use yii\data\ActiveDataProvider;
use yii\db\QueryInterface;

/**
 * Class RestDataProvider
 */
class RestDataProvider extends ActiveDataProvider
{
    /**
     * @var ActiveQuery the query that is used to fetch data models and [[totalCount]]
     * if it is not explicitly set.
     */
    public $query;

    /**
     * {@inheritdoc}
     * @throws InvalidConfigException
     * @throws \yii\db\Exception
     * @throws \yii\base\NotSupportedException
     */
    protected function prepareTotalCount()
    {
        if (!$this->query instanceof QueryInterface) {
            throw new InvalidConfigException('The "query" property must be an instance of a class that implements the QueryInterface e.g. yii\db\Query or its subclasses.');
        }

        return (int)$this->query->count();
    }
}
