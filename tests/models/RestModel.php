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
 *
 * @property-read RelatedRestModel[] $relatedRests
 * @property-read RelatedRestModel $relatedRest
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

    /**
     * Get related rests
     * @return \yii\db\ActiveQueryInterface
     */
    public function getRelatedRests()
    {
        return $this->hasMany(RelatedRestModel::class, ['rest_model_id' => 'id']);
    }

    /**
     * Get related rest
     * @return \simialbi\yii2\rest\ActiveQuery|\yii\db\ActiveQuery|\yii\db\ActiveQueryInterface
     */
    public function getRelatedRest()
    {
        return $this->hasOne(RelatedRestModel::class, ['rest_model_id' => 'id']);
    }
}
