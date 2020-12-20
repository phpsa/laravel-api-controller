<?php

namespace Phpsa\LaravelApiController\Generator;

use Illuminate\Foundation\Console\ResourceMakeCommand as Command;

class ApiResourceMakeCommand extends Command
{
    protected $name = 'make:api:resource';

    protected $description = 'Create a new api resource (phpsa/laravel-api-controller)';

    /**
     * Get the stub file for the generator.
     *
     * @return string
     */
    protected function getStub()
    {
        return $this->collection()
                    ? __DIR__.'/stubs/resource-collection.stub'
                    : __DIR__.'/stubs/resource.stub';
    }
}
