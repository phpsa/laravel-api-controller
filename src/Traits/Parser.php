<?php

namespace Phpsa\LaravelApiController\Traits;

use Phpsa\LaravelApiController\Exceptions\UnknownColumnException;


Trait Parser {

	/**
     * Default Fields to response with.
     *
     * @var array
     */
    protected $defaultFields = ['*'];

    /**
     * Set the default sorting for queries.
     *
     * @var string
     */
    protected $defaultSort = null;

    /**
     * Number of items displayed at once if not specified.
     * There is no limit if it is 0 or false.
     *
     * @var int
     */
    protected $defaultLimit = 25;

    /**
     * Maximum limit that can be set via $_GET['limit'].
     *
     * @var int
     */
    protected $maximumLimit = 0;

	/**
	 * Parses our include joins
	 *
	 * @return void
	 */
	protected function parseIncludeParams() : void
    {
        $field = config('laravel-api-controller.parameters.include');

		if (empty($field)) {
            return;
		}

		$with = $this->request->input($field);

        if ($with !== null) {
            $this->repository->with(explode(',', $with));
        }
	}

	/**
	 * Parses our sort parameters
	 *
	 * @return void
	 */
	protected function parseSortParams() : void
    {
        $field = config('laravel-api-controller.parameters.sort');
        $sort = $field && $this->request->has($field) ? $this->request->input($field) : $this->defaultSort;

        if ($sort) {
            $sorts = is_array($sort) ? $sort : explode(',', $sort);
            if (empty($sorts)) {
                return;
            }

            foreach ($sorts as $sort) {
                if (empty($sort)) {
                    continue;
                }
                $sortP = explode(' ', $sort);

                $sortF = $sortP[0];

                if (! in_array($sortF, $this->tableColumns)) {
                    continue;
                }

                $sortD = ! empty($sortP[1]) && strtolower($sortP[1]) == 'asc' ? 'asc' : 'desc';
                $this->repository->orderBy($sortF, $sortD);
            }
        }
	}

	/**
	 * parses our filter parameters
	 *
	 * @return void
	 */
	protected function parseFilterParams() : void
    {
        $where = $this->uriParser->whereParameters();
        if (! empty($where)) {
            foreach ($where as $whr) {
                if (strpos($whr['key'], '.') > 0) {
                    //test if exists in the withs, if not continue out to exclude from the qbuild
                    //continue;
                } else {
                    if (! in_array($whr['key'], $this->tableColumns)) {
                        continue;
                    }
                }
                switch ($whr['type']) {
                    case 'In':
                        if (! empty($whr['values'])) {
                            $this->repository->whereIn($whr['key'], $whr['values']);
                        }
                        break;
                    case 'NotIn':
                        if (! empty($whr['values'])) {
                            $this->repository->whereNotIn($whr['key'], $whr['values']);
                        }
                        break;
                    case 'Basic':
                        $this->repository->where($whr['key'], $whr['value'], $whr['operator']);

                        break;
                }
            }
        }
	}

	/**
	 * parses the fields to return
	 *
	 * @throws UnknownColumnException
	 * @return array
	 */
	protected function parseFieldParams() : array
    {
        $attributes = $this->model->attributesToArray();
        $fields = $this->request->has('fields') && ! empty($this->request->input('fields')) ? explode(',', $this->request->input('fields')) : $this->defaultFields;
        foreach ($fields as $k => $field) {
            if ($field === '*') {
                continue;
            }
            if (strpos($field, '.') > 0) {
                //check if mapped field exists
                //@todo
                unset($fields[$k]);
                continue;
            }
            if (! in_array($field, $this->tableColumns)) {
                //does the attribute exist ?

                if (! array_key_exists($field, $attributes)) {
                    throw new UnknownColumnException($field.' does not exist in table');
                }
                unset($fields[$k]);
            }
        }

        return $fields;
	}

	/**
	 * parses the limit value
	 *
	 * @return int
	 */
	protected function parseLimitParams() : int
	{

		$limit = $this->request->has('limit') ? intval($this->request->input('limit')) : $this->defaultLimit;

		if ($this->maximumLimit && ($limit > $this->maximumLimit || ! $limit)) {
            $limit = $this->maximumLimit;
        }

		return $limit;
	}

}