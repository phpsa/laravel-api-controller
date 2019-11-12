<?php

namespace Phpsa\LaravelApiController\Tests;

use Illuminate\Http\Request;
use Orchestra\Testbench\TestCase;
use Phpsa\LaravelApiController\Facades\LaravelApiController;
use Phpsa\LaravelApiController\ServiceProvider;
use Phpsa\LaravelApiController\UriParser;

class LaravelApiControllerTest extends TestCase
{
    protected function getPackageProviders($app)
    {
        return [ServiceProvider::class];
    }

    protected function getPackageAliases($app)
    {
        return [
            'laravel-api-controller' => LaravelApiController::class,
        ];
    }

    public function testQueryParsers()
    {
        $myRequest = $this->createRequest(
            'GET',
            '/test',
            [
                'ignore' => 'ignored',
                'sort' => 'sorted column',
                'limit' => '5',
                'filter' => [
                    'equal' => 5,
                    'greaterThan>' => '1',
                    'lessThan<' => '10',
                    'greaterEqual>=' => '11',
                    'lessEqual<=' => '20',
                    'not<>' => '15',
                    'notAgain!' => '15',
                    'contains~' => 'raig',
                    'starts^' => 'craig',
                    'ends$' => 'smith',
                    'ids' => '1||2||3||4',
                    'notin!' => '1||2||3||4',
                    'notstart!^' => 'fake',
                    'notend!$' => 'notend',
                    'notcontain!~' => 'notin',
                    'new[]' => 1,
                    'new[]!' => 2,
                ],
            ]
        );

        $parser = new UriParser($myRequest, 'filter');

        $params = $parser->whereParameters();

        $this->assertSame(17, count($params));

        $this->assertSame($parser->queryParameter('equal'), [
            'type' => 'Basic',
            'key' => 'equal',
            'operator' => '=',
            'value' => '5',
        ]);

        $this->assertSame($parser->queryParameter('greaterThan'), [
            'type' => 'Basic',
            'key' => 'greaterThan',
            'operator' => '>',
            'value' => '1',
        ]);

        $this->assertSame($parser->queryParameter('lessThan'), [
            'type' => 'Basic',
            'key' => 'lessThan',
            'operator' => '<',
            'value' => '10',
        ]);

        $this->assertSame($parser->queryParameter('greaterEqual'), [
            'type' => 'Basic',
            'key' => 'greaterEqual',
            'operator' => '>=',
            'value' => '11',
        ]);

        $this->assertSame($parser->queryParameter('lessEqual'), [
            'type' => 'Basic',
            'key' => 'lessEqual',
            'operator' => '<=',
            'value' => '20',
        ]);

        $this->assertSame($parser->queryParameter('not'), [
            'type' => 'Basic',
            'key' => 'not',
            'operator' => '!=',
            'value' => '15',
        ]);

        $this->assertSame($parser->queryParameter('notAgain'), [
            'type' => 'Basic',
            'key' => 'notAgain',
            'operator' => '!=',
            'value' => '15',
        ]);

        $this->assertSame($parser->queryParameter('contains'), [
            'type' => 'Basic',
            'key' => 'contains',
            'operator' => 'like',
            'value' => '%raig%',
        ]);

        $this->assertSame($parser->queryParameter('starts'), [
            'type' => 'Basic',
            'key' => 'starts',
            'operator' => 'like',
            'value' => 'craig%',
        ]);

        $this->assertSame($parser->queryParameter('ends'), [
            'type' => 'Basic',
            'key' => 'ends',
            'operator' => 'like',
            'value' => '%smith',
        ]);

        $this->assertSame($parser->queryParameter('ids'), [
            'type' => 'In',
            'key' => 'ids',
            'values' => [
                0 => '1',
                1 => '2',
                2 => '3',
                3 => '4',
            ],
        ]);

        $this->assertSame($parser->queryParameter('notin'), [
            'type' => 'NotIn',
            'key' => 'notin',
            'values' => [
                0 => '1',
                1 => '2',
                2 => '3',
                3 => '4',
            ],
        ]);

        $this->assertSame($parser->queryParameter('new'), [
            [
                'type' => 'In',
                'key' => 'new',
                'values' => [
                    '1',
                ],
            ],
            [
                'type' => 'NotIn',
                'key' => 'new',
                'values' => [
                    '2',
                ],
            ],
        ]);

        $this->assertSame($parser->queryParameter('notstart'), [
            'type' => 'Basic',
            'key' => 'notstart',
            'operator' => 'not like',
            'value' => 'fake%',
        ]);

        $this->assertSame($parser->queryParameter('notend'), [
            'type' => 'Basic',
            'key' => 'notend',
            'operator' => 'not like',
            'value' => '%notend',
        ]);

        $this->assertSame($parser->queryParameter('notcontain'), [
            'type' => 'Basic',
            'key' => 'notcontain',
            'operator' => 'not like',
            'value' => '%notin%',
        ]);
    }

    protected function createRequest(
        $method,
        $uri = '/test',
        $parameters = [],
        $content = '',
        $server = ['CONTENT_TYPE' => 'application/json'],
        $cookies = [],
        $files = []
    ) {
        $request = new Request;

        return $request->createFromBase(
            \Symfony\Component\HttpFoundation\Request::create(
                $uri,
                $method,
                $parameters,
                $cookies,
                $files,
                $server,
                $content
            )
        );
    }
}
