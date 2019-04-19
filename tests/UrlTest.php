<?php
/**
 * @package yii2-rest-client
 * @author Simon Karlen <simi.albi@outlook.com>
 * @copyright Copyright © 2019 Simon Karlen
 */

namespace yiiunit\extensions\rest;

use Yii;
use yiiunit\extensions\rest\models\RestModel;

class UrlTest extends TestCase
{
    protected function setUp()
    {
        parent::setUp();

        $this->mockWebApplication();
        Yii::$app->log->logger->flush();
    }

    public function testGetOne()
    {
        RestModel::findOne(1);

        $logEntry = $this->parseLogs();

        $this->assertEquals('https://api.site.com/rest-models/1', $logEntry['url']);
    }

    public function testGetAnotherOne()
    {
        RestModel::find()->where(['id' => 1])->one();

        $logEntry = $this->parseLogs();

        $this->assertEquals('https://api.site.com/rest-models/1', $logEntry['url']);
    }
}
