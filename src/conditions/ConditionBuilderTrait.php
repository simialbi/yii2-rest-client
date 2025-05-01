<?php

declare(strict_types=1);
/**
 * @package yii2-rest-client
 * @author Simon Karlen <simi.albi@outlook.com>
 * @copyright Copyright © 2019 Simon Karlen
 */

namespace simialbi\yii2\rest\conditions;

use yii\data\DataFilter;
use yii\helpers\ArrayHelper;

trait ConditionBuilderTrait
{
    /**
     * Returns the operator that is represented by this condition class
     *
     * @throws \Exception
     */
    protected function getOperator(string $operator): string
    {
        return ArrayHelper::getValue(array_flip((new DataFilter())->filterControls), $operator, 'and');
    }
}
