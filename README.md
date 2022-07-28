# REST Client for Yii 2 (ActiveRecord-like model)
This extension provides an interface to work with RESTful API via ActiveRecord-like model in Yii 2.
It is based on [ApexWire's](https://github.com/ApexWire) [yii2-restclient](https://github.com/ApexWire/yii2-restclient).


[![Latest Stable Version](https://poser.pugx.org/simialbi/yii2-rest-client/v/stable?format=flat-square)](https://packagist.org/packages/simialbi/yii2-rest-client)
[![Total Downloads](https://poser.pugx.org/simialbi/yii2-rest-client/downloads?format=flat-square)](https://packagist.org/packages/simialbi/yii2-rest-client)
[![License](https://poser.pugx.org/simialbi/yii2-rest-client/license?format=flat-square)](https://packagist.org/packages/simialbi/yii2-rest-client)
![Build Status](https://github.com/simialbi/yii2-rest-client/workflows/build/badge.svg)

## Resources
 * [yii2-restclient](https://github.com/ApexWire/yii2-restclient)
 * [\yii\db\ActiveRecord](http://www.yiiframework.com/doc-2.0/guide-db-active-record.html)

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
            'class' => 'simialbi\yii2\rest\Connection',
            'baseUrl' => 'https://api.site.com/',
            // 'auth' => function (simialbi\yii2\rest\Connection $db) {
            //      return 'Bearer: <mytoken>';
            // },
            // 'auth' => 'Bearer: <mytoken>',
            // 'usePluralisation' => false,
            // 'useFilterKeyword' => false,
            // 'enableExceptions' => true,
            // 'itemsProperty' => 'items'
        ],
    ],
```

| Parameter           | Default    | Description                                                                                                   |
| ------------------- | -----------| ------------------------------------------------------------------------------------------------------------- |
| `baseUrl`           | `''`       | The location of the api. E.g. for http://api.site.com/v1/users the `baseUrl` would be http://api.site.com/v1/  (required)   |
| `auth`              |            | Either a Closure which returns a `string` or a `string`. The rest connection will be passed as parameter.        |
| `usePluralisation`  | `true`     | Whether to use plural version for lists (index action) or not (e.g. http://api.site.com/users instead of `user`) |
| `useFilterKeyword`  | `true`     | Whether to use "filter" key word in url parameters when filtering (e.g. ?filter[name]=user instead of ?name=user |
| `enableExceptions`  | `false`    | Whether the connection should throw an exception if response is not 200 or not                                   |
| `itemsProperty`     |            | If your items are wrapped inside a property (e.g. `items`), set it's name here                                   | 
| `requestConfig`     | `[]`       |  Client request configuration                                                                                    | 
| `responseConfig`    | `[]`       | Client response configuration                                                                                   | 
| `updateMethod`      | `'put'`    | The method to use for update operations.                                                                        | 
| `isTestMode`        | `false`    | Whether we are in test mode or not (prevent execution)                                                          | 
| `enableQueryCache`  | `false`    | Whether to enable query caching                                                                                 | 
| `queryCacheDuration`| `3600`     | The default number of seconds that query results can remain valid in cache                             | 
| `queryCache`        | `'cache'`  | The cache object or the ID of the cache application component                                           | 

## Usage
Define your Model

```php
<?php

namespace app\models;

use simialbi\yii2\rest\ActiveRecord;

/**
 * MyModel
 * 
 * @property integer $id
 * @property string $name
 * @property string $description 
 * 
 * @property-read MyOtherModel $myOtherModel
 */
class MyModel extends ActiveRecord {
    /**
     * {@inheritdoc}
     */
    public static function modelName() {
        return 'my-super-model-name';
    }

    /**
     * {@inheritdoc}
     */
    public static function primaryKey() {
        return ['id'];
    }
}

/**
 * Class MyOtherModel
 * 
 * @property integer $id
 * @property integer $my_model_id
 * @property string $subject
 * 
 * @property-read MyModel[] $myModels
 */
class MyOtherModel extends ActiveRecord {
    /**
     * {@inheritdoc}
     */
    public static function primaryKey() {
        return ['id'];
    }
}
```

It's important that you define the primary key by overriding `primaryKey()` method. Otherwise you'll get an exception.
If you do not override the `modelName()` method, it will guess it by class name (**MyModel** becomes **my-model**). It's used
to generate the URL together with `simialbi\yii2\rest\Connection::$baseUrl`.

The usage how to define the active record (rules, behaviors etc.) is the same like [yii\db\ActiveRecord](http://www.yiiframework.com/doc-2.0/guide-db-active-record.html).

> Important: Be sure to either define the properties of the object like in the example above (`@property` syntax in phpdoc) 
> or override the `attributes()` method to return the allowed attributes as array

> The same about relations. Be sure to either define them via `@property-read` phpdoc comment or override the `getRelations`
> method. If the related class has not the same namespace as the main class, be sure to use the fully qualified class name
> (e.g. `@property-read \app\models\OtherModel[] $otherModels`)

## License

**yii2-rest-client** is released under MIT license. See bundled [LICENSE](LICENSE) for details.

## Acknowledgments
 * [ApexWire's](https://github.com/ApexWire) [yii2-restclient](https://github.com/ApexWire/yii2-restclient)
 * [Yii2 HiArt](https://github.com/hiqdev/yii2-hiart).
 * [mikolajzieba](https://github.com/mikolajzieba)
