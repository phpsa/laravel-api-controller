<?php

namespace Phpsa\LaravelApiController;

use Illuminate\Support\ServiceProvider;
use Phpsa\LaravelApiController\Generator\ApiControllerMakeCommand;
use Phpsa\LaravelApiController\Generator\ApiModelMakeCommand;
use Phpsa\LaravelApiController\Generator\ApiPolicyMakeCommand;
use Phpsa\LaravelApiController\Generator\ApiResourceMakeCommand;

class ServiceProvider extends ServiceProvider
{
    protected const CONFIG_PATH = __DIR__.'/../config/laravel-api-controller.php';

    public function boot()
    {
        $this->publishes([
            self::CONFIG_PATH => config_path('laravel-api-controller.php'),
        ], 'config');
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
}
