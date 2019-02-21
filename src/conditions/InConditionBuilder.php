<?php
/**
 * @package yii2-rest-client
 * @author Simon Karlen <simi.albi@outlook.com>
 */

namespace simialbi\yii2\rest\conditions;

use simialbi\yii2\rest\Query;
use yii\db\conditions\InCondition;
use yii\db\ExpressionInterface;

/**
 * {@inheritdoc}
 *
 * @property \simialbi\yii2\rest\QueryBuilder $queryBuilder
 */
class InConditionBuilder extends \yii\db\conditions\InConditionBuilder
{
    /**
     * {@inheritdoc}
     *
     * @return array
     */
    public function build(ExpressionInterface $expression, array &$params = [])
    {
        /* @var $expression InCondition */

        $operator = $expression->getOperator();
        $column = $expression->getColumn();
        $values = $expression->getValues();

        if ($column === []) {
            return [0 => 1];
        }

        if ($values instanceof Query) {
            // TODO
//            return $this->buildSubqueryInCondition($operator, $column, $values, $params);
            return [];
        }
        if ($column instanceof \Traversable || ((is_array($column) || $column instanceof \Countable) && count($column) > 1)) {
            // TODO
//            return $this->buildCompositeInCondition($operator, $column, $values, $params);
            return [];
        }

        if (is_array($column)) {
            $column = reset($column);
        }

        $sqlValues = $this->buildValues($expression, $values, $params);
        if (empty($sqlValues)) {
            return [0 => 1];
        }

        if (count($sqlValues) > 1) {
            $operator = $operator === 'IN' ? 'in' : 'nin';
            return [$column => [$operator => $sqlValues]];
        }

        return $operator === 'IN'
            ? [$column => reset($sqlValues)]
            : $this->queryBuilder->buildCondition(['not', [$column => reset($sqlValues)]], $params);
    }
}