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

This will create a Api/ModelNameController for you and you will have the basic routes in place as follows:

* GET `api/v1/{model_name}` - list all/paged/filtered (class::index)
* GET `api/v1/{model_name}/$id` - Show a specified id (class::show)
* POST `api/v1/{model_name}` - Insert a new record (class::store)
* PUT `api/v1/{model_name}/$id` - Update an existing record (class::update)
* DELETE `api/v1/{model_name}/$id` - Delete an existing record (class::destroy)

You can override the methods by simply putting in your own methods to override - method names in braces above

## Events

* POST (class::store) - triggers a new `Phpsa\LaravelApiController\Events\Created` Event which has the new record available as `$record`
* PUT  (class::update) - triggers a new `Phpsa\LaravelApiController\Events\Updated` Event which has the updated record available as `$record`
* DELETE (class::destry) - triggers a new `Phpsa\LaravelApiController\Events\Deleted` Event which has the deleted record available as `$record`

## Policies

Policies: https://laravel.com/docs/6.x/authorization#generating-policies

Generate with `php artisan make:policy PostPolicy --model=Post`

* Get list - calls the `viewAny` policy
* Get single - calls the `view` policy
* Post New - calls the `create` policy
* Put Update - calls the `update` policy
* Delete item - calls the `delete` policy

Query/Data modifiers in policies for the api endpoints

* `qualifyCollectionQueryWithUser($user, $repository)` -> return void - add any queries to the repository (ie ->where('x','))
* `qualifyItemQueryWithUser($user, $repository)`-> return void - add any queries to the repository (ie ->where('x','))
* `qualifyStoreDataWithUser($data)` - return the updated data array
* `qualifyUpdateDataWithUser($data)` - return the updated data array

## Resources / Collections (Transforming)
 Resources: https://laravel.com/docs/6.x/eloquent-resources

 Generate with
 `php artisan make:resource UserResource` and `php artisan make:resource UserCollection`

 Change the Resource to extend from:

use `Phpsa\LaravelApiController\Http\Resources\ApiResource` for your resource
use `Phpsa\LaravelApiController\Http\Resources\ApiCollection` for your resource collection

in your controller override the following params:
```php
	protected $resourceSingle = UserResource::class;
	protected $resourceCollection = UserCollection::class;
```


## Snake vs Camel

* middleware to convert all came to snake: `Phpsa\LaravelApiController\Http\Middleware\SnakeCaseInputs`
* set request header `X-Accept-Case-Type` to either `snake` or `camel` to alter your data response




## Filtering

For the get command you can filter by using the following url patterns

| Seperator | Description 					| Example 					| Result 							|
| --- 		| --- 							| --- 						| --- 	 							|
| *`=`* 	| Equals 						| ?filter[field]=hello		| select ... where field = 'hello'	|
| *`!=`*  	| Not Equals 					| ?filter[field!]=hello		| select ... where field != 'hello'	|
| *`<>`* 	| Not Equals (alt) 				| ?filter[field<>]=hello	| select ... where field != 'hello'	|
| *`>`* 	| Greater Than					| ?filter[field>]=5	 		| select ... where field > 5	|
| *`>=`* 	| Greater  Or Equal to			| ?filter[field=>]=5	 	| select ... where field >= 5	|
| *`<`* 	| Less Than						| ?filter[field<]=5	 		| select ... where field <> 5	|
| *`<=`* 	| Less Or Equal to				| ?filter[field=<]=5	 	| select ... where field <= 5	|
| *`~`*  	| Contains (LIKE with wildcard on both sides)		| ?filter[field~]=hello		| select ... where field like '%hello%'	|
| *`^`*  	| Starts with (LIKE with wildcard on end)			| ?filter[field^]=hello		| select ... where field like 'hello%'	|
| *`$`*  	| Ends with (LIKE with wildcard on start)			| ?filter[field$]=hello		| select ... where field like 'hello%'	|
| *`!~`*  	| Not Contains (LIKE with wildcard on both sides)	| ?filter[field!~]=hello	| select ... where field not like '%hello%'	|
| *`!^`*  	| Not Starts with (LIKE with wildcard on end)		| ?filter[field!^]=hello	| select ... where field not like 'hello%'	|
| *`!$`*  	| Not Ends with (LIKE with wildcard on start)		| ?filter[field!$]=hello	| select ... where field not like 'hello%'	|

# In / Not In
You can pass to the filters an array of values
ie: `filter[user_id]=1||2||||4||7` or `filter[user_id!]=55||33`


# Fields, Relationships, Sorting & Pagination

## Fields
By default all fields are returned, you can limit that to specific fields in the following ways:

* Api Controller parameter `$defaultFields` default as `protected $defaultFields = ['*'];` - switch to include an array of fields
* fields param in url querystring: ie `fields=id,name,age` = will only return those, this will also override the above.
* in your response resource you can set the static::allowedFields to lock down which fields are returnable
* addfields and removefields params in url querystring will work with these.

## Relationships

* Using the relationships defined in your models, you can pass a comma delimited list eg `include=join1,join2` which will return those joins (one or many)

## Sorting

* Sorts can be passed as comma list aswell, ie `sort=age asc` or `sort=age asc,name desc,eyes` - generates sql of `sort age asc` and `sort age asc, name desc, eyes asc` respectively
* Default sort can also be added on the controller using by overrideing the `protected $defaultSort = null;
` parameter

## Pagination
* pagination can be enabled/disbled on the controller by overriding the `protected $defaultLimit = 25;` on the controller
* pagination can also be passed via the url using `limit=xx&page=y`
* pagination can also be limited to a max per page by overriding the `protected $maximumLimit = false;` parameter

## Validation
* When Posting a new record, validation can be done by adding a `rulesForCreate` method to your controller returning an array eg
```php
[
    'email' => 'required|email',
    'games' => 'required|numeric',
]
```
see https://laravel.com/docs/5.8/validation#conditionally-adding-rules
* for updating a record, add a method `rulesForUpdate` per above.

## Defaults

The following parameters are set in the Base Api controller and can be overwritten by your Controller on a case by case basis:

* **DEPRECATED** `protected $resourceKeySingular = 'data';`
* **DEPRECATED** `protected $resourceKeyPlural = 'data';`

* `protected $resourceSingle = JsonResource::class;`			Collection to use for your single resource
* `protected $resourceCollection = ResourceCollection::class;`	Collection to use for your resource collection
* `protected $defaultFields = ['*'];`							Default Fields to respond with
* `protected $defaultSort = null;`								Set the default sorting for queries.
* `protected $defaultLimit = 25;`								Number of items displayed at once if not specified. (0 = maximumLimit)
* `protected $maximumLimit = 0;`								Maximum limit that can be set via $_GET['limit']. - this ties in with the defaultLimit aswell, and if wanting to disable pagination , both should be 0. ) will allow all records to be returned in a single call.
* `protected $unguard = false;`   								Do we need to unguard the model before create/update?

## Security

If you discover any security related issues, please email
instead of using the issue tracker.

## Credits

- [Craig G Smith](https://github.com/phpsa)
- [Phil Taylor]()
- [All contributors](https://github.com/phpsa/laravel-api-controller/graphs/contributors)

## Sponsors
- [Custom D](https://customd.com)

[badge_laravel]:   https://img.shields.io/badge/Laravel-5.8%20to%206-orange.svg?style=flat-square
[badge_issues]:    https://img.shields.io/github/issues/ARCANEDEV/Support.svg?style=flat-square
