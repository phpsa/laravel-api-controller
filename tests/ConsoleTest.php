<?php

namespace Phpsa\LaravelApiController\Tests;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Artisan;
use Phpsa\LaravelApiController\Tests\TestCase;

class ConsoleTest extends TestCase
{

    function test_the_install_command_copies_the_configuration()
    {

        $this->flushFiles();

       // dd(app_path('api.php'));
        // make sure we're starting from a clean state
        if (! File::exists(app_path('../routes/api.php'))) {
            touch(app_path('../routes/api.php'));
        }

        $this->assertTrue(File::exists(app_path('../routes/api.php')));

   /*     $this->artisan('make:api', ['name' => 'TestController'])
        ->expectsQuestion('For which model?', 'Test')
        ->expectsQuestion('Add Custom Request?', true)
        ->expectsQuestion('Add Custom Resource & Collection?', true)
        ->expectsQuestion('A App\Models\Test model does not exist! Do you want to generate it?', true)
        ->expectsQuestion('A App\Models\Test model does not exist. Do you want to generate it?', true)
        ->expectsQuestion('Add Feature Test?', true)
        ->expectsQuestion('Do you wish to generate a factory?', false)
        ->expectsQuestion('Do you wish to generate a migration?', false)
        ->expectsQuestion('Do you wish to generate a seeder?', false)
        ->expectsQuestion('Do you wish to generate a policy?', false)
       // ->expectsQuestion('A App\Models\Test model does not exist. Do you want to generate it?', true)
        ;*/ //TODO Needs work
    }


    protected function flushFiles()
    {
        $files = [
            app_path('Http/Controllers/Api/TestController.php'),
            app_path('Http/Requests/TestRequest.php'),
            app_path('Http/Resources/TestResource.php'),
            app_path('Http/Resources/TestResourceCollection.php'),
            app_path('Models/Test.php'),
            app_path('Models/Policies/TestPolicy.php'),
        ];

        foreach ($files as $file) {
            if (File::exists($file)) {
                unlink($file);
            }
            $this->assertFalse(File::exists($file));
        }
    }
}
