<?php

declare(strict_types=1);
/**
 * @package yii2-rest-client
 * @author Simon Karlen <simi.albi@outlook.com>
 * @copyright Copyright © 2019 Simon Karlen
 */

namespace simialbi\yii2\rest;

class Exception extends \yii\db\Exception
{
    public function getName(): string
    {
        return 'REST Exception';
    }
}
