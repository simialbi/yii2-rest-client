<?php
/**
 * @package yii2-rest-client
 * @author Simon Karlen <simi.albi@gmail.com>
 */

namespace simialbi\yii2\rest\conditions;


use yii\db\conditions\ConjunctionCondition;
use yii\db\ExpressionInterface;

/**
 * {@inheritdoc}
 *
 * @property \simialbi\yii2\rest\QueryBuilder $queryBuilder
 */
class ConjunctionConditionBuilder extends \yii\db\conditions\ConjunctionConditionBuilder
{
    /**
     * {@inheritdoc}
     *
     * @return array
     */
    public function build(ExpressionInterface $condition, array &$params = [])
    {
        /* @var $condition ConjunctionCondition */
        $parts = $this->buildExpressionsFrom($condition, $params);

        if (empty($parts)) {
            return [];
        }

        if (count($parts) === 1) {
            return $parts;
        }

        return [$condition->getOperator() => $parts];
    }

    /**
     * Builds expressions, that are stored in $condition
     *
     * @param ExpressionInterface|ConjunctionCondition $condition the expression to be built.
     * @param array $params the binding parameters.
     * @return string[]
     */
    private function buildExpressionsFrom(ExpressionInterface $condition, &$params = [])
    {
        $parts = [];
        foreach ($condition->getExpressions() as $condition) {
            if (is_array($condition)) {
                $condition = $this->queryBuilder->buildCondition($condition, $params);
            }
            if ($condition instanceof ExpressionInterface) {
                $condition = $this->queryBuilder->buildExpression($condition, $params);
            }
            if ($condition !== '') {
                $parts[] = $condition;
            }
        }

        return $parts;
    }
}