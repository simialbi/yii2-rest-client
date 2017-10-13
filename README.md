# REST Client for Yii 2 (ActiveRecord-like model)
This extension provides an interface to work with RESTful API via ActiveRecord-like model in Yii 2.
It is based on [ApexWire's](https://github.com/ApexWire) [yii2-restclient](https://github.com/ApexWire/yii2-restclient).

## Resources
 * [yii2-restclient](https://github.com/ApexWire/yii2-restclient)
 * [Yii2 HiArt](https://github.com/hiqdev/yii2-hiart).

## Installation
The preferred way to install this extension is through [composer](http://getcomposer.org/download/).

Either run

```
$ php composer.phar require --prefer-dist simialbi/yii2-rest-client
```

or add

```
"simialbi/yii2-rest-client": "*"
```

to the `require` section of your `composer.json`.

## Configuration
To use this extension, configure restclient component in your application config:

```php
    'components' => [
        'rest' => [
            'class'   => 'simialbi\yii2\rest\Connection',
            'baseUrl' => 'https://api.site.com/'
        ],
    ],
```

## Usage
Define your Model

```php
<?php

namespace app\models;

use simialbi\yii2\rest\ActiveRecord;

class MyModel extends ActiveRecord {
	/**
	 * @inheritdoc
	 */
	public static function modelName() {
		return 'my-super-model-name';
	}

	/**
	 * @inheritdoc
	 */
	public static function primaryKey() {
		return ['id'];
	}
}
```

The usage after define a model is the same like [yii\db\ActiveRecord](http://www.yiiframework.com/doc-2.0/guide-db-active-record.html)

## License

**yii2-rest-client** is released under MIT license. See bundled [LICENSE](LICENSE) for details.

## Acknowledgments
 * [ApexWire's](https://github.com/ApexWire) [yii2-restclient](https://github.com/ApexWire/yii2-restclient)
 * [Yii2 HiArt](https://github.com/hiqdev/yii2-hiart).