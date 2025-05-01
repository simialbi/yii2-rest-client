<?php

declare(strict_types=1);
/**
 * @package yii2-rest-client
 * @author Simon Karlen <simi.albi@outlook.com>
 */

namespace simialbi\yii2\rest\conditions;

use yii\db\conditions\ConjunctionCondition;
use yii\db\ExpressionInterface;

/**
 * @property \simialbi\yii2\rest\QueryBuilder $queryBuilder
 */
class ConjunctionConditionBuilder extends \yii\db\conditions\ConjunctionConditionBuilder
{
    use ConditionBuilderTrait;

    /**
     * @throws \Exception
     */
    public function build(ExpressionInterface $condition, array &$params = []): array
    {
        /** @var ConjunctionCondition $condition */
        $parts = $this->buildExpressionsFrom($condition, $params);

        if (empty($parts)) {
            return [];
        }

        if (count($parts) === 1) {
            return $parts;
        }

        return [
            $this->getOperator($condition->getOperator()) => $parts,
        ];
    }

    /**
     * Builds expressions, that are stored in $condition
     *
     * @param ExpressionInterface|ConjunctionCondition $condition the expression to be built.
     * @param array $params the binding parameters.
     *
     * @return string[]
     */
    private function buildExpressionsFrom(ExpressionInterface $condition, array &$params = []): array
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
