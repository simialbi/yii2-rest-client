<?php
/**
 * Created by PhpStorm.
 * User: simialbi
 * Date: 12.10.2017
 * Time: 10:34
 */

namespace simialbi\yii2\rest;

use yii\base\InvalidConfigException;
use yii\base\NotSupportedException;
use yii\db\ActiveQueryInterface;
use yii\db\ActiveQueryTrait;
use yii\db\ActiveRelationTrait;
use yii\helpers\ArrayHelper;

/**
 * Class RestQuery
 */
class ActiveQuery extends Query implements ActiveQueryInterface
{
    use ActiveQueryTrait;
    use ActiveRelationTrait;

    /**
     * @var array|null a list of relations that this query should be joined with
     */
    public $joinWith = [];

    /**
     * Creates a DB command that can be used to execute this query.
     *
     * @param Connection $db the DB connection used to create the DB command.
     *                       If null, the DB connection returned by [[modelClass]] will be used.
     *
     * @return Command the created DB command instance.
     * @throws InvalidConfigException
     * @throws \yii\db\Exception
     * @throws \yii\base\NotSupportedException
     */
    public function createCommand($db = null)
    {
        /**
         * @var ActiveRecord $modelClass
         */
        $modelClass = $this->modelClass;

        if ($db === null) {
            $db = $modelClass::getDb();
        }

        if ($this->from === null) {
            $this->from($modelClass::modelName());
        }

        return parent::createCommand($db);
    }

    /**
     * {@inheritdoc}
     */
    public function all($db = null)
    {
        return parent::all($db);
    }

    /**
     * {@inheritdoc}
     * @throws InvalidConfigException
     */
    public function one($db = null)
    {
        $row = parent::one($db);
        if (!empty($row)) {
            $models = $this->populate(isset($row[0]) ? $row : [$row]);

            return reset($models) ?: null;
        }

        return null;
    }

    /**
     * {@inheritDoc}
     * @throws NotSupportedException
     */
    public function via($relationName, callable $callable = null)
    {
        throw new NotSupportedException('Via relations are not supported in rest applications');
    }

    /**
     * {@inheritdoc}
     * @throws InvalidConfigException
     */
    public function prepare($builder)
    {
        return Query::create($this);
    }

    /**
     * Joins with the specified relations.
     *
     * This method allows you to reuse existing relation definitions to perform JOIN queries.
     * Based on the definition of the specified relation(s), the method will append one or multiple
     * JOIN statements to the current query.
     *
     * @param string|array $with the relations to be joined. This can either be a string, representing a relation name or
     * an array with the following semantics:
     *
     * - Each array element represents a single relation.
     * - You may specify the relation name as the array key and provide an anonymous functions that
     *   can be used to modify the relation queries on-the-fly as the array value.
     * - If a relation query does not need modification, you may use the relation name as the array value.
     *
     * Sub-relations can also be specified, see [[with()]] for the syntax.
     *
     * In the following you find some examples:
     *
     * ```php
     * // find all orders that contain books, and eager loading "books"
     * Order::find()->joinWith('books')->all();
     * // find all orders, eager loading "books", and sort the orders and books by the book names.
     * Order::find()->joinWith([
     *     'books' => function (\simialbi\yii2\rest\ActiveQuery $query) {
     *         $query->orderBy('item.name');
     *     }
     * ])->all();
     * // find all orders that contain books of the category 'Science fiction', using the alias "b" for the books table
     * Order::find()->joinWith(['books b'])->where(['b.category' => 'Science fiction'])->all();
     * ```
     *
     * @return $this the query object itself
     */
    public function joinWith($with)
    {
        if (is_array($with)) {
            $this->join = array_merge((array)$this->join, $with);
        } else {
            $this->join[] = $with;
        }

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function with()
    {
        $this->joinWith(func_get_args());

        return $this;
    }

    /**
     * {@inheritdoc}
     * @throws InvalidConfigException
     */
    public function populate($rows)
    {
        if (empty($rows)) {
            return [];
        }

        if ($this->asArray) {
            if ($this->indexBy) {
                return ArrayHelper::index($rows, $this->indexBy);
            }

            return $rows;
        }

        $models = $this->createModels($rows);
        foreach ($models as $model) {
            $model->afterFind();
        }

        return $models;
    }

    /**
     * {@inheritDoc}
     */
    protected function createModels($rows)
    {
        $models = [];
        /** @var ActiveRecord $class */
        $class = $this->modelClass;
        $namespace = null;
        foreach ($rows as $row) {
            $model = $class::instantiate($row);
            $relations = $model->getRelations();
            /** @var ActiveRecord $modelClass */
            $modelClass = get_class($model);
            $modelClass::populateRecord($model, $row);
            $models[] = $model;
            if (is_array($this->join)) {
                foreach ($this->join as $join) {
                    $relationRows = ArrayHelper::remove($row, $join, []);
                    if (empty($relationRows)) {
                        continue;
                    }
                    if (isset($relations[$join])) {
                        $relationClass = $relations[$join];
                        if (!class_exists($relationClass)) {
                            if (null === $namespace && strpos($relationClass, '\\') === false) {
                                $r = new \ReflectionClass($this->modelClass);
                                $namespace = $r->getNamespaceName();
                            }

                            if (class_exists($namespace . '\\' . $relationClass)) {
                                $relationClass = $namespace . '\\' . $relationClass;
                            }
                        }
                        if (class_exists($relationClass)) {
                            /** @var ActiveRecord $relationClass */
                            if (ArrayHelper::isAssociative($relationRows)) {
                                $relationModel = $relationClass::instantiate($relationRows);
                                $relationClass::populateRecord($relationModel, $relationRows);
                                $model->populateRelation($join, $relationModel);
                            } else {
                                $populatedRows = [];
                                foreach ($relationRows as $relationRow) {
                                    $relationModel = $relationClass::instantiate($relationRow);
                                    $relationClass::populateRecord($relationModel, $relationRow);
                                    $populatedRows[] = $relationModel;
                                }
                                $model->populateRelation($join, $populatedRows);
                            }
                        }
                    } else {
                        $model->populateRelation($join, ArrayHelper::remove($row, $join, []));
                    }
                }
            }
        }
        return $models;
    }
}
