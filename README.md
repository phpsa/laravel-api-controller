# Laravel Api Controller
[![For Laravel 5][badge_laravel]](https://github.com/phpsa/laravel-api-controller/issue)
[![Build Status](https://travis-ci.org/phpsa/laravel-api-controller.svg?branch=master)](https://travis-ci.org/phpsa/laravel-api-controller)
[![Coverage Status](https://coveralls.io/repos/github/phpsa/laravel-api-controller/badge.svg?branch=master)](https://coveralls.io/github/phpsa/laravel-api-controller?branch=master)
[![Packagist](https://img.shields.io/packagist/v/phpsa/laravel-api-controller.svg)](https://packagist.org/packages/phpsa/laravel-api-controller)
[![Packagist](https://poser.pugx.org/phpsa/laravel-api-controller/d/total.svg)](https://packagist.org/packages/phpsa/laravel-api-controller)
[![Packagist](https://img.shields.io/packagist/l/phpsa/laravel-api-controller.svg)](https://packagist.org/packages/phpsa/laravel-api-controller)
[![Github Issues][badge_issues]](https://github.com/phpsa/laravel-api-controller/issue)

Basic CRUD API Methods that can be extended for your models by default has a list, show, update, add and delete endpoint to interact with your model.

## Installation

Install via composer
```bash
composer require phpsa/laravel-api-controller
```

### Publish Configuration File (optional - if you need to change any of the default configurations)

```bash
php artisan vendor:publish --provider="Phpsa\LaravelApiController\ServiceProvider" --tag="config"
```

## Usage

**Generate a new Api Controller, Repository and Route via `php artisan make:api {ModelName}`**

This will create a Api/ModelNameControlelr for you and you will have the basic routes in place as follows:

* GET `api/v1/{model_name}` - list all/paged/filtered (index)
* GET `api/v1/{model_name}/$id` - Show a specified id (show)
* POST `api/v1/{model_name}` - Insert a new record (store)
* PUT `api/v1/{model_name}/$id` - Update an existing record (update)
* DELETE `api/v1/{model_name}/$id` - Delete an existing record (destroy)

You can override the methods by simply putting in your own methods to override - method names in braces above



## Security

If you discover any security related issues, please email
instead of using the issue tracker.

## Credits

- [Craig G Smith](https://github.com/phpsa)
- [All contributors](https://github.com/phpsa/laravel-api-controller/graphs/contributors)

[badge_laravel]:   https://img.shields.io/badge/Laravel-5.8%20to%205.8-orange.svg?style=flat-square
[badge_issues]:    https://img.shields.io/github/issues/ARCANEDEV/Support.svg?style=flat-square
