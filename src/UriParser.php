<?php

namespace Phpsa\LaravelApiController;

use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

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
     * '$' => Ends with.
     */
    protected const PATTERN = '/!=|=|!~|~|!\^|\^|!\$|\$|<>|<=|<|>=|>/';

    /**
     * Patttern to match an array within the url structure.
     */
    protected const ARRAY_QUERY_PATTERN = '/(.*)\[\]/';

    /**
     * Undocumented variable.
     *
     * @var Request
     */
    protected $request;

    protected $queryUri;

    protected $queryParameters = [];

    /**
     * Constructor.
     *
     * @param \Illuminate\Http\Request $request
     * @param string $filter - which key to filer on
     */
    public function __construct(Request $request, string $filter)
    {
        $this->request = $request;

        $this->queryUri = $request->query($filter);

        if ($this->hasQueryUri()) {
            $this->setQueryParameters($this->queryUri);
        }
    }

    /**
     * gets our pattern.
     *
     * @return string
     */
    public static function getPattern(): string
    {
        return self::PATTERN;
    }

    /**
     * Gets our Array pattern.
     *
     * @return string
     */
    public static function getArrayQueryPattern(): string
    {
        return self::ARRAY_QUERY_PATTERN;
    }

    /**
     * Undocumented function.
     *
     * @param string $key - key to grab from the filter params
     *
     * @return mixed
     */
    public function queryParameter($key)
    {
        $keys = Arr::pluck($this->queryParameters, 'key');
        $counts = array_count_values($keys);

        if (empty($counts[$key])) {
            return;
        }

        if ($counts[$key] === 1) {
            $idx = array_search($key, $keys);

            return $this->queryParameters[$idx];
        }

        $return = [];
        foreach (array_keys($keys, $key) as $param) {
            $return[] = $this->queryParameters[$param];
        }

        return $return;
    }

    /**
     * returns the list of wheres from the query.
     *
     * @return array
     */
    public function whereParameters(): array
    {
        return $this->queryParameters;
    }

    private function setQueryParameters($queryUri): self
    {
        foreach ($queryUri as $key => $value) {
            preg_match(self::PATTERN, urldecode($key), $matches);
            $operator = empty($matches[0]) ? '=' : '';
            $this->appendQueryParameter($key.$operator.$value);
        }

        return $this;
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

        if (! isset($matches[0])) {
            return;
        }
        $operator = $matches[0];
        [$key, $value] = explode($operator, $parameter);

        $isAnIn = strpos($value, '||');

        if ($isAnIn) {
            $values = explode('||', $value);

            if (Str::contains($parameter, '!=') || Str::contains($parameter, '<>')) {
                $type = 'NotIn';
            } else {
                $type = 'In';
            }
            $this->queryParameters[] = [
                'type' => $type,
                'key' => $key,
                'values' => $values,
            ];

            return;
        }

        if ($this->isLikeQuery($value)) {
            $operator = 'like';
            $value = str_replace('*', '%', $value);
        }

        if ($operator === '<>') {
            $operator = '!=';
        }

        if (in_array($operator, ['$', '^', '~'])) {
            $pre = in_array($operator, ['$', '~']) ? '%' : '';
            $post = in_array($operator, ['^', '~']) ? '%' : '';
            $operator = 'like';
            $value = $pre.$value.$post;
        }

        if (in_array($operator, ['!$', '!^', '!~'])) {
            $pre = in_array($operator, ['!$', '!~']) ? '%' : '';
            $post = in_array($operator, ['!^', '!~']) ? '%' : '';
            $operator = 'not like';
            $value = $pre.$value.$post;
        }
        $this->queryParameters[] = [
            'type' => 'Basic',
            'key' => $key,
            'operator' => $operator,
            'value' => $value,
        ];
    }

    private function appendQueryParameterAsWhereIn($parameter, $key)
    {
        if (Str::contains($parameter, '!=')) {
            $type = 'NotIn';
            $seperator = '!=';
        } else {
            $type = 'In';
            $seperator = '=';
        }
        $index = null;
        foreach ($this->queryParameters as $_index => $queryParameter) {
            if ($queryParameter['type'] === $type && $queryParameter['key'] === $key) {
                $index = $_index;
                break;
            }
        }

        if ($index !== null) {
            $this->queryParameters[$index]['values'][] = explode($seperator, $parameter)[1];
        } else {
            $this->queryParameters[] = [
                'type' => $type,
                'key' => $key,
                'values' => [explode($seperator, $parameter)[1]],
            ];
        }
    }

    public function hasQueryUri(): bool
    {
        return ! empty($this->queryUri);
    }

    public function getQueryUri()
    {
        return $this->queryUri;
    }

    public function hasQueryParameters(): bool
    {
        return count($this->queryParameters) > 0;
    }

    public function hasQueryParameter($key): bool
    {
        $keys = Arr::pluck($this->queryParameters, 'key');

        return in_array($key, $keys);
    }

    private function isLikeQuery($query): bool
    {
        $pattern = "/^\*|\*$/";

        return preg_match($pattern, $query, $matches) ? true : false;
    }
}
