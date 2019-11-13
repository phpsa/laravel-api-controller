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

      /*  $name = ucfirst($this->argument('name'));
        $options = $this->options();

        dd($name);

        if($options['model'] === true || $options['all'] === true){
            $this->call('make:model', ['name' => $name]);

            if ($this->anticipate('Would you like to create a Migration for this resource?', ['yes', 'no']) == 'yes') {
                $migrationName = Str::snake(Str::pluralStudly($name));
                $this->call('make:migration', ['name' => "create_{$migrationName}_table"]);

                if ($this->anticipate('Would you like to create a Seeder for this resource?', ['yes', 'no']) == 'yes') {
                    $seederName = Str::plural($name) . 'Seeder';
                    $this->call('make:seeder', ['name' => $seederName]);
                    $this->line('Please add the following to your DatabaseSeeder.php file', 'important');
                    $this->line('$this->call('. $seederName .'::class);', 'code');
                    $this->line(PHP_EOL);
                }
            }
        }

        dd($this->argument('name'), $this->options()); */

        $this->prepareVariablesForStubs($this->argument('name'));
        $this->createOptionals();
        $this->createController();
        $this->addRoutes();
    }

    protected function createOptionals()
    {
        if ($this->option('model') || $this->option('all')) {
            $this->call('make:model', ['name' => $this->stubVariables['model']['fullNameWithoutRoot']]);
            if ($this->anticipate('Would you like to create a Migration for this resource?', ['yes', 'no']) == 'yes') {
                $migrationName = $this->stubVariables['model']['migration'];
                $this->call('make:migration', ['name' => "create_{$migrationName}_table"]);

                if ($this->anticipate('Would you like to create a Seeder for this resource?', ['yes', 'no']) == 'yes') {
                    $seederName = $this->stubVariables['model']['fullNameWithoutRoot'].'Seeder';
                    $this->call('make:seeder', ['name' => $seederName]);
                    $this->line('Please add the following to your DatabaseSeeder.php file', 'important');
                    $this->line('$this->call('.$seederName.'::class);', 'code');
                    $this->line(PHP_EOL);
                }
            }
        }

        if ($this->option('all') || $this->option('policy')) {
            $this->call('make:policy', ['name' => $this->stubVariables['model']['name'].'Policy', '--model' => $this->stubVariables['model']['fullNameWithoutRoot']]);
        }

        if ($this->option('all') || $this->option('resource')) {
            $this->call('make:resource', ['name' => $this->stubVariables['model']['name']]);
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
        $this->files->put($path, $this->constructStub(base_path(config('laravel-api-controller.'.$type.'_stub'))));
        $this->info(ucfirst($type).' created successfully.');
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
