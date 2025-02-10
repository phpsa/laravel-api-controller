<?php

namespace Phpsa\LaravelApiController;

use Illuminate\Support\ServiceProvider as BaseServiceProvider;
use Phpsa\LaravelApiController\Generator\ApiControllerMakeCommand;
use Phpsa\LaravelApiController\Generator\ApiModelMakeCommand;
use Phpsa\LaravelApiController\Generator\ApiPolicyMakeCommand;
use Phpsa\LaravelApiController\Generator\ApiResourceMakeCommand;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Http\Request;

class ServiceProvider extends BaseServiceProvider
{
    protected const CONFIG_PATH = __DIR__.'/../config/laravel-api-controller.php';

    public function boot()
    {
        $this->publishes([
            self::CONFIG_PATH => config_path('laravel-api-controller.php'),
        ], 'config');
        $this->addDbMacros();
        $this->addRequestMacros();
    }

    public function register()
    {
        $this->mergeConfigFrom(
            self::CONFIG_PATH,
            'laravel-api-controller'
        );

        $this->app->singleton('command.api.make.resource', static function ($app) {
            return new ApiResourceMakeCommand($app['files']);
        });
        $this->app->singleton('command.api.make.model', static function ($app) {
            return new ApiModelMakeCommand($app['files']);
        });
        $this->app->singleton('command.api.make.policy', static function ($app) {
            return new ApiPolicyMakeCommand($app['files']);
        });
        $this->app->singleton('command.api.make.api', static function ($app) {
            return new ApiControllerMakeCommand($app['files']);
        });

        $this->commands('command.api.make.resource');
        $this->commands('command.api.make.model');
        $this->commands('command.api.make.policy');
        $this->commands('command.api.make.api');
    }

    public function addDbMacros()
    {
        /** @macro EloquentBuilder */
        EloquentBuilder::macro('getRaw', function (array $columns = ['*']) {
            /**  @var \Illuminate\Database\Eloquent\Builder $this */
            return $this->toBase()
            ->get($columns)->map(function ($row) {
                return (array) $row;
            });
        });

         /** @macro EloquentBuilder */
        EloquentBuilder::macro('paginateRaw', function ($limit = 25, array $columns = ['*'], $pageName = 'page', $page = null) {
             /** @var \Illuminate\Database\Eloquent\Builder $this */
            $result = $this->toBase()
            ->paginate($limit, $columns, $pageName, $page);

            //@phpstan-ignore-next-line
            $collection = $result->getCollection()->map(function ($row) {
                return (array) $row;
            });

            //@phpstan-ignore-next-line
            $result->setCollection($collection);

            return $result;
        });
    }

    protected function addRequestMacros(): void
    {
          /** @macro \Illuminate\Http\Request */
        Request::macro('apiFilter', function (string $column, $operator, $value = null) {
            /** @var Request $this */
            [$value, $operator] =  func_num_args() === 2 ? [$operator, '='] : [$value, $operator];

            $filters = $this->input('filters', []);
            $filters[$column] ??= [];
            $filters[$column][$operator] = $value;

            return $this->merge([
                'filters' => $filters,
            ]);
        });

        Request::macro('apiInclude', function (string|array $relations) {
            /** @var Request $this */

            $requestField = config('laravel-api-controller.parameters.include', 'include');

            $relations = (array) $relations;

            $existing = $this->input($requestField, '');
            $includes = explode(',', $existing);

            return $this->merge([
                $requestField => implode(",", array_filter([...$includes, ...$relations], fn($val) => filled($val))),
            ]);
        });

        Request::macro('apiScope', function (string $scope, ?string $value = null) {
            /** @var Request $this */
            return $this->merge([
                $scope => $value,
            ]);
        });

        Request::macro('apiAddFields', function (string|array $fieldsOrAttributes) {
            /** @var Request $this */

            $requestField = config('laravel-api-controller.parameters.addfields', 'addfields');

            $fields = (array) $fieldsOrAttributes;

            $existingAddFields = $this->input($requestField, '');
            $existingAddFieldsArray = explode(',', $existingAddFields);

            return $this->merge([
                $requestField => implode(
                    ",",
                    array_filter(
                        [...$existingAddFieldsArray, ...$fields],
                        fn($val) => filled($val)
                    )
                ),
            ]);
        });
    }
}
