<?php
/**
 * @package yii2-rest-client
 * @author Simon Karlen <simi.albi@outlook.com>
 * @copyright Copyright © 2019 Simon Karlen
 */

namespace yiiunit\extensions\rest\log;

use yii\helpers\ArrayHelper;
use yii\log\Target;

/**
 * Class ArrayTarget
 * @package yiiunit\extensions\rest\log
 *
 * @property-read array $cache
 */
class ArrayTarget extends Target
{
    /**
     * @var array Stores log data
     */
    private $_cache = [];

    /**
     * Exports log [[messages]] to a specific destination.
     * Child classes must implement this method.
     */
    public function export()
    {
        $this->_cache = ArrayHelper::merge($this->_cache, $this->messages);
    }

    /**
     * Getter for cache variable
     * @return array
     */
    public function getCache()
    {
        return $this->_cache;
    }
}