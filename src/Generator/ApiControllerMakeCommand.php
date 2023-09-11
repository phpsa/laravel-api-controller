<?php

namespace Phpsa\LaravelApiController\Generator;

use Illuminate\Support\Str;
use Illuminate\Support\Facades\File;
use Symfony\Component\Console\Input\InputOption;
use Illuminate\Routing\Console\ControllerMakeCommand;

class ApiControllerMakeCommand extends ControllerMakeCommand
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'make:api:controller';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new controller class';


    public function handle()
    {

        if (empty($this->option('model'))) {
            $this->input->setOption('model', $this->ask('For which model?', Str::replace("Controller", "", $this->getNameInput())));
        }

        parent::handle();

        $this->addRoutes();

        $this->info('Complete');
    }


    protected function qualifyResource(string $resource): string
    {
        $resource = ltrim($resource, '\\/');

        $resource = str_replace('/', '\\', $resource);

        $rootNamespace = $this->rootNamespace();

        if (Str::startsWith($resource, $rootNamespace)) {
            return $resource;
        }

        return $rootNamespace.'Http\\Resources\\'.$resource;
    }



    protected function buildModelReplacements(array $replace)
    {
        /** @var string $model */
        $model = $this->option('model');

        $modelClass = $this->parseModel($model);

        if (! class_exists($modelClass)) {
            if ($this->confirm("A {$modelClass} model does not exist. Do you want to generate it?", true)) {
                $opts = ['name' => $modelClass];
                $opts['--migration'] = $this->confirm("Create a new migration file for the model", false);
                $opts['--policy'] = $this->confirm("Create a new policy for the model", false);
                $opts['--seed'] = $this->confirm("Create a new seed for the model", false);
                $opts['--factory'] = $this->confirm("Create a new factory for the model?", false);

                $this->call('make:api:model', $opts);
            }
        }

        $resourceClass = $this->qualifyResource($model . 'Resource');
        if (! class_exists($resourceClass)) {
            $this->call('make:api:resource', ['name' => $model . 'Resource']);
        }

        $resourceCollection = $this->qualifyResource($model . 'Collection');
        if (! class_exists($resourceCollection)) {
            $this->call('make:api:resource', ['name' => $model . 'Collection']);
        }

        $replace = $this->buildFormRequestReplacements($replace, $modelClass);

        return array_merge($replace, [
            'DummyFullModelClass'         => $modelClass,
            '{{ namespacedModel }}'       => $modelClass,
            '{{namespacedModel}}'         => $modelClass,
            'DummyModelClass'             => class_basename($modelClass),
            '{{ model }}'                 => class_basename($modelClass),
            '{{model}}'                   => class_basename($modelClass),
            'DummyModelVariable'          => lcfirst(class_basename($modelClass)),
            '{{ modelVariable }}'         => lcfirst(class_basename($modelClass)),
            '{{modelVariable}}'           => lcfirst(class_basename($modelClass)),
            '{{useResourceSingle}}'       => $resourceClass,
            '{{useResourceCollection}}'   => $resourceCollection,
            '{{ useResourceSingle }}'     => $resourceClass,
            '{{ useResourceCollection }}' => $resourceCollection,
            '{{resourceSingle}}'          => class_basename($resourceClass),
            '{{resourceCollection}}'      => class_basename($resourceCollection),
            '{{ resourceSingle }}'        => class_basename($resourceClass),
            '{{ resourceCollection }}'    => class_basename($resourceCollection),
        ]);
    }



    protected function getStub()
    {
        $stub = 'controller.api.stub';

        if ($this->option('parent') && $this->option('soft-deletes')) {
            $stub = 'controller.nested.api.soft-deletes.stub';
        }
        if ($this->option('parent')) {
            $stub = 'controller.nested.api.stub';
        }
        if($this->option('soft-deletes')) {
            $stub = 'controller.api.soft-deletes.stub';
        }

        return $this->resolveStubPath($stub);
    }

    /**
     * Resolve the fully-qualified path to the stub.
     *
     * @param  string  $stub
     * @return string
     */
    protected function resolveStubPath($stub)
    {
        return file_exists($customPath = $this->laravel->basePath('api/'.trim($stub, '/')))
        ? $customPath
        : __DIR__.'/stubs/'.$stub;
    }

    /**
     * Get the default namespace for the class.
     *
     * @param  string  $rootNamespace
     * @return string
     */
    protected function getDefaultNamespace($rootNamespace)
    {
        return $rootNamespace.'\Http\Controllers\Api';
    }


    protected function addRoutes(): void
    {
        $stub = $this->resolveStubPath($this->option('soft-deletes') ? 'route.soft-deletes.stub' : 'route.stub');
        $routesFile = app_path(config('laravel-api-controller.routes_file'));

        $name = $this->qualifyClass($this->getNameInput());

        $replacements = [
            '{{route.name}}'       => strtolower(Str::plural($this->option('model'))),
            '{{route.controller}}' => '\\'.$name.'::class',
        ];

        $stub = $this->files->get($stub);

        $stub = str_replace(
            array_keys($replacements),
            $replacements,
            $stub
        );

        if (! File::exists($routesFile)) {
            $this->error('could not read routes file, add the following to your routes file:');
            $this->info("\n".$stub."\n");

            return;
        }

        // read file
        $lines = file($routesFile);
        if ($lines === false) {
            return;
        }

        $lastLine = trim($lines[count($lines) - 1]);
        // modify file
        if (strcmp($lastLine, '});') === 0) {
            $lines[count($lines) - 1] = '    '.$stub;
            $lines[] = "\r\n});\r\n";
        } else {
            $lines[] = "$stub\r\n";
        }
        // save file
        $fileResource = fopen($routesFile, 'w');

        if (! is_resource($fileResource)) {
            $this->error('could not read routes file, add the following to your routes file:');
            $this->info("\n".$stub."\n");

            return;
        }
        fwrite($fileResource, implode('', $lines));
        fclose($fileResource);
        $this->info('Routes added successfully.');
    }

    /**
     * Get the console command options.
     *
     * @return array
     */
    protected function getOptions()
    {
        $options = parent::getOptions();
        $options[] =  ['soft-deletes', null, InputOption::VALUE_NONE, 'Enable Soft Deletes'];
        return $options;
    }
}
