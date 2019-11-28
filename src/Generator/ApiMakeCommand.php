<?php

namespace Phpsa\LaravelApiController\Generator;

use Illuminate\Console\Command;
use Illuminate\Console\DetectsApplicationNamespace;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Str;
use Symfony\Component\Console\Formatter\OutputFormatterStyle;
use Symfony\Component\Console\Input\InputArgument;

class ApiMakeCommand extends Command
{
    use DetectsApplicationNamespace;

    /**
     * The filesystem instance.
     *
     * @var \Illuminate\Filesystem\Filesystem
     */
    protected $files;

    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'make:api';

    protected $signature = 'make:api
                        {name : The name of the model}
                        {--M|model : create the model}
                        {--R|resource : create a resource}
                        {--P|policy : create a policy}
                        {--A|all : create all requirements}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create api controller and api routes for a given model (phpsa/laravel-api-controller)';

    /**
     * The array of variables available in stubs.
     *
     * @var array
     */
    protected $stubVariables = [
        'app' => [],
        'model' => [],
        'controller' => [],
        'route' => [],
    ];

    protected $modelsBaseNamespace;

    /**
     * Create a new controller creator command instance.
     *
     * @param \Illuminate\Filesystem\Filesystem $files
     */
    public function __construct(Filesystem $files)
    {
        parent::__construct();
        $this->files = $files;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->prepareVariablesForStubs($this->argument('name'));
        $this->createOptionals();
        $this->createController();
        $this->addRoutes();
    }

    protected function createOptionals()
    {
        if ($this->option('model') || $this->option('all')) {
            $params = ['name' => $this->stubVariables['model']['fullName']];

            if ($this->confirm('Would you like to create a Migration for this resource?')) {
                $params['--migration'] = true;

                if ($this->confirm('Would you like to create a Seeder for this resource?')) {
                    $seederName = $this->stubVariables['model']['fullNameWithoutRoot'].'Seeder';
                    $this->call('make:seeder', ['name' => $seederName]);
                    $this->line('Please add the following to your DatabaseSeeder.php file', 'important');
                    $this->line('$this->call('.$seederName.'::class);', 'code');
                    $this->line(PHP_EOL);
                }
            }
            $this->call('make:model', $params);
        }

        if ($this->option('all') || $this->option('policy')) {
            $this->call('make:policy', ['name' => $this->stubVariables['model']['name'].'Policy', '--model' => $this->stubVariables['model']['fullName']]);
        }

        if ($this->option('all') || $this->option('resource')) {
            $this->call('make:resource', ['name' => $this->stubVariables['model']['name']]);
            $this->call('make:resource', ['name' => $this->stubVariables['model']['name'].'Collection']);
        }
    }

    /**
     * Prepare names, paths and namespaces for stubs.
     *
     * @param $name
     */
    protected function prepareVariablesForStubs($name)
    {
        $this->stubVariables['app']['namespace'] = $this->getAppNamespace();
        $baseDir = config('laravel-api-controller.models_base_dir');
        $this->modelsBaseNamespace = $baseDir ? trim($baseDir, '\\').'\\' : '';
        $this->setModelData($name)
            ->setControllerData()
            ->setRouteData();
    }

    /**
     * Set the model name and namespace.
     *
     * @return $this
     */
    protected function setModelData($name)
    {
        if (Str::contains($name, '/')) {
            $name = $this->convertSlashes($name);
        }
        $name = trim($name, '\\');
        $this->stubVariables['model']['fullNameWithoutRoot'] = $name;
        $this->stubVariables['model']['fullName'] = $this->stubVariables['app']['namespace'].$this->modelsBaseNamespace.$name;
        $exploded = explode('\\', $this->stubVariables['model']['fullName']);
        $this->stubVariables['model']['name'] = array_pop($exploded);
        $this->stubVariables['model']['namespace'] = implode('\\', $exploded);
        $exploded = explode('\\', $this->stubVariables['model']['fullNameWithoutRoot']);
        array_pop($exploded);
        $this->stubVariables['model']['additionalNamespace'] = implode('\\', $exploded);

        $name = str_replace('\\', '', $this->stubVariables['model']['fullNameWithoutRoot']);
        $name = Str::snake($name);
        $this->stubVariables['model']['migration'] = Str::singular($name);

        return $this;
    }

    /**
     * Set the controller names and namespaces.
     *
     * @return $this
     */
    protected function setControllerData()
    {
        return $this->setDataForEntity('controller');
    }

    /**
     * Set route data for a given model.
     * "Profile\Payer" -> "profile_payers".
     *
     * @return $this
     */
    protected function setRouteData()
    {
        $name = str_replace('\\', '', $this->stubVariables['model']['fullNameWithoutRoot']);
        $name = Str::snake($name);
        $this->stubVariables['route']['name'] = Str::plural($name);

        return $this;
    }

