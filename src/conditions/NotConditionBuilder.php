<?php
/**
 * @package yii2-rest-client
 * @author Simon Karlen <simi.albi@outlook.com>
 */

namespace simialbi\yii2\rest\conditions;


use yii\db\conditions\NotCondition;
use yii\db\ExpressionInterface;

/**
 * {@inheritdoc}
 *
 * @property \simialbi\yii2\rest\QueryBuilder $queryBuilder
 */
class NotConditionBuilder extends \yii\db\conditions\NotConditionBuilder
{
    /**
     * {@inheritdoc}
     *
     * @return array
     */
    public function build(ExpressionInterface $expression, array &$params = [])
    {
        /* @var $expression NotCondition */
        $operand = $expression->getCondition();
        if (empty($operand)) {
            return [];
        }

        $expression = $this->queryBuilder->buildCondition($operand, $params);

        return ['not' => $expression];
    }
}