# Laravel Api Controller

[[_TOC_]]

[![For Laravel 9 to 10][badge_laravel]](https://github.com/phpsa/laravel-api-controller/issues)
[![Build Status](https://api.travis-ci.com/phpsa/laravel-api-controller.svg?branch=master)](https://travis-ci.com/phpsa/laravel-api-controller)
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

**CLI Commands**

* `artisan make:api:controller {ControllerName}` to generate the controller
* `artisan make:api:policy {PolicyName} -m {Model}` to generate a policy file
* `artisan make:api:resource {ResourceName|CollectionName}` to geneate the response resource

This will create a Api/ModelNameController for you and you will have the basic routes in place as follows:

- GET `api/v1/{model_name}` - list all/paged/filtered (class::index)
- GET `api/v1/{model_name}/$id` - Show a specified id (class::show)
- POST `api/v1/{model_name}` - Insert a new record (class::store)
- PUT `api/v1/{model_name}/$id` - Replace an existing record (class::update)
- PATCH `api/v1/{model_name}/$id` - Update an existing record (class::update)
- DELETE `api/v1/{model_name}/$id` - Delete an existing record (class::destroy)

If you specify `--soft-deletes` option on `make:api:controller` it will also create an additional `restore` controller endpoint & route:
- PATCH `api/v1/{model_name}/$id` - Restore a soft-deleted record (class::restore). This only works for models with Soft Deletes enabled.

You can override the methods by simply putting in your own methods to override - method names in braces above

## Events

- POST (class::store) - triggers a new `Phpsa\LaravelApiController\Events\Created` Event which has the new record available as `$record`
- PUT (class::update) - triggers a new `Phpsa\LaravelApiController\Events\Updated` Event which has the updated record available as `$record`
- DELETE (class::destroy) - triggers a new `Phpsa\LaravelApiController\Events\Deleted` Event which has the deleted record available as `$record`

## Policies

Policies: https://laravel.com/docs/6.x/authorization#generating-policies

Generate with `php artisan make:policy PostPolicy --model=Post`

- Get list - calls the `viewAny` policy
- Get single - calls the `view` policy
- Post New - calls the `create` policy
- Put Update - calls the `update` policy
- Delete item - calls the `delete` policy

Query/Data modifiers in policies for the api endpoints

- `qualifyCollectionQueryWithUser($user, $repository)` -> return void - add any queries to the repository (ie ->where('x','))
- `qualifyItemQueryWithUser($user, $repository)`-> return void - add any queries to the repository (ie ->where('x','))
- `qualifyStoreDataWithUser($data)` - return the updated data array
- `qualifyUpdateDataWithUser($data)` - return the updated data array

## Resources / Collections (Transforming)

Resources: https://laravel.com/docs/6.x/eloquent-resources

Generate with
`php artisan make:apiresource UserResource` and `php artisan make:api:resource UserCollection`

Change the Resource to extend from:

use `Phpsa\LaravelApiController\Http\Resources\ApiResource` for your resource
use `Phpsa\LaravelApiController\Http\Resources\ApiCollection` for your resource collection

in your controller override the following params:

```php
	protected $resourceSingle = UserResource::class;
	protected $resourceCollection = UserCollection::class;
```

## Snake vs Camel

- middleware to convert all camel to snake: `Phpsa\LaravelApiController\Http\Middleware\SnakeCaseInputs`
- set request header `X-Accept-Case-Type` to either `snake` or `camel` to alter your data response

## Filtering

 ### stable option that will be removed once experimental stable

For the get command you can filter by using the following url patterns

| Seperator | Description                                     | Example                | Result                                    |
| --------- | ----------------------------------------------- | ---------------------- | ----------------------------------------- |
| _`=`_     | Equals                                          | ?filter[field]=hello   | select ... where field = 'hello'          |
| _`!=`_    | Not Equals                                      | ?filter[field!]=hello  | select ... where field != 'hello'         |
| _`<>`_    | Not Equals (alt)                                | ?filter[field<>]=hello | select ... where field != 'hello'         |
| _`>`_     | Greater Than                                    | ?filter[field>]=5      | select ... where field > 5                |
| _`>=`_    | Greater Or Equal to                             | ?filter[field>=]=5     | select ... where field >= 5               |
| _`<`_     | Less Than                                       | ?filter[field<]=5      | select ... where field <> 5               |
| _`<=`_    | Less Or Equal to                                | ?filter[field<=]=5     | select ... where field <= 5               |
| _`~`_     | Contains (LIKE with wildcard on both sides)     | ?filter[field~]=hello  | select ... where field like '%hello%'     |
| _`^`_     | Starts with (LIKE with wildcard on end)         | ?filter[field^]=hello  | select ... where field like 'hello%'      |
| _`$`_     | Ends with (LIKE with wildcard on start)         | ?filter[field$]=hello  | select ... where field like 'hello%'      |
| _`!~`_    | Not Contains (LIKE with wildcard on both sides) | ?filter[field!~]=hello | select ... where field not like '%hello%' |
| _`!^`_    | Not Starts with (LIKE with wildcard on end)     | ?filter[field!^]=hello | select ... where field not like 'hello%'  |
| _`!$`_    | Not Ends with (LIKE with wildcard on start)     | ?filter[field!$]=hello | select ... where field not like 'hello%'  |

# In / Not In

You can pass to the filters an array of values
ie: `filter[user_id]=1||2||||4||7` or `filter[user_id!]=55||33`

# Null / Not Null (introduced 1.23.0)

If you need to filter on whether a field is null or not null, you can use the filter param as of version 1.23.0 EG: `filter[age]=NULL` or `filter[age!]=NULL`. Note that NULL must be uppercase.

**Older versions**
Add a scope to your model: eg

```php

public function scopeAgeNull(Builder $builder, $isNull = true){
  $isNull ? $builder->whereNull('age') : $builder->whereNotNull('age');
}
```

Add to your allowedScopes and can then be called in url as `?ageNull=1` for where null and `?ageNull=0` for where age not null



### Filtering 

- use the url pattern `filters[column][operator]=value` eg `filters[age][>]=18&filters[title][contains]=testing`

| Seperator | Description                                     | Example                | Result                                    |
| --------- | ----------------------------------------------- | ---------------------- | ----------------------------------------- |
| empty / _`=`_ / `is` / `equals`    | Equals                                          | ?filters[field]=hello / ?filters[field][is]=hello  | select ... where field = 'hello'          |
| _`!=`_ / `!is` / `!equals` / `not_equals`  | Not Equals                                      | ?filters[field][!is]=hello  | select ... where field != 'hello'         |
| _`>`_ / `greater_than`    | Greater Than                                    | ?filters[field][greater_than]=5      | select ... where field > 5                |
| _`>=`_ / `greater_than_or_equal_to` / `greater_or_equal` / `gte`  | Greater Or Equal to                             | ?filters[field][greater_or_equal]=5     | select ... where field >= 5               |
| _`<`_  / `less_than`   | Less Than                                       | ?filters[field][<]=5      | select ... where field <> 5               |
| _`<=`_ / `less_than_or_equal_to` / `less_or_equal` / `lte`  | Less Or Equal to                                | ?filters[field][less_or_equal]=5     | select ... where field <= 5               |
| _`~`_  / `contains`   | Contains (LIKE with wildcard on both sides)     | ?filters[field][contains]=hello  | select ... where field like '%hello%'     |
| _`^`_  / `starts_with`   | Starts with (LIKE with wildcard on end)         | ?filters[field][starts_with]=hello  | select ... where field like 'hello%'      |
| _`$`_  / `ends_with`   | Ends with (LIKE with wildcard on start)         | ?filters[field][ends_with]=hello  | select ... where field like 'hello%'      |
| _`!~`_ / `!contains` / `not_contains`  | Not Contains (LIKE with wildcard on both sides) | ?filters[field][!contains]=hello | select ... where field not like '%hello%' |
| _`!^`_ / `!starts_with` / `not_starts_with`   | Not Starts with (LIKE with wildcard on end)     | ?filters[field][!^]=hello | select ... where field not like 'hello%'  |
| _`!$`_ / `!ends_with` /   `not_ends_with`   | Not Ends with (LIKE with wildcard on start)     | ?filters[field][!$]=hello | select ... where field not like 'hello%'  |
| `in`   | in    | ?filters[field][in]=1,2,3 | select ... where field in(1,2,3)  |
| `not_in`  / `!in`  | NOT in    | ?filters[field][in]=1,2,3 | select ... where field not in(1,2,3)  |
| `has`   | has    | ?filters[field][has] | select ... where exists(field join)  |
| `not_has`  / `!has`  | NOT has    | ?filters[field][!has] | select ... where not exists (field join)  |

* Null = `filters[age]=NULL` will generate `where age is null`

* Json columns = `filters[meta->seo][is]=enabled` will generate
```
where json_unquote(json_extract(`meta\`, '$."seo"')) = 'enabled'
```

* Relations: `filters[relationName][has]` or `filters[relationName][!has]` or `filters[relation_name][not_has]`
* Relations filtering `filters[tags][has][slug]=my_slug`
* Relations `filters[tags]=true` or `filters['tags.slug']=myslug` `filters[tags.slug][!]=myslug` `filters[tags.slug][!][contains]=money`

Enforced scopes / query filters on a controller override the
```
protected function getNewQuery(): Builder
    {
        return resolve($this->model())->newQuery();
    }
```
method in your controller to include any additional queries / scopes etc.

## Requests

We have added a request macro to enable you to set these on your request as needed:

eg:
```php
    public function index(Request $request)
    {

        $request->apiFilter('user_id', auth()->id());
        $request->apiFilter('owner_id', 'not_equals', auth()->id());
        $request->apiFilter('age', '>=', 5);
        $request->apiFilter('age', '<=', 10);
        $request->apiInclude(['owner','user']);
        $request->apiAddFields(['x','y','z']);

```

* filter method is `apiFilter($column, $value)` or `apiFilter($column, $operator, $value)`
* addfields method is `apiAddFields(array|string $fieldsOrAttributesToInclude)`
* include method is `apiInclude(array|string $relations)`




In your controller, we generally use `request->all()` for the filling of models. Should you wish to use a more strict option, you can opt into using validated values only by calling `$this->setOnlyValidated()` in your controller which will then use the `request->validated()` to get the data (Note: this means it will not take any merged information from postValidation).

## Scopes

In addition to filtering, you can use Laravel's Eloquent [Query Scopes](https://laravel.com/docs/6.x/eloquent#local-scopes) to do more complex searches or filters.
Simply add an `$allowedScopes` to your `ApiResource`, and that scope will be exposed as a query parameter.

Assuming you have a `scopeFullname` defined on your Eloquent Model, you can expose this scope to your API as follows:

```php
protected static $allowedScopes = [
  'fullname'
];
```

Given the above `$allowedScopes` array, your API consumers will now be able to request `?fullname=John`. The query parameter value will be passed to your scope function in your Eloquent Model.

## Filtering on related models

You can easily filter using any related model that is configured for `include`. Simply specify `?filter[model.field]=123` in your query string. The same filter options above apply to related fields.

## Grouped Filtering Scopes

`filter_by_relation_group[a][name]=weight&filter_by_relation_group[a][value][>]=900&filter_by_relation_group[b][name]=color&filter_by_relation_group[b][value]=color`

```
 public function scopeFilterByRelationGroup(Builder $builder, array $wheres): void
    {
        $where =  collect($wheres)->map(fn ($child) =>
           $this->parseFiltersArray($child)
        )->each(
            fn($group, $key) => $builder->whereHas('Relation', function ($subQ) use ($group, $key) {
                $group->each(
                    fn($filter, $column) => collect($filter)->each(fn($value, $comparison) => $this->buildQuery($column, $comparison, $value, $subQ))
                );
            }
            )
        );

    }
  ```

# Fields, Relationships, Sorting & Pagination

## Fields

By default all fields are returned, you can limit that to specific fields in the following ways:

- Api Controller parameter `$defaultFields` default as `protected $defaultFields = ['*'];` - switch to include an array of fields
- fields param in url querystring: ie `fields=id,name,age` = will only return those, this will also override the above.
- in your response resource you can set the static::allowedFields to lock down which fields are returnable.
  - This also controls which related resources are returnable. Include the key that is used in `$mapResources` (see "Relationships" below).
- `addfields` and `removefields` params in url querystring will work with these.
- Use laravel [eloquent model `$appends`](https://laravel.com/docs/6.x/eloquent-serialization#appending-values-to-json) property to automatically include custom attribute accessors.

### Gated Response Fields

Gates can be used to control access to fields and related resources, by defining `$gatedFields`:

```
protected static array $fieldGates = [
    'gate-one' => [
        'fieldA',
        'fieldB',
     ],
     'gate-two' => [
       'fieldA',
       'fieldC,
       'relatedResourceD'
     ]
];
```

Each specified gate will be used to determine whether that set of fields will be included.

Each gate will be passed the resource as well as the user, so it can test whether the user should be allowed to access that specific resource.

Example gate definition:

```
  Gate::define(
      'supervises-the-group',
      fn ($user, Group $group) => (int) $user->id === $group->supervisor_id
  );
```

## Relationships

- Using the relationships defined in your models, you can pass a comma delimited list eg `include=join1,join2` which will return those joins (one or many).

Simply add a `protected static $mapResources` to your `Resource` to define which resources to assign your related data. E.e., for a one to many relationship, you should specify a collection, and a one-to-one relationship specify the related resource directly. This will allow the API to properly format the related record.

```
    protected static $mapResources = [
        'notes' => NotesCollection::class,
        'owner' => OwnerResource::class
    ];
```

- You can automatically update and create related records for most types of relationships. Just include the related resource name in your POST or PUT request.
- Important: if you are using `$defaultFields` and/or `$allowedFields` in your resource, the related resource key from `$mapResources` must also be included in those lists for that related resource to be included.

For `BelongsToMany` or `MorphToMany` relationships, you can choose the sync strategy. By default, this will take an _additive_ strategy. That is to say, related records sent will be ADDED to any existing related records. On a request-by-request basis, you can opt for a _sync_ strategy which will remove the pivot for any related records not listed in the request. Note the actual related record will not be removed, just the pivot entry.

To opt for the _sync_ behavaiour, set `?sync[field]=true` in your request.

## Sorting

- Sorts can be passed as comma list aswell, ie `sort=age asc` or `sort=age asc,name desc,eyes` - generates sql of `sort age asc` and `sort age asc, name desc, eyes asc` respectively
- Default sort can also be added on the controller using by overrideing the `protected $defaultSort = null;` parameter

## Pagination

- pagination can be enabled/disbled on the controller by overriding the `protected $defaultLimit = 25;` on the controller
- pagination can also be passed via the url using `limit=xx&page=y`
- pagination can also be limited to a max per page by overriding the `protected $maximumLimit = false;` parameter

## Validation

- When Posting a new record, validation can be done by adding a `rulesForCreate` method to your controller returning an array eg

```php
[
    'email' => 'required|email',
    'games' => 'required|numeric',
]
```

see https://laravel.com/docs/5.8/validation#conditionally-adding-rules

- for updating a record, add a method `rulesForUpdate` per above.

## Defaults

The following parameters are set in the Base Api controller and can be overwritten by your Controller on a case by case basis:
- `protected $resourceSingle = JsonResource::class;` Collection to use for your single resource
- `protected $resourceCollection = ResourceCollection::class;` Collection to use for your resource collection
- `protected $defaultFields = ['*'];` Default Fields to respond with
- `protected $defaultSort = null;` Set the default sorting for queries.
- `protected $defaultLimit = 25;` Number of items displayed at once if not specified. (0 = maximumLimit)
- `protected $maximumLimit = 0;` Maximum limit that can be set via \$\_GET['limit']. - this ties in with the defaultLimit aswell, and if wanting to disable pagination , both should be 0. ) will allow all records to be returned in a single call.
- `protected $unguard = false;` Do we need to unguard the model before create/update?

## Scopes

### SoftDeleted Records

add the `Phpsa\LaravelApiController\Model\Scopes\WithSoftDeletes` trait to your model,
add to your resource file:

```php
class MyModelResource extends ApiResource
{

 protected static $allowedScopes = [
        'withTrashed',
        'onlyTrashed'
    ];
```

you can now append `withTrashed=1` or `onlyTrashed=1` to your query.

## Responses

you can override responses for each point by overriding the following protected methods:

- handleIndexResponse
- handleStoreResponse
- handleShowResponse
- handleUpdateResponse
- handleDestroyResponse

## Perforance Tips

### Cache Table column definitions
-- introduced https://github.com/phpsa/laravel-api-controller/pull/118/files
add the ability to cache the table definitions to reduce calls to fetch table columns, to enable either enable in the config file or set the `PHPSA_API_CACHE_TABLE_COLUMNS` variable to true.

### Raw Pagination gets

-- handleIndexAction will use full eloquent models, handleIndexActionRaw will bypass eloquent and use raw responses from the database.

## Security

If you discover any security related issues, please email
instead of using the issue tracker.

## Credits

- [Craig G Smith](https://github.com/phpsa)
- [Sam Sehnert](https://github.com/samatcd)
- [Phil Taylor](https://github.com/codeberry)
- [All contributors](https://github.com/phpsa/laravel-api-controller/graphs/contributors)

## Sponsors

- [Custom D](https://customd.com)

[badge_laravel]: https://img.shields.io/badge/Laravel-8.7%20to%209-blue.svg
[badge_issues]: https://img.shields.io/github/issues/phpsa/laravel-api-controller
