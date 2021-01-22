<?php

namespace Phpsa\LaravelApiController\Generator;

use Illuminate\Routing\Console\ControllerMakeCommand;
use Illuminate\Support\Str;
use Symfony\Component\Console\Input\InputOption;

class ApiControllerMakeCommand extends ControllerMakeCommand
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'make:api';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new controller class';

    protected $customClasses = [
        'request'            => false,
        'rawName'            => null,
        'resources'          => false,
        'resourceSingle'     => null,
        'resourceCollection' => null,
    ];

    public function handle()
    {
        $this->setRawName();

        if (empty($this->option('model'))) {
            $this->input->setOption('model', $this->ask('For which model?', $this->customClasses['rawName']));
        }

        $this->customClasses['request'] = $this->option('request') ?: $this->confirm('Add Custom Request?');

        if ($this->confirm('Add Custom Resource & Collection?')) {
            $this->customClasses['resources'] = true;
            $this->customClasses['resourceSingle'] = $this->customClasses['rawName'].'Resource';
            $this->customClasses['resourceCollection'] = $this->customClasses['rawName'].'ResourceCollection';
        }

        $this->confirmModelExists();

        parent::handle();

        if ($this->confirm('Add Feature Test?')) {
            $this->call('make:test', ['name' =>  $this->customClasses['rawName'].'Test']);
        }

        $this->addRoutes();

        $this->info('Complete');
    }

    protected function setRawName()
    {
        $name = substr($this->getNameInput(), -10) === 'Controller' ? substr($this->getNameInput(), 0, -10) : $this->getNameInput();
        $this->customClasses['rawName'] = class_basename($name);
    }

    protected function getRequestClass(): string
    {
        return  $this->customClasses['request'] === false ? '\\Illuminate\\Http\\Request' : $this->makeRequestClass();
    }

    protected function makeRequestClass()
    {
        $class = $this->customClasses['rawName'].'Request';

        $this->call('make:request', [
            'name' => $class,
        ]);

        return 'App\\Http\\Requests\\'.$class;
    }

    protected function confirmModelExists()
    {
        $modelClass = $this->parseModel(/** @scrutinizer ignore-type */ $this->option('model'));

        if (! class_exists($modelClass, false)) {
            if ($this->confirm("A {$modelClass} model does not exist! Do you want to generate it?", true)) {
                $this->call('make:api:model', ['name' => $modelClass]);
            }

            return;
        }

        $this->confirmModelPolicyExists();
    }

    protected function confirmModelPolicyExists()
    {
        $modelClass = $this->parseModel(/** @scrutinizer ignore-type */ $this->option('model'));
        $model = class_basename($modelClass);
        $policyClass = rtrim($modelClass, $model).'Policies\\'.$model.'Policy';
        if (! class_exists($policyClass)) {
            if ($this->confirm("A {$policyClass} Policy does not exist. Do you want to generate it?", true)) {
                $this->call('make:api:policy', ['name' => $policyClass, '--model' => '\\'.$modelClass]);
            }
        }
    }

    /**
     * Build the model replacement values.
     *
     * @param  array  $replace
     * @return array
     */
    protected function buildModelReplacements(array $replace)
    {
        $requestClass = $this->getRequestClass();
        $useResourceSingle = $this->getUseResourceSingle();
        $useResourceCollection = $this->getUseResourceCollection();

        return array_merge(parent::buildModelReplacements($replace), [
            '{{namespaceRequest}}'        => $requestClass,
            '{{request}}'                 => class_basename($requestClass),
            '{{ namespaceRequest }}'      => $requestClass,
            '{{ request }}'               => class_basename($requestClass),
            '{{useResourceSingle}}'       => $useResourceSingle,
            '{{useResourceCollection}}'   => $useResourceCollection,
            '{{ useResourceSingle }}'     => $useResourceSingle,
            '{{ useResourceCollection }}' => $useResourceCollection,
            '{{resourceSingle}}'          => $this->customClasses['resourceSingle'] ? 'protected $resourceSingle = '.$this->customClasses['resourceSingle'].":class;\n" : null,
            '{{resourceCollection}}'      => $this->customClasses['resourceCollection'] ? 'protected $resourceCollection = '.$this->customClasses['resourceCollection'].":class;\n" : null,
            '{{ resourceSingle }}'        => $this->customClasses['resourceSingle'] ? 'protected $resourceSingle = '.$this->customClasses['resourceSingle'].":class;\n" : null,
            '{{ resourceCollection }}'    => $this->customClasses['resourceCollection'] ? 'protected $resourceCollection = '.$this->customClasses['resourceCollection'].":class;\n" : null,
        ]);
    }

    protected function getUseResourceSingle()
    {
        if (! $this->customClasses['resources']) {
            return null;
        }

        $this->call('make:api:resource', ['name' => $this->customClasses['resourceSingle']]);

        return 'use App\Http\\Resources\\'.$this->customClasses['resourceSingle'].';';
    }

    protected function getUseResourceCollection()
    {
        if (! $this->customClasses['resources']) {
            return null;
        }

        $this->call('make:api:resource', ['name' => $this->customClasses['resourceCollection']]);

        return 'use App\Http\\Resources\\'.$this->customClasses['resourceCollection'].';';
    }

    protected function getStub()
    {
        $stub = 'controller.api.stub';

        if ($this->option('parent')) {
            $stub = 'controller.nested.stub';
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

    /**
     * Get the console command options.
     *
     * @return array
     */
    protected function getOptions()
    {
        return [
            ['force', null, InputOption::VALUE_NONE, 'Create the class even if the controller already exists'],
            ['request', 'r', InputOption::VALUE_NONE, 'Create a request class for the put/post requests'],
            ['model', 'm', InputOption::VALUE_OPTIONAL, 'Generate a resource controller for the given model.'],
            ['parent', 'p', InputOption::VALUE_OPTIONAL, 'Generate a nested resource controller class.'],
        ];
    }

    protected function addRoutes()
    {
        $stub = $this->resolveStubPath('route.stub');
        $routesFile = app_path(config('laravel-api-controller.routes_file'));

        $name = $this->qualifyClass($this->getNameInput());

        $replacements = [
            '{{route.name}}'       => strtolower(Str::plural($this->customClasses['rawName'])),
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

            return false;
        }

        // read file
        $lines = file($routesFile);

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

            return false;
        }
        fwrite($fileResource, implode('', $lines));
        fclose($fileResource);
        $this->info('Routes added successfully.');
    }
}
