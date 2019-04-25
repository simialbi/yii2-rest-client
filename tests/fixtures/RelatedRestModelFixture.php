<?php
/**
 * @package yii2-rest-client
 * @author Simon Karlen <simi.albi@outlook.com>
 * @copyright Copyright © 2019 Simon Karlen
 */

namespace yiiunit\extensions\rest\fixtures;

use simialbi\yii2\rest\ActiveFixture;

class RelatedRestModelFixture extends ActiveFixture
{
    public $modelClass = 'yiiunit\extensions\rest\models\RelatedRestModel';
    public $depends = ['yiiunit\extensions\rest\fixtures\RestModelFixture'];
}
