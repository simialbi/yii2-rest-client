<?php
/**
 * @package yii2-rest-client
 * @author Simon Karlen <simi.albi@outlook.com>
 */

namespace simialbi\yii2\rest\conditions;

use yii\db\conditions\LikeCondition;
use yii\db\ExpressionInterface;

/**
 * {@inheritdoc}
 *
 * @property \simialbi\yii2\rest\QueryBuilder $queryBuilder
 */
class LikeConditionBuilder extends \yii\db\conditions\LikeConditionBuilder
{
    use ConditionBuilderTrait;

    /**
     * {@inheritdoc}
     *
     * @return array
     */
    public function build(ExpressionInterface $expression, array &$params = [])
    {
        /* @var $expression LikeCondition */
        $operator = $expression->getOperator();
        $column = $expression->getColumn();
        $values = $expression->getValue();
//        $escape = $expression->getEscapingReplacements();
//        if ($escape === null || $escape === []) {
//            $escape = $this->escapingReplacements;
//        }

        list($andor, $not,) = $this->parseOperator($operator);

        if (!is_array($values)) {
            $values = [$values];
        }

        if (empty($values)) {
            return $not ? [] : $this->queryBuilder->buildCondition([0 => 1], $params);
        }

        $parts = [];
        foreach ($values as $value) {
            if ($value instanceof ExpressionInterface) {
                $phName = $this->queryBuilder->buildExpression($value, $params);
            } else {
                $phName = $this->queryBuilder->bindParam($value, $params);
            }
            $parts[] = [$column => ['like' => is_array($phName) ? reset($phName) : $phName]];
        }

        if (count($parts) === 1) {
            return reset($parts);
        }

        array_unshift($parts, $andor);

        return $this->queryBuilder->buildCondition($parts, $params);
    }
}