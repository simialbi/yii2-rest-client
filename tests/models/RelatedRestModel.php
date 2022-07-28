<?php
/**
 * @package yii2-rest-client
 * @author Simon Karlen <simi.albi@outlook.com>
 * @copyright Copyright © 2019 Simon Karlen
 */

namespace yiiunit\extensions\rest\models;

use simialbi\yii2\rest\ActiveRecord;

/**
 * Class RelatedRestModel
 * @package yiiunit\extensions\rest\models
 *
 * @property integer $id
 * @property integer $rest_model_id
 * @property string $subject
 * @property string $message
 * @property integer $created_at
 * @property integer $updated_at
 * @property string $created_by
 * @property string $updated_by
 */
class RelatedRestModel extends ActiveRecord
{
    /**
     * {@inheritDoc}
     */
    public static function primaryKey(): array
    {
        return ['id'];
    }
}
