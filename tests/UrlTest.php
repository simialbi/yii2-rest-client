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

        $this->assertEquals('GET', $logEntry['method']);
        $this->assertStringStartsWith('https://api.site.com/rest-models/1', $logEntry['url']);
    }

    public function testGetAnotherOne()
    {
        RestModel::find()->where(['id' => 1])->one();

        $logEntry = $this->parseLogs();

        $this->assertEquals('GET', $logEntry['method']);
        $this->assertStringStartsWith('https://api.site.com/rest-models/1', $logEntry['url']);
    }

    public function testFilter()
    {
        RestModel::find()->where(['name' => 'John'])->one();

        $logEntry = $this->parseLogs();

        $this->assertEquals('GET', $logEntry['method']);
        $this->assertStringStartsWith('https://api.site.com/rest-models?filter%5Bname%5D=John', $logEntry['url']);
    }

    public function testWithoutFilterKeyword()
    {
        $this->mockWebApplication([
            'components' => [
                'rest' => [
                    'useFilterKeyword' => false
                ]
            ]
        ]);

        RestModel::find()->where(['name' => 'John'])->one();

        $logEntry = $this->parseLogs();

        $this->assertEquals('GET', $logEntry['method']);
        $this->assertStringStartsWith('https://api.site.com/rest-models?name=John', $logEntry['url']);
    }

    public function testDeleteOne()
    {
        $model = new RestModel([
            'oldAttributes' => [
                'id' => 1,
                'name' => 'test',
                'description' => 'test',
                'created_at' => time(),
                'updated_at' => time(),
                'created_by' => 'simialbi',
                'updated_by' => 'simialbi'
            ],
            'attributes' => [
                'id' => 1,
                'name' => 'test',
                'description' => 'test',
                'created_at' => time(),
                'updated_at' => time(),
                'created_by' => 'simialbi',
                'updated_by' => 'simialbi'
            ]
        ]);

        $model->delete();

        $logEntry = $this->parseLogs();

        $this->assertEquals('DELETE', $logEntry['method']);
        $this->assertStringStartsWith('https://api.site.com/rest-models/1', $logEntry['url']);
    }
}
