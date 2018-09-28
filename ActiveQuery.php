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

/**
 * Class RestQuery
 */
class ActiveQuery extends Query implements ActiveQueryInterface {
	use ActiveQueryTrait;
	use ActiveRelationTrait;

	/**
	 * @var array options for search
	 */
	public $options = [];

	/**
	 * Constructor.
	 *
	 * @param string $modelClass the model class associated with this query
	 * @param array $config configurations to be applied to the newly created query object
	 */
	public function __construct($modelClass, $config = []) {
		$this->modelClass = $modelClass;
		parent::__construct($config);
	}


	/**
	 * Creates a DB command that can be used to execute this query.
	 *
	 * @param Connection $db the DB connection used to create the DB command.
	 *                       If null, the DB connection returned by [[modelClass]] will be used.
	 *
	 * @return Command the created DB command instance.
	 */
	public function createCommand($db = null) {
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

//		if ($this->searchModel === null) {
//			$this->searchModel = mb_substr(mb_strrchr($this->modelClass, '\\'), 1).'Search';
//		}

		return parent::createCommand($db);
	}

	/**
	 * @inheritdoc
	 */
	public function all($db = null) {
		return parent::all($db);
	}

	/**
	 * @inheritdoc
	 */
	public function populate($rows) {
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
		}

		return $models;
	}

	/**
	 * Removes duplicated models by checking their primary key values.
	 * This method is mainly called when a join query is performed, which may cause duplicated rows being returned.
	 *
	 * @param array $models the models to be checked
	 *
	 * @throws InvalidConfigException if model primary key is empty
	 * @return array the distinctive models
	 */
	private function removeDuplicatedModels($models) {
		$hash = [];
		/* @var $class ActiveRecord */
		$class = $this->modelClass;
		$pks   = $class::primaryKey();

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
	 * @inheritdoc
	 */
	public function one($db = null) {
		$row = parent::one($db);
		if ($row !== false && !empty($row)) {
			$models = $this->populate(isset($row[0]) ? $row : [$row]);

			return reset($models) ?: null;
		}

		return null;
	}

    public function prepare($builder)
    {
        if ($this->primaryModel === null) {
            // eager loading
//            $query = $this;
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
                    $viaModels = $viaQuery->all();
                    $this->primaryModel->populateRelation($viaName, $viaModels);
                } else {
                    $model = $viaQuery->one();
                    $this->primaryModel->populateRelation($viaName, $model);
                    $viaModels = $model === null ? [] : [$model];
                }
                $this->filterByModels($viaModels);
            } else {
                $this->filterByModels([$this->primaryModel]);
            }
        }

        return $this;
    }

}
