<?php
/**
 * Created by PhpStorm.
 * User: simialbi
 * Date: 12.10.2017
 * Time: 10:34
 */

namespace simialbi\yii2\rest;

use yii\base\InvalidConfigException;
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
    public function createCommand($db = null): Command
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
     * @throws InvalidConfigException
     */
    public function prepare($builder): Query
    {
        if (!empty($this->joinWith)) {
            $this->buildJoinWith();
            $this->joinWith = null;
        }

        if ($this->primaryModel === null) {
            // eager loading
            $query = Query::create($this);
        } else {
            // lazy loading of a relation
            $where = $this->where;

            if ($this->via instanceof self) {
                // via junction table
                $viaModels = $this->via->findJunctionRows([$this->primaryModel]);
                $this->filterByModels($viaModels);
            } elseif (is_array($this->via)) {
                // via relation
                /* @var $viaQuery ActiveQuery */
                list($viaName, $viaQuery) = $this->via;
                if ($viaQuery->multiple) {
                    if ($this->primaryModel->isRelationPopulated($viaName)) {
                        $viaModels = $this->primaryModel->$viaName;
                    } else {
                        $viaModels = $viaQuery->all();
                        $this->primaryModel->populateRelation($viaName, $viaModels);
                    }
                } else {
                    if ($this->primaryModel->isRelationPopulated($viaName)) {
                        $model = $this->primaryModel->$viaName;
                    } else {
                        $model = $viaQuery->one();
                        $this->primaryModel->populateRelation($viaName, $model);
                    }
                    $viaModels = $model === null ? [] : [$model];
                }
                $this->filterByModels($viaModels);
            } else {
                $this->filterByModels([$this->primaryModel]);
            }

            $query = Query::create($this);
            $this->andWhere($where);
        }

        return $query;
    }

    /**
     * Builds join with clauses
     */
    private function buildJoinWith()
    {
        $join = $this->join;
        $this->join = [];
        $model = new $this->modelClass();
        foreach ($this->joinWith as $with) {
            $this->joinWithRelations($model, $with);
            foreach ($with as $name => $callback) {
                $this->innerJoin(is_int($name) ? $callback : [$name => $callback]);
                unset($with[$name]);
            }
        }
        if (!empty($join)) {
            // append explicit join to joinWith()
            // https://github.com/yiisoft/yii2/issues/2880
            $this->join = empty($this->join) ? $join : array_merge($this->join, $join);
        }
    }

    /**
     * Modifies the current query by adding join fragments based on the given relations.
     * @param ActiveRecord $model the primary model
     * @param array $with the relations to be joined
     */
    protected function joinWithRelations(ActiveRecord $model, array $with)
    {
        foreach ($with as $name => $callback) {
            if (is_int($name)) {
                $name = $callback;
                $callback = null;
            }
            $primaryModel = $model;
            $parent = $this;
            if (!isset($relations[$name])) {
                $relations[$name] = $relation = $primaryModel->getRelation($name);
                /* @var $relation static */
                if ($callback !== null) {
                    call_user_func($callback, $relation);
                }
                if (!empty($relation->joinWith)) {
                    $relation->buildJoinWith();
                }
                $this->joinWithRelation($parent, $relation);
            }
        }
    }

    /**
     * Joins a parent query with a child query.
     * The current query object will be modified accordingly.
     *
     * @param ActiveQuery $parent
     * @param ActiveQuery $child
     */
    private function joinWithRelation(ActiveQuery $parent, ActiveQuery $child)
    {
        if (!empty($child->join)) {
            foreach ($child->join as $join) {
                $this->join[] = $join;
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function all($db = null): array
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
        if ($row !== false) {
            $models = $this->populate(isset($row[0]) ? $row : [$row]);

            return reset($models) ?: null;
        }

        return null;
    }


    /**
     * {@inheritdoc}
     * @throws InvalidConfigException
     */
    public function populate($rows): array
    {
        if (empty($rows)) {
            return [];
        }

        $models = $this->createModels($rows);
        if (!empty($this->join) && $this->indexBy === null) {
            $models = $this->removeDuplicatedModels($models);
        }
        if (!empty($this->with)) {
            $this->findWith($this->with, $models);
        }
        if (!$this->asArray) {
            foreach ($models as $model) {
                $model->afterFind();
            }
        } elseif ($this->indexBy !== null) {
            $models = ArrayHelper::index($models, $this->indexBy);
        }

        return $models;
    }

    /**
     * {@inheritDoc}
     */
    protected function createModels($rows): array
    {
        if ($this->asArray) {
            return $rows;
        } else {
            $models = [];
            /* @var $class ActiveRecord */
            $class = $this->modelClass;
            foreach ($rows as $row) {
                $model = $class::instantiate($row);
                /** @var $modelClass ActiveRecord */
                $modelClass = get_class($model);
                $modelClass::populateRecord($model, $row);
                if (!empty($this->join)) {
                    foreach ($this->join as $join) {
                        if (isset($join[1], $row[$join[1]])) {
                            $relation = $model->getRelation($join[1]);
                            $rows = (ArrayHelper::isAssociative($row[$join[1]])) ? [$row[$join[1]]] : $row[$join[1]];
                            $relations = $relation->populate($rows);
                            $model->populateRelation($join[1], $relation->multiple ? $relations : $relations[0]);
                        }
                    }
                }
                $models[] = $model;
            }
            return $models;
        }
    }

    /**
     * Removes duplicated models by checking their primary key values.
     * This method is mainly called when a join query is performed, which may cause duplicated rows being returned.
     *
     * @param array $models the models to be checked
     *
     * @return array the distinctive models
     * @throws InvalidConfigException if model primary key is empty
     */
    private function removeDuplicatedModels(array $models): array
    {
        $hash = [];
        /* @var $class ActiveRecord */
        $class = $this->modelClass;
        $pks = $class::primaryKey();

        if (count($pks) > 1) {
            // composite primary key
            foreach ($models as $i => $model) {
                $key = [];
                foreach ($pks as $pk) {
                    if (!isset($model[$pk])) {
                        // do not continue if the primary key is not part of the result set
                        break 2;
                    }
                    $key[] = $model[$pk];
                }
                $key = serialize($key);
                if (isset($hash[$key])) {
                    unset($models[$i]);
                } else {
                    $hash[$key] = true;
                }
            }
        } elseif (empty($pks)) {
            /** @var $class string */
            throw new InvalidConfigException("Primary key of '{$class}' can not be empty.");
        } else {
            // single column primary key
            $pk = reset($pks);
            foreach ($models as $i => $model) {
                if (!isset($model[$pk])) {
                    // do not continue if the primary key is not part of the result set
                    break;
                }
                $key = $model[$pk];
                if (isset($hash[$key])) {
                    unset($models[$i]);
                } elseif ($key !== null) {
                    $hash[$key] = true;
                }
            }
        }

        return array_values($models);
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
    public function joinWith($with): ActiveQuery
    {
        $this->joinWith[] = (array)$with;
        return $this;
    }
}
