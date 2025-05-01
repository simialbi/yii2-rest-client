<?php

declare(strict_types=1);
/**
 * @package yii2-rest-client
 * @author Simon Karlen <simi.albi@outlook.com>
 */

namespace simialbi\yii2\rest\conditions;

use simialbi\yii2\rest\Query;
use yii\db\conditions\InCondition;
use yii\db\ExpressionInterface;
use yii\helpers\ArrayHelper;

/**
 * @property \simialbi\yii2\rest\QueryBuilder $queryBuilder
 */
class HashConditionBuilder extends \yii\db\conditions\HashConditionBuilder
{
    use ConditionBuilderTrait;

    public function build(ExpressionInterface $expression, array &$params = []): array
    {
        /** @var \yii\db\conditions\HashCondition $expression */

        $hash = $expression->getHash();
        $parts = [];
        foreach ($hash as $column => $value) {
            if (ArrayHelper::isTraversable($value) || $value instanceof Query) {
                // IN condition
                $parts[] = $this->queryBuilder->buildCondition(new InCondition($column, 'IN', $value), $params);
            } else {
                if ($value === null) {
                    $parts[] = [
                        $column => null,
                    ];
                } elseif ($value instanceof ExpressionInterface) {
                    $parts[] = [
                        $column => $this->queryBuilder->buildExpression($value, $params),
                    ];
                } else {
                    $phName = $this->queryBuilder->bindParam($value, $params);
                    $parts[] = [
                        $column => $phName,
                    ];
                }
            }
        }

        return count($parts) === 1 ? $parts[0] : $parts;
    }
}
