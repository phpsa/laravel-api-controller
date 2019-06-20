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

## Filtering

For the get command you can filter by using the following url patterns

| Seperator | Description 					| Example 		| Result 							|
| --- 		| --- 							| --- 			| --- 	 							|
| *`=`* 	| Equals 						| ?field=hello	| select ... where field = 'hello'	|
| *`!=`*  	| Not Equals 					| ?field!=hello	| select ... where field != 'hello'	|
| *`<>`* 	| Not Equals (alt) 				| ?field<>hello	| select ... where field != 'hello'	|
| *`>`* 	| Greater Than					| ?field>5	 	| select ... where field > 5	|
| *`>=`* 	| Greater  Or Equal to			| ?field=>5	 	| select ... where field >= 5	|
| *`<`* 	| Less Than						| ?field<5	 	| select ... where field <> 5	|
| *`<=`* 	| Less Or Equal to				| ?field=<5	 	| select ... where field <= 5	|
| *`~`*  	| Contains (LIKE with wildcard on both sides)| ?field~hello	| select ... where field like '%hello%'	|
| *`^`*  	| Starts with (LIKE with wildcard on end)| ?field^hello	| select ... where field like 'hello%'	|
| *`$`*  	| Ends with (LIKE with wildcard on start)| ?field$hello	| select ... where field like 'hello%'	|
| *`!~`*  	| Not Contains (LIKE with wildcard on both sides)| ?field!~hello	| select ... where field not like '%hello%'	|
| *`!^`*  	| Not Starts with (LIKE with wildcard on end)| ?field!^hello	| select ... where field not like 'hello%'	|
| *`!$`*  	| Not Ends with (LIKE with wildcard on start)| ?field!$hello	| select ... where field not like 'hello%'	|


# Fields, Relationships, Sorting & Pagination

## Fields
By default all fields are returned, you can limit that to specific fields in the following ways:

* Api Controller parameter `$defaultFields` default as `protected $defaultFields = ['*'];` - switch to include an array of fields
* fields param in url querystring: ie `fields=id,name,age` = will only return those, this will also override the above.

## Relationships

* Using the relationships defined in your models, you can pass a comma delimited list eg `with=join1,join2` which will return those joins (one or many)

## Sorting

* Sorts can be passed as comma list aswell, ie `sort=age asc` or `sort=age asc,name desc,eyes` - generates sql of `sort age asc` and `sort age asc, name desc, eyes asc` respectively
* Default sort can also be added on the controller using by overrideing the `protected $defaultSort = null;
` parameter

## Pagination
* pagination can be enabled/disbled on the controller by overriding the `protected $defaultLimit = 25;` on the controller
* pagination can also be passed via the url using `limit=xx&page=y`
* pagination can also be limited to a max per page by overriding the `protected $maximumLimit = false;` parameter




## Security

If you discover any security related issues, please email
instead of using the issue tracker.

## Credits

- [Craig G Smith](https://github.com/phpsa)
- [All contributors](https://github.com/phpsa/laravel-api-controller/graphs/contributors)

[badge_laravel]:   https://img.shields.io/badge/Laravel-5.8%20to%205.8-orange.svg?style=flat-square
[badge_issues]:    https://img.shields.io/github/issues/ARCANEDEV/Support.svg?style=flat-square
