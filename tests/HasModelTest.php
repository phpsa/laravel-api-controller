<?php

namespace Phpsa\LaravelApiController\Tests;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\App;
use Illuminate\Database\Eloquent\Builder;
use function PHPUnit\Framework\assertTrue;
use function PHPUnit\Framework\assertEquals;
use Phpsa\LaravelApiController\ServiceProvider;
use Phpsa\LaravelApiController\Tests\Models\User;

use function PHPUnit\Framework\assertArrayNotHasKey;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Phpsa\LaravelApiController\Http\Api\Contracts\HasModel;
use Phpsa\LaravelApiController\Tests\Controllers\UserController;

class HasModelTest extends TestCase
{

    public function test_model_init_when_called()
    {
        $class = App::make(UserController::class);

        $model = self::getMethod($class, 'getModel');
        $this->assertInstanceOf(User::class, $model->invoke($class));

        $model = self::getProperty($class, 'model');
        $this->assertInstanceOf(User::class, $model->getValue($class));

        $builder = self::getMethod($class, 'getBuilder');
        $this->assertInstanceOf(Builder::class, $builder->invoke($class));

        $builder = self::getProperty($class, 'builder');
        $this->assertInstanceOf(Builder::class, $builder->getValue($class));
    }

    public function test_controller_makes_model_query_builder()
    {
        // $class->makeModel();

       // $mock = $this->getMockForTrait(HasModel::class);
      //  $mock->expects($this->any())->method('makeModel')->willReturn(true);
        $class = App::make(UserController::class);
        $this->assertInstanceOf(UserController::class, $class);

        $makeModel = self::getMethod($class, 'getModel');
        $makeModel->invoke($class);

        $model = self::getProperty($class, 'model');
        $this->assertInstanceOf(User::class, $model->getValue($class));

        $builder = self::getMethod($class, 'getBuilder');
        $this->assertInstanceOf(Builder::class, $builder->invoke($class));

        $addTableData = self::getMethod($class, 'addTableData');

        $res = $addTableData->invokeArgs($class, [
            ['id' => 5, 'age' => 10, 'email' => 'bobo@bobo.com']
        ]);

        assertArrayNotHasKey('age', $res);
        $this->assertArrayHasKey('email', $res);

        $getUnqualifiedTableName = self::getMethod($class, 'getUnqualifiedTableName');
        assertEquals('users', $getUnqualifiedTableName->invoke($class));
    }


    public function test_db_macros()
    {

        factory(User::class, 100)->create();
        $result = User::where('id', '<', 10)->getRaw();
        $this->assertInstanceOf(Collection::class, $result);
        $this->assertEquals(9, $result->count());

        $paged = User::paginateRaw(5)->appends(['age>' => 5]);
        $this->assertInstanceOf(LengthAwarePaginator::class, $paged);
        $this->assertEquals(100, $paged->total());
        $this->assertIsArray($paged->items());
    }
}
