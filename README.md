# Exchange Web Services for yii2 (ActiveRecord-like models)
This extension provides an interface to work with Exchange Web Services. It's based on 
[php-ews](https://github.com/jamesiarmes/php-ews).

[![Latest Stable Version](https://poser.pugx.org/simialbi/yii2-ews/v/stable?format=flat-square)](https://packagist.org/packages/simialbi/yii2-rest-client)
[![Total Downloads](https://poser.pugx.org/simialbi/yii2-ews/downloads?format=flat-square)](https://packagist.org/packages/simialbi/yii2-rest-client)
[![License](https://poser.pugx.org/simialbi/yii2-ews/license?format=flat-square)](https://packagist.org/packages/simialbi/yii2-rest-client)
[![Build Status](https://github.com/simialbi/yii2-ews/actions/workflows/build.yml/badge.svg)](https://github.com/simialbi/yii2-ews/actions/workflows/build.yml)

## Resources
 * [php-ews](https://github.com/jamesiarmes/php-ews)
 * [EWS Reference](https://docs.microsoft.com/en-us/exchange/client-developer/web-service-reference/web-services-reference-for-exchange)

## Installation
The preferred way to install this extension is through [composer](http://getcomposer.org/download/).

Either run

```
$ php composer.phar require --prefer-dist simialbi/yii2-ews
```

or add

```
"simialbi/yii2-ews": "^1.0.0"
```

to the `require` section of your `composer.json`.

## Configuration
To use this extension, configure ews component in your application config:

```php
    'components' => [
        'ews' => [
            'class' => 'simialbi\yii2\ews\Connection',
            'server' => 'my-exchange.server.com',
            'username' => 'administrator',
            'password' => 'superSafePassword',
            // 'enableLogging' => true
            //TODO extend 
        ],
    ],
```

| Parameter          | Description                                                                                                      |
| ------------------ | ---------------------------------------------------------------------------------------------------------------- | 

## Usage
> TODO

## License

**yii2-ews** is released under MIT license. See bundled [LICENSE](LICENSE) for details.
