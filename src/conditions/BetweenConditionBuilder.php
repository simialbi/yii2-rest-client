<?php

declare(strict_types=1);
/**
 * @package yii2-rest-client
 * @author Simon Karlen <simi.albi@outlook.com>
 */

namespace simialbi\yii2\rest\conditions;

use yii\db\conditions\BetweenCondition;
use yii\db\ExpressionInterface;

/**
 * @property \simialbi\yii2\rest\QueryBuilder $queryBuilder
 */
class BetweenConditionBuilder extends \yii\db\conditions\BetweenConditionBuilder
{
    use ConditionBuilderTrait;

    public function build(ExpressionInterface $expression, array &$params = []): array
    {
        /** @var BetweenCondition $expression */
        $operator = $expression->getOperator();
        $column = $expression->getColumn();

        $phName1 = $this->createPlaceholder($expression->getIntervalStart(), $params);
        $phName2 = $this->createPlaceholder($expression->getIntervalEnd(), $params);

        if ($operator === 'BETWEEN') {
            return [
                $column => [
                    'gt' => $phName1,
                    'lt' => $phName2,
                ],
            ];
        } else {
            return $this->queryBuilder->buildCondition([
                'or',
                ['<', $column, $expression->getIntervalStart()],
                ['>', $column, $expression->getIntervalEnd()],
            ], $params);
        }
    }
}
