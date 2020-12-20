<?php

namespace Phpsa\LaravelApiController\Generator;

use Illuminate\Console\GeneratorCommand;
use Illuminate\Foundation\Console\ModelMakeCommand as Command;
use Illuminate\Support\Str;
use Symfony\Component\Console\Input\InputOption;

class ApiModelMakeCommand extends Command
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'make:api:model';

    protected $type = 'Model';

    public function handle()
    {
        if (GeneratorCommand::handle() === false && ! $this->option('force')) {
            return false;
        }

        if ($this->confirm('Do you wish to generate a factory?')) {
            $this->createFactory();
        }

        if ($this->confirm('Do you wish to generate a migration?')) {
            $this->createMigration();
        }

        if ($this->confirm('Do you wish to generate a seeder?')) {
            $this->createSeeder();
        }

        if ($this->confirm('Do you wish to generate a policy?')) {
            $this->createPolicy();
        }
    }

    /**
     * Create a migration file for the model.
     *
     * @return void
     */
    protected function createMigration()
    {
        $table = Str::snake(Str::pluralStudly(class_basename(/** @scrutinizer ignore-type */ $this->argument('name'))));

        $this->call('make:migration', [
            'name'     => "create_{$table}_table",
            '--create' => $table,
        ]);
    }

    /**
     * Create a seeder file for the model.
     *
     * @return void
     */
    protected function createPolicy()
    {
        $name = Str::studly(class_basename($this->getNameInput()));
        $modelName = ($this->qualifyClass($this->getNameInput()));
        $policy = rtrim($modelName, $name).'Policies\\'.$name;

        $this->call('make:api:policy', [
            'name'    => "{$policy}Policy",
            '--model' => $modelName,
        ]);
    }

    /**
     * Get the stub file for the generator.
     *
     * @return string
     */
    protected function getStub()
    {
        return $this->resolveStubPath('/stubs/model.stub');
    }

    /**
     * Resolve the fully-qualified path to the stub.
     *
     * @param  string  $stub
     * @return string
     */
    protected function resolveStubPath($stub)
    {
        return file_exists($customPath = $this->laravel->basePath(trim($stub, '/')))
        ? $customPath
        : __DIR__.$stub;
    }

    protected function getOptions()
    {
        return [
            ['force', null, InputOption::VALUE_NONE, 'Create the class even if the model already exists'],
        ];
    }
}
