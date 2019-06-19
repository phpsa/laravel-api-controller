<?php
namespace Phpsa\LaravelApiController;

use Illuminate\Http\Request;

class UriParser
{

	/**
	 * Pattern match
	* '=' =>  Equals
	* '>' =>  Greater than
	* '<' =>  Less than
	* '>=' =>  Greater or equal
	* '<=' =>  Less or equal
	* '<>' =>  Where not
	* '!=' =>  Where not
	* '~' =>  Contains (LIKE with wildcard on both sides)
	* '^' =>  Begins with
	* '$' => Ends with
	 */
    const PATTERN = '/!=|=|~|\^|\$|<>|<=|<|>=|>/';

    const ARRAY_QUERY_PATTERN = '/(.*)\[\]/';

    protected $request;

    protected $constantParameters = [
        'order_by',
        'group_by',
        'limit',
        'page',
        'fields',
        'with',
    ];

    protected $uri;

    protected $queryUri;

    protected $queryParameters = [];

    public function __construct(Request $request)
    {
        $this->request = $request;
        $this->uri     = $request->getRequestUri();
        $this->setQueryUri($this->uri);
        if ($this->hasQueryUri()) {
            $this->setQueryParameters($this->queryUri);
        }
    }

    public static function getPattern()
    {
        return self::PATTERN;
    }

    public static function getArrayQueryPattern()
    {
        return self::ARRAY_QUERY_PATTERN;
    }

    public function queryParameter($key)
    {
		$keys            = array_pluck($this->queryParameters, 'key');
		$counts = array_count_values($keys);
		if($counts[$key] === 1){
			$idx = array_search($key, $keys);
			return $this->queryParameters[$idx];
		}

		$return = [];
		foreach(array_keys($keys, $key) as $k){
			$return[] = $this->queryParameters[$k];
		}
        return $return;
    }

    public function constantParameters()
    {
        return $this->constantParameters;
    }

    public function whereParameters()
    {
        return array_filter(
            $this->queryParameters,
            function ($queryParameter) {
                $key = $queryParameter['key'];
                return (!in_array($key, $this->constantParameters));
            }
        );
    }

    private function setQueryUri($uri)
    {
        $explode        = explode('?', $uri);
        $this->queryUri = (isset($explode[1])) ? urldecode($explode[1]) : null;
    }

    private function setQueryParameters($queryUri)
    {
        $queryParameters = array_filter(explode('&', $queryUri));
        array_map([$this, 'appendQueryParameter'], $queryParameters);
    }

    private function appendQueryParameter($parameter)
    {
        // whereIn expression
        preg_match(self::ARRAY_QUERY_PATTERN, $parameter, $arrayMatches);
        if (count($arrayMatches) > 0) {
            $this->appendQueryParameterAsWhereIn($parameter, $arrayMatches[1]);
            return;
        }
        // basic where expression
        $this->appendQueryParameterAsBasicWhere($parameter);
    }

    private function appendQueryParameterAsBasicWhere($parameter)
    {
		preg_match(self::PATTERN, $parameter, $matches);
		if(!isset($matches[0])){
			return;
		}
		$operator          = $matches[0];
        list($key, $value) = explode($operator, $parameter);

        $in = strpos($value, "||");
        if ($in) {
            $values = explode("||", $value);
            if (str_contains($parameter, '!=') || str_contains($parameter, '<>')) {
                $type      = 'NotIn';
                $seperator = '!=';
            } else {
                $type      = 'In';
                $seperator = '=';
            }
            $this->queryParameters[] = [
                'type'   => $type,
                'key'    => $key,
                'values' => $values,
            ];
            return;
        }

        if (!$this->isConstantParameter($key) && $this->isLikeQuery($value)) {
            $operator = 'like';
            $value    = str_replace('*', '%', $value);
		}
		if($operator == '<>'){
			$operator = '!=';
		}
		if(in_array($operator, ['$','^','~'])){
			$pre  = in_array($operator, ['^','~']) ? '%' : '';
			$post = in_array($operator, ['$','~']) ? '%' : '';
			$operator = 'like';
            $value    = $pre . $value . $post;
		}
        $this->queryParameters[] = [
            'type'     => 'Basic',
            'key'      => $key,
            'operator' => $operator,
            'value'    => $value,
        ];
	}

    private function appendQueryParameterAsWhereIn($parameter, $key)
    {
        if (str_contains($parameter, '!=')) {
            $type      = 'NotIn';
            $seperator = '!=';
        } else {
            $type      = 'In';
            $seperator = '=';
        }
        $index = null;
        foreach ($this->queryParameters as $_index => $queryParameter) {
            if ($queryParameter['type'] == $type && $queryParameter['key'] == $key) {
                $index = $_index;
                break;
            }
        }
        if ($index !== null) {
            $this->queryParameters[$index]['values'][] = explode($seperator, $parameter)[1];
        } else {
            $this->queryParameters[] = [
                'type'   => $type,
                'key'    => $key,
                'values' => [explode($seperator, $parameter)[1]],
            ];
        }
    }
    public function hasQueryUri()
    {
        return ($this->queryUri);
    }
    public function getQueryUri()
    {
        return $this->queryUri;
    }
    public function hasQueryParameters()
    {
        return (count($this->queryParameters) > 0);
    }
    public function hasQueryParameter($key)
    {
        $keys = array_pluck($this->queryParameters, 'key');
        return (in_array($key, $keys));
    }
    private function isLikeQuery($query)
    {
        $pattern = "/^\*|\*$/";
        return (preg_match($pattern, $query, $matches));
    }
    private function isConstantParameter($key)
    {
        return (in_array($key, $this->constantParameters));
    }
}
