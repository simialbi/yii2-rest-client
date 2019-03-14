<?php
/**
 * Created by PhpStorm.
 * User: simialbi
 * Date: 12.10.2017
 * Time: 10:34
 */

namespace simialbi\yii2\rest;

use yii\base\NotSupportedException;
use yii\db\Expression;
use yii\db\ExpressionInterface;
use yii\helpers\ArrayHelper;

/**
 * Class QueryBuilder builds an HiActiveResource query based on the specification given as a [[Query]] object.
 */
class QueryBuilder extends \yii\db\QueryBuilder
{
    /**
     * @var Connection the database connection.
     */
    public $db;

    /**
     * @var string the separator between different fragments of a SQL statement.
     * Defaults to an empty space. This is mainly used by [[build()]] when generating a SQL statement.
     */
    public $separator = ',';

    /**
     * @var array the abstract column types mapped to physical column types.
     * This is mainly used to support creating/modifying tables using DB-independent data type specifications.
     * Child classes should override this property to declare supported type mappings.
     */
    public $typeMap = [];

    /**
     * @var array map of query condition to builder methods.
     * These methods are used by [[buildCondition]] to build SQL conditions from array syntax.
     */
    protected $conditionBuilders = [
        'AND' => 'buildAndCondition',
        'IN' => 'buildInCondition'
    ];

    /**
     * QueryBuilder constructor.
     *
     * @param mixed $connection the database connection.
     * @param array $config name-value pairs that will be used to initialize the object properties
     */
    public function __construct($connection, array $config = [])
    {
        parent::__construct($connection, $config);
    }

    /**
     * Build query data
     *
     * @param Query $query
     * @param array $params
     *
     * @return array
     * @throws \yii\db\Exception
     * @throws NotSupportedException
     */
    public function build($query, $params = [])
    {
        $query = $query->prepare($this);

        $params = empty($params) ? $query->params : array_merge($params, $query->params);

        $clauses = [
            'fields' => $this->buildSelect($query->select, $params),
            'pathInfo' => $this->buildFrom($query->from, $params),
            'expand' => $this->buildJoin($query->join, $params),
            'filter' => $this->buildWhere($query->where, $params),
            'sort' => $this->buildOrderBy($query->orderBy)
        ];

        $clauses = array_merge($clauses, $this->buildLimit($query->limit, $query->offset));

        return [
            'modelClass' => ArrayHelper::getValue($query, 'modelClass', ''),
            'pathInfo' => ArrayHelper::remove($clauses, 'pathInfo'),
            'queryParams' => array_filter($clauses)
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function buildSelect($columns, &$params, $distinct = false, $selectOptions = null)
    {
        if (!empty($columns) && is_array($columns)) {
            return implode($this->separator, $columns);
        }

        return '';
    }

    /**
     * @param string $tables
     * @param array $params the binding parameters to be populated
     *
     * @return string the model name
     */
    public function buildFrom($tables, &$params)
    {
        if (!is_string($tables)) {
            return '';
        }

        return trim($tables);
    }

    /**
     * {@inheritdoc}
     */
    public function buildJoin($joins, &$params)
    {
        if (empty($joins)) {
            return '';
        }

        $expand = [];
        foreach ($joins as $i => $join) {
            if (empty($join)) {
                continue;
            }
            if (is_array($join)) {
                $expand[] = $join[1];
                continue;
            }
            $expand[] = $join;
        }

        return implode($this->separator, $expand);
    }

    /**
     * @param string|array $condition
     * @param array $params the binding parameters to be populated
     *
     * @return array the WHERE clause built from [[Query::$where]].
     * @throws NotSupportedException
     */
    public function buildWhere($condition, &$params)
    {
        $where = $this->buildCondition($condition, $params);

        return $where;
    }

    /**
     * {@inheritdoc}
     *
     * @return array
     */
    public function buildCondition($condition, &$params)
    {
        if ($condition instanceof Expression || empty($condition) || !is_array($condition)) {
            return [];
        }

        $condition = $this->createConditionFromArray($condition);
        /* @var $condition \yii\db\conditions\SimpleCondition */

        return $this->buildExpression($condition, $params);
    }

    /**
     * {@inheritdoc}
     *
     * @return array
     */
    public function buildExpression(ExpressionInterface $expression, &$params = [])
    {
        return (array)parent::buildExpression($expression, $params);
    }

    /**
     * {@inheritdoc}
     */
    protected function defaultExpressionBuilders()
    {
        return [
            'yii\db\Query' => 'yii\db\QueryExpressionBuilder',
            'yii\db\PdoValue' => 'yii\db\PdoValueBuilder',
            'yii\db\Expression' => 'yii\db\ExpressionBuilder',
            'yii\db\conditions\ConjunctionCondition' => 'simialbi\yii2\rest\conditions\ConjunctionConditionBuilder',
            'yii\db\conditions\NotCondition' => 'simialbi\yii2\rest\conditions\NotConditionBuilder',
            'yii\db\conditions\AndCondition' => 'simialbi\yii2\rest\conditions\ConjunctionConditionBuilder',
            'yii\db\conditions\OrCondition' => 'simialbi\yii2\rest\conditions\ConjunctionConditionBuilder',
            'yii\db\conditions\BetweenCondition' => 'simialbi\yii2\rest\conditions\BetweenConditionBuilder',
            'yii\db\conditions\InCondition' => 'simialbi\yii2\rest\conditions\InConditionBuilder',
            'yii\db\conditions\LikeCondition' => 'simialbi\yii2\rest\conditions\LikeConditionBuilder',
//            'yii\db\conditions\ExistsCondition' => 'yii\db\conditions\ExistsConditionBuilder',
            'yii\db\conditions\SimpleCondition' => 'simialbi\yii2\rest\conditions\SimpleConditionBuilder',
            'yii\db\conditions\HashCondition' => 'simialbi\yii2\rest\conditions\HashConditionBuilder',
//            'yii\db\conditions\BetweenColumnsCondition' => 'yii\db\conditions\BetweenColumnsConditionBuilder'
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function buildOrderBy($columns)
    {
        if (empty($columns)) {
            return '';
        }

        $orders = [];
        foreach ($columns as $name => $direction) {
            if ($direction instanceof Expression) {
                $orders[] = $direction->expression;
            } else {
                $orders[] = ($direction === SORT_DESC ? '-' : '') . $name;
            }
        }

        return implode($this->separator, $orders);
    }

    /**
     * {@inheritdoc}
     */
    public function buildLimit($limit, $offset)
    {
        $clauses = [];
        if ($this->hasLimit($limit)) {
            $clauses['per-page'] = (string)$limit;
        }
        if ($this->hasOffset($offset)) {
            $offset = intval((string)$offset);
            $clauses['page'] = ceil($offset / $limit) + 1;
        }

        return $clauses;
    }

    /**
     * {@inheritdoc}
     */
    public function bindParam($value, &$params)
    {
        return $value;
    }
}
