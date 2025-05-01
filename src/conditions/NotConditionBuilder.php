<?php

declare(strict_types=1);
/**
 * @package yii2-rest-client
 * @author Simon Karlen <simi.albi@outlook.com>
 */

namespace simialbi\yii2\rest\conditions;

use yii\db\conditions\NotCondition;
use yii\db\ExpressionInterface;

/**
 * @property \simialbi\yii2\rest\QueryBuilder $queryBuilder
 */
class NotConditionBuilder extends \yii\db\conditions\NotConditionBuilder
{
    use ConditionBuilderTrait;

    public function build(ExpressionInterface $expression, array &$params = []): array
    {
        /** @var NotCondition $expression */
        $operand = $expression->getCondition();
        if (empty($operand)) {
            return [];
        }

        $expression = $this->queryBuilder->buildCondition($operand, $params);

        return [
            $this->getNegationOperator() => $expression,
        ];
    }

    protected function getNegationOperator(): string
    {
        return 'not';
    }
}
