<?php
/**
 * @package yii2-rest-client
 * @author Simon Karlen <simi.albi@outlook.com>
 */

namespace yiiunit\extensions\rest;

use Yii;
use yii\di\Container;
use yii\helpers\ArrayHelper;

class TestCase extends \PHPUnit\Framework\TestCase
{
    private $_index = 0;

    /**
     * {@inheritdoc}
     */
    protected function tearDown()
    {
        parent::tearDown();
        $this->destroyApplication();
    }


    /**
     * @param array $config
     * @param string $appClass
     */
    protected function mockWebApplication($config = [], $appClass = '\yii\web\Application')
    {
        new $appClass(ArrayHelper::merge([
            'id' => 'testapp',
            'basePath' => __DIR__,
            'vendorPath' => dirname(__DIR__) . '/vendor',
            'aliases' => [
                '@bower' => '@vendor/bower-asset',
                '@npm' => '@vendor/npm-asset',
            ],
            'components' => [
                'request' => [
                    'cookieValidationKey' => 'wefJDF8sfdsfSDefwqdxj9oq',
                    'scriptFile' => __DIR__ . '/index.php',
                    'scriptUrl' => '/index.php',
                ],
                'rest' => [
                    'class' => 'simialbi\yii2\rest\Connection',
                    'baseUrl' => 'https://api.site.com/',
                    'isTestMode' => true
                ],
                'log' => [
                    'traceLevel' => 3,
                    'targets' => [
                        [
                            'class' => 'yiiunit\extensions\rest\log\ArrayTarget'
                        ]
                    ],
                    'flushInterval' => 0
                ]
            ]
        ], $config));
    }

    /**
     * Destroys application in Yii::$app by setting it to null.
     */
    protected function destroyApplication()
    {
        Yii::$app = null;
        Yii::$container = new Container();
    }

    /**
     * Parse log from index and returns data
     * @return array
     */
    protected function parseLogs()
    {
        $method = '';
        $url = '';
        $data = [];
        $headers = [];

        $profile = false;
        for (; $this->_index <= count(Yii::$app->log->logger->messages); $this->_index++) {
            $message = Yii::$app->log->logger->messages[$this->_index];
            if ($message[2] === 'simialbi\yii2\rest\Connection::request-method') {
                $method = $message[0];
            } elseif ($message[2] === 'simialbi\yii2\rest\Connection::request-url') {
                $url = $message[0];
            } elseif ($message[2] === 'simialbi\yii2\rest\Connection::request-data') {
                $data = $message[0];
            } elseif ($message[2] === 'simialbi\yii2\rest\Connection::request-headers') {
                $data = $message[0];
            } elseif ($message[2] === 'simialbi\yii2\rest\Connection::request') {
                if ($profile) {
                    break;
                }
                $profile = true;
            }
        }

        return [
            'method' => $method,
            'url' => $url,
            'data' => $data,
            'headers' => $headers
        ];
    }
}
