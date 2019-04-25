<?php
/**
 * @package yii2-rest-client
 * @author Simon Karlen <simi.albi@outlook.com>
 * @copyright Copyright © 2019 Simon Karlen
 */

namespace simialbi\yii2\rest;

class Exception extends \yii\db\Exception
{
    /**
     * {@inheritDoc}
     */
    public function getName()
    {
        return 'REST Exception';
    }
}
