<?php

namespace Phpsa\LaravelApiController;

use Illuminate\Support\Arr;
use Illuminate\Support\Str;
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
            $this->appendQueryParameter($key . $operator . $value);
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

    /**
     * filter out the comparison operator!
     *
     * @param mixed $parameter
     *
     * @return string
     */
    protected function getParameterOperator($parameter): ?string
    {
        preg_match(self::PATTERN, $parameter, $matches);
        return isset($matches[0]) ? $matches[0] : null;
    }

    /**
     * replaces starting and ending chars of string for the like query
     *
     * @param string $value
     *
     * @return string
     */
    protected function replaceForLikeValue(string $value): string
    {
        if($value[0] === '*'){
            $value[0] = '%';
        }
        if($value[-1] === '*'){
            $value[-1] = '%';
        }

        return $value;
    }

    /**
     * Undocumented function
     *
     * @param string $parameter
     *
     * @return void
     */
    private function appendQueryParameterAsBasicWhere(string $parameter): void
    {
        $operator = $this->getParameterOperator($parameter);

        if(is_null($operator)){
            return;
        }

        [$key, $value] = explode($operator, $parameter);

        //check if we are comparing an array of in / not in!
        if(Str::contains($parameter, '||'))
        {
            $this->setInQueryParameters($key, $value, $parameter);
            return;
        }

        // Is this a like query?
        if ($this->isLikeQuery($value)) {
            $operator = 'like';
            $value = $this->replaceForLikeValue($value);
        }

        if (in_array($operator, ['$', '^', '~'])) {
            $operator = 'like';
            $value = $this->parseLikeValueSurrounders($value, $operator);
        }

        if (in_array($operator, ['!$', '!^', '!~'])) {
            $operator = 'not like';
            $value = $this->parseLikeValueSurrounders($value, $operator);
        }

        $this->queryParameters[] = [
            'type' => 'Basic',
            'key' => $key,
            'operator' => $operator === '<>' ? '!=' : $operator,
            'value' => $value,
        ];
    }

    /**
     * PArses and sets the surrounding signs for like query parsed
     *
     * @param string $value
     * @param string $operator
     *
     * @return string
     */
    protected function parseLikeValueSurrounders(string $value, string $operator):string
    {
        $pre =   Str::contains($operator, ['$', '^', '~'])? '%' : '';
        $post =  Str::contains($operator, ['^', '~']) ? '%' : '';
        return $pre . $value . $post;
    }

    /**
     * appends as a where in parameter
     *
     * @param string $parameter
     * @param string $key
     *
     * @return void
     */
    private function appendQueryParameterAsWhereIn(string $parameter,string $key): void
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

    /**
     * Sets an in question where statement
     *
     * @param string $key
     * @param string $value
     * @param string $parameter
     *
     * @return void
     */
    protected function setInQueryParameters(string $key, string $value, string $parameter): void
    {
        $values = explode('||', $value);

        $type = Str::contains($parameter, ['!=', '<>']) ? 'NotIn' : 'In';

        $this->queryParameters[] = [
            'type' => $type,
            'key' => $key,
            'values' => $values,
        ];
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
