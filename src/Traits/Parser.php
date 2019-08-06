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

		$sorts = $this->getSortValue();

		foreach ($sorts as $sort) {

			$sortP = explode(' ', $sort);
			$sortF = $sortP[0];

			if (empty($sortF) || ! in_array($sortF, $this->tableColumns)) {
				continue;
			}

			$sortD = ! empty($sortP[1]) && strtolower($sortP[1]) == 'desc' ? 'desc' : 'asc';
			$this->repository->orderBy($sortF, $sortD);
		}
	}

	/**
	 * gets the sort value
	 *
	 * @returns array
	 */
	protected function getSortValue() : array
	{

		$field = config('laravel-api-controller.parameters.sort');
        $sort = $field && $this->request->has($field) ? $this->request->input($field) : $this->defaultSort;

		if(!$sort){
			return [];
		}

		return is_array($sort) ? $sort : explode(',', $sort);
	}

	/**
	 * parses our filter parameters
	 *
	 * @return void
	 */
	protected function parseFilterParams() : void
    {
		$where = $this->uriParser->whereParameters();
		if(empty($where)){
			return;
		}

		foreach ($where as $whr) {
			if (strpos($whr['key'], '.') > 0) {
				//@TODO: test if exists in the withs, if not continue out to exclude from the qbuild
				//continue;
			} elseif (! in_array($whr['key'], $this->tableColumns)) {
				continue;
			}

			$this->setWhereClause($whr);

		}

	}

	/**
	 * set the Where clause
	 *
	 * @param array $where the where clause
	 *
	 * @return void
	 */
	protected function setWhereClause($where) : void
	{
		switch ($where['type']) {
			case 'In':
				if (! empty($where['values'])) {
					$this->repository->whereIn($where['key'], $where['values']);
				}
				break;
			case 'NotIn':
				if (! empty($where['values'])) {
					$this->repository->whereNotIn($where['key'], $where['values']);
				}
				break;
			case 'Basic':
				$this->repository->where($where['key'], $where['value'], $where['operator']);
				break;
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
			if (
				$field === '*'  ||
				in_array($field, $this->tableColumns)  ||
				array_key_exists($field, $attributes)
			) {
                continue;
            }
            if (strpos($field, '.') > 0) {
                //@TODO check if mapped field exists
                //@todo
                unset($fields[$k]);
                continue;
			}

			unset($fields[$k]);

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