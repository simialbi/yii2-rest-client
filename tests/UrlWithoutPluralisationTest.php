<?php
/**
 * @package yii2-rest-client
 * @author Simon Karlen <simi.albi@outlook.com>
 * @copyright Copyright © 2019 Simon Karlen
 */

namespace yiiunit\extensions\rest;


use yiiunit\extensions\rest\models\RestModel;

class UrlWithoutPluralisationTest extends TestCase
{
    protected function setUp()
    {
        parent::setUp();

        $this->mockWebApplication([
            'components' => [
                'rest' => [
                    'usePluralisation' => false
                ]
            ]
        ]);
    }

    public function testGetOne()
    {
        RestModel::findOne(1);

        $logEntry = $this->parseLogs();

        $this->assertEquals('https://api.site.com/rest-model/1', $logEntry['url']);
    }

    public function testGetAnotherOne()
    {
        RestModel::find()->where(['id' => 1])->one();

        $logEntry = $this->parseLogs();

        $this->assertEquals('https://api.site.com/rest-model/1', $logEntry['url']);
    }
}