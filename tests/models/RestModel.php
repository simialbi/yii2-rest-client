<?php
/**
 * @package yii2-rest-client
 * @author Simon Karlen <simi.albi@outlook.com>
 * @copyright Copyright © 2019 Simon Karlen
 */

namespace yiiunit\extensions\rest\models;


use simialbi\yii2\rest\ActiveRecord;

/**
 * Class RestModel
 * @package yiiunit\extensions\rest\models
 *
 * @property integer $id
 * @property string $name
 * @property string $description
 * @property integer $created_at
 * @property integer $updated_at
 * @property string $created_by
 * @property string $updated_by
 */
class RestModel extends ActiveRecord
{
    /**
     * {@inheritDoc}
     */
    public static function primaryKey()
    {
        return ['id'];
    }
}