<?php

namespace Phpsa\LaravelApiController\Tests;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\App;
use Phpsa\LaravelApiController\Http\Api\Contracts\HasModel;
use Phpsa\LaravelApiController\Tests\Controllers\UserController;
use Phpsa\LaravelApiController\Tests\Models\User;

use function PHPUnit\Framework\assertArrayNotHasKey;
use function PHPUnit\Framework\assertEquals;
use function PHPUnit\Framework\assertTrue;

class HasModelTest extends TestCase
{


    public function test_controller_makes_model_query_builder()
    {
        // $class->makeModel();

       // $mock = $this->getMockForTrait(HasModel::class);
      //  $mock->expects($this->any())->method('makeModel')->willReturn(true);
        $class = App::make(UserController::class);
        $this->assertInstanceOf(UserController::class, $class);

        $makeModel = self::getMethod($class, 'makeModel');
        $makeModel->invoke($class);

        $model = self::getProperty($class, 'model');
        $this->assertInstanceOf(User::class, $model->getValue($class));

        $builder = self::getProperty($class, 'builder');
        $this->assertInstanceOf(Builder::class, $builder->getValue($class));

        $addTableData = self::getMethod($class, 'addTableData');

        $res = $addTableData->invokeArgs($class, [
            ['id' => 5, 'age' => 10, 'email' => 'bobo@bobo.com']
        ]);

        assertArrayNotHasKey('age', $res);
        $this->assertArrayHasKey('email', $res);

        $getUnqualifiedTableName = self::getMethod($class, 'getUnqualifiedTableName');
        assertEquals('users', $getUnqualifiedTableName->invoke($class));
    }


    // public function testFoo()
    // {
    //     $foo = self::getMethod('foo');
    //     $obj = new MyClass();
    //     $foo->invokeArgs($obj, [...]);
    //     ...
    // }
}
