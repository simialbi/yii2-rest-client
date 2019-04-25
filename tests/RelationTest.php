<?php
/**
 * @package yii2-rest-client
 * @author Simon Karlen <simi.albi@outlook.com>
 * @copyright Copyright © 2019 Simon Karlen
 */

namespace yiiunit\extensions\rest;


use Yii;
use yiiunit\extensions\rest\fixtures\RestModelFixture;
use yiiunit\extensions\rest\models\RestModel;

class RelationTest extends TestCase
{
    protected function setUp()
    {
        parent::setUp();

        $this->mockWebApplication();
        Yii::$app->log->logger->flush();
    }

    public function testRelationGet()
    {
        $fixture = new RestModelFixture();
        $fixture->load();

        /* @var $model RestModel */
        $model = $fixture->getModel(0);

        $this->assertInstanceOf(RestModel::class, $model);

        Yii::$app->log->logger->flush();

//        var_dump($model);
        $model->getRelatedRests()->all();

        $logEntry = $this->parseLogs();

        $this->assertEquals('GET', $logEntry['method']);
        $this->assertEquals('https://api.site.com/related-rest-models?filter%5Brest_model_id%5D=1', $logEntry['url']);
    }
}
