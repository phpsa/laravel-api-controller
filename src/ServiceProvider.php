<?php

namespace Phpsa\LaravelApiController;

use Phpsa\LaravelApiController\Generator\ApiMakeCommand;
use Phpsa\LaravelApiController\Generator\ApiResourceMakeCommand;

class ServiceProvider extends \Illuminate\Support\ServiceProvider
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

        $this->app->singleton('command.api.make.controller', static function ($app) {
            return new ApiMakeCommand($app['files']);
        });
        $this->app->singleton('command.api.make.resource', static function ($app) {
            return new ApiResourceMakeCommand($app['files']);
        });

        $this->commands('command.api.make.controller');
        $this->commands('command.api.make.resource');
    }
}
