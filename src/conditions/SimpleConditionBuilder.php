<?php
/**
 * @package yii2-rest-client
 * @author Simon Karlen <simi.albi@outlook.com>
 */

namespace simialbi\yii2\rest\conditions;

use yii\db\conditions\SimpleCondition;
use yii\db\ExpressionInterface;

/**
 * {@inheritdoc}
 *
 * @property \simialbi\yii2\rest\QueryBuilder $queryBuilder
 */
class SimpleConditionBuilder extends \yii\db\conditions\SimpleConditionBuilder
{
    use ConditionBuilderTrait;

    /**
     * {@inheritdoc}
     *
     * @return array
     */
    public function build(ExpressionInterface $expression, array &$params = []): array
    {
        /* @var $expression SimpleCondition */
        $operator = $expression->getOperator();
        $column = $expression->getColumn();
        $value = $expression->getValue();

        if ($value === null) {
            return [$column => [$this->getOperator($operator) => null]];
        }
        if ($value instanceof ExpressionInterface) {
            return [$column => [$this->getOperator($operator) => $this->queryBuilder->buildExpression($value, $params)]];
        }

        $phName = $this->queryBuilder->bindParam($value, $params);
        return [$column => [$this->getOperator($operator) => $phName]];
    }
}