    /**
     *  Set entity's names and namespaces.
     *
     * @param string $entity
     *
     * @return $this
     */
    protected function setDataForEntity($entity)
    {
        $entityNamespace = $this->convertSlashes(config("laravel-api-controller.{$entity}s_dir"));
        $this->stubVariables[$entity]['name'] = $this->stubVariables['model']['name'].ucfirst($entity);
        $this->stubVariables[$entity]['namespaceWithoutRoot'] = implode('\\', array_filter([
            $entityNamespace,
            $this->stubVariables['model']['additionalNamespace'],
        ]));
        $this->stubVariables[$entity]['namespaceBase'] = $this->stubVariables['app']['namespace'].$entityNamespace;
        $this->stubVariables[$entity]['namespace'] = $this->stubVariables['app']['namespace'].$this->stubVariables[$entity]['namespaceWithoutRoot'];
        $this->stubVariables[$entity]['fullNameWithoutRoot'] = $this->stubVariables[$entity]['namespaceWithoutRoot'].'\\'.$this->stubVariables[$entity]['name'];
        $this->stubVariables[$entity]['fullName'] = $this->stubVariables[$entity]['namespace'].'\\'.$this->stubVariables[$entity]['name'];

        return $this;
    }

    /**
     *  Create controller class file from a stub.
     */
    protected function createController()
    {
        $this->createClass('controller');
    }

    /**
     *  Add routes to routes file.
     */
    protected function addRoutes()
    {
        $stub = $this->constructStub(base_path(config('laravel-api-controller.route_stub')));
        $routesFile = app_path(config('laravel-api-controller.routes_file'));
        // read file
        $lines = file($routesFile);

        if (! $lines) {
            //@todo - better error handling here
            return false;
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
            //@todo - better error handling here
            return false;
        }
        fwrite($fileResource, implode('', $lines));
        fclose($fileResource);
        $this->info('Routes added successfully.');
    }

    /**
     * Create class with a given type.
     *
     * @param $type
     *
     * @throws \Illuminate\Contracts\Filesystem\FileNotFoundException
     */
    protected function createClass($type)
    {
        $path = $this->getPath($this->stubVariables[$type]['fullNameWithoutRoot']);

        if ($this->files->exists($path)) {
            $this->error(ucfirst($type).' already exists!');

            return;
        }
        $this->makeDirectoryIfNeeded($path);
        $fileContent = $this->constructStub(base_path(config('laravel-api-controller.'.$type.'_stub')));

        if ($type === 'controller' && ($this->option('all') || $this->option('resource'))) {
            $resourceName = $this->stubVariables['model']['fullName'];
            $resourceCollection = $resourceName.'Collection';

            $fileContent = str_replace('protected $includesBlacklist = [];', 'protected $includesBlacklist = [];
            /**
     * Resource for item.
     *
     * @var mixed instance of \Illuminate\Http\Resources\Json\JsonResource
     *
     *
     */
    protected $resourceSingle = \\'.$resourceName.';

    /**
     * Resource for collection.
     *
     * @var mixed instance of \Illuminate\Http\Resources\Json\ResourceCollection
     *
     */
    protected $resourceCollection = \\'.$resourceCollection.';

            ', $fileContent);
        }

        $this->files->put($path, $fileContent);
        $this->info(ucfirst($type).' created successfully.');

        /*
        /**
     * Resource for item.
     *
     * @var mixed instance of \Illuminate\Http\Resources\Json\JsonResource
     *
    protected $resourceSingle = TenantReviewResource::class;

    /**
     * Resource for collection.
     *
     * @var mixed instance of \Illuminate\Http\Resources\Json\ResourceCollection
     *
    protected $resourceCollection = TenantReviewCollection::class;

    */
    }

    /**
     * Get the destination file path.
     *
     * @param string $name
     *
     * @return string
     */
    protected function getPath($name)
    {
        $name = str_replace($this->stubVariables['app']['namespace'], '', $name);

        return $this->laravel['path'].'/'.str_replace('\\', '/', $name).'.php';
    }

    /**
     * Build the directory for the class if needed.
     *
     * @param string $path
     *
     * @return string
     */
    protected function makeDirectoryIfNeeded($path)
    {
        if (! $this->files->isDirectory(dirname($path))) {
            $this->files->makeDirectory(dirname($path), 0777, true, true);
        }
    }

    /**
     * Get stub content and replace all stub placeholders
     * with data from $this->stubData.
     *
     * @param string $path
     *
     * @return string
     */
    protected function constructStub($path)
    {
        $stub = $this->files->get($path);
        foreach ($this->stubVariables as $entity => $fields) {
            foreach ($fields as $field => $value) {
                $stub = str_replace("{{{$entity}.{$field}}}", $value, $stub);
            }
        }

        return $stub;
    }

    /**
     * Get the console command arguments.
     *
     * @return array
     */
    protected function getArguments()
    {
        return [
            ['name', InputArgument::REQUIRED, 'The name of the model'],
        ];
    }

    /**
     * Convert "/" to "\".
     *
     * @param $string
     *
     * @return string
     */
    protected function convertSlashes($string)
    {
        return str_replace('/', '\\', $string);
    }

    /**
     * Setup styles for command.
     */
    protected function setupStyles()
    {
        $style = new OutputFormatterStyle('yellow', 'black', ['bold']);
        $this->output->getFormatter()->setStyle('important', $style);
        $style = new OutputFormatterStyle('cyan', 'black', ['bold']);
        $this->output->getFormatter()->setStyle('code', $style);
    }
}
