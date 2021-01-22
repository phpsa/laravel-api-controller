<?php

namespace Phpsa\LaravelApiController;

use Illuminate\Support\ServiceProvider as BaseServiceProvider;
use Phpsa\LaravelApiController\Generator\ApiControllerMakeCommand;
use Phpsa\LaravelApiController\Generator\ApiModelMakeCommand;
use Phpsa\LaravelApiController\Generator\ApiPolicyMakeCommand;
use Phpsa\LaravelApiController\Generator\ApiResourceMakeCommand;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;

class ServiceProvider extends BaseServiceProvider
{
    protected const CONFIG_PATH = __DIR__.'/../config/laravel-api-controller.php';

    public function boot()
    {
        $this->publishes([
            self::CONFIG_PATH => config_path('laravel-api-controller.php'),
        ], 'config');
        $this->addDbMacros();
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
        EloquentBuilder::macro('getRaw', function (array $columns = ['*']) {
            return $this->/** @scrutinizer ignore-call */getQuery()
            ->get($columns);
        });

        EloquentBuilder::macro('paginateRaw', function ($limit = 25, array $columns = ['*'], $pageName = 'page', $page = null) {
            return $this->/** @scrutinizer ignore-call */getQuery()
            ->paginate($limit, $columns, $pageName, $page);
        });
    }
}
