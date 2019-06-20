<?php

namespace Phpsa\LaravelApiController\Tests;

use Phpsa\LaravelApiController\Facades\LaravelApiController;
use Phpsa\LaravelApiController\ServiceProvider;
use Tests\TestCase;
use Illuminate\Http\Request;
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


	public function testQueryParsers(){

		$myRequest = $this->createRequest('GET', '/test?equal=5&greaterThan>1&lessThan<10&greaterEqual>=11&lessEqual<=20&not<>15&notAgain!=15&contains~raig&starts^craig&ends$smith&ids=1||2||3||4&notin!=1||2||3||4&new[]=1&new[]!=2&notstart!^fake&notend!$notend&notcontain!~notin');

		$parser = new UriParser($myRequest);

		$params = $parser->whereParameters();

		$this->assertEquals(17, count($params));

		$this->assertEquals($parser->queryParameter('equal'), [
			'type' => 'Basic',
		    'key' => 'equal',
		    'operator' => '=',
			'value' => '5'
		]);

		$this->assertEquals($parser->queryParameter('greaterThan'), [
			'type' => 'Basic',
		    'key' => 'greaterThan',
		    'operator' => '>',
			'value' => '1'
		]);

		$this->assertEquals($parser->queryParameter('lessThan'), [
			'type' => 'Basic',
		    'key' => 'lessThan',
		    'operator' => '<',
			'value' => '10'
		]);

		$this->assertEquals($parser->queryParameter('greaterEqual'), [
			'type' => 'Basic',
		    'key' => 'greaterEqual',
		    'operator' => '>=',
			'value' => '11'
		]);

		$this->assertEquals($parser->queryParameter('lessEqual'), [
			'type' => 'Basic',
		    'key' => 'lessEqual',
		    'operator' => '<=',
			'value' => '20'
		]);

		$this->assertEquals($parser->queryParameter('not'), [
			'type' => 'Basic',
			'key' => 'not',
			'operator' => '!=',
			'value' => '15'
		]);

		$this->assertEquals($parser->queryParameter('notAgain'), [
			'type' => 'Basic',
			'key' => 'notAgain',
			'operator' => '!=',
			'value' => '15'
		]);

		$this->assertEquals($parser->queryParameter('contains'), [
			'type' => 'Basic',
			'key' => 'contains',
			'operator' => 'like',
			'value' => '%raig%'
		]);

		$this->assertEquals($parser->queryParameter('starts'), [
			'type' => 'Basic',
			'key' => 'starts',
			'operator' => 'like',
			'value' => '%craig'
		]);

		$this->assertEquals($parser->queryParameter('ends'), [
			'type' => 'Basic',
			'key' => 'ends',
			'operator' => 'like',
			'value' => 'smith%'
		]);

		$this->assertEquals($parser->queryParameter('ids'), [
			'type' => 'In',
			'key' => 'ids',
			'values' => [
				0 => '1',
				1 => '2',
				2 => '3',
				3 => '4',
			]
		]);

		$this->assertEquals($parser->queryParameter('notin'), [
			'type' => 'NotIn',
			'key' => 'notin',
			'values' => [
				0 => '1',
				1 => '2',
				2 => '3',
				3 => '4',
			]
		]);

		$this->assertEquals($parser->queryParameter('new'), [
			[
				"type" => "In",
				"key" => "new",
				"values" =>  [
						"1"
				]
			],
			[
				"type" => "NotIn",
				"key" => "new",
				"values" => [
						"2"
				]
			]
		]);

		$this->assertEquals($parser->queryParameter('notstart'), [
			'type' => 'Basic',
		    'key' => 'notstart',
		    'operator' => 'not like',
			'value' => '%fake'
		]);

		$this->assertEquals($parser->queryParameter('notend'), [
			'type' => 'Basic',
		    'key' => 'notend',
		    'operator' => 'not like',
			'value' => 'notend%'
		]);

		$this->assertEquals($parser->queryParameter('notcontain'), [
			'type' => 'Basic',
		    'key' => 'notcontain',
		    'operator' => 'not like',
			'value' => '%notin%'
		]);



	}


	protected function createRequest(
		$method,
		$uri = '/test',
		$content = '',
		$parameters = [],
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
