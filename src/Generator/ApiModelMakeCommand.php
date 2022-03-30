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
            return;
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
     * Create a seeder file for the model.
     *
     * @return void
     */
    protected function createPolicy()
    {
        $name = Str::studly(class_basename($this->getNameInput()));
        $modelName = ($this->qualifyClass($this->getNameInput()));

        $this->call('make:api:policy', [
            'name'    => "{$name}Policy",
            '--model' => $modelName,
        ]);
    }

    protected function getOptions()
    {
        return [
            ['force', null, InputOption::VALUE_NONE, 'Create the class even if the model already exists'],
        ];
    }
}
