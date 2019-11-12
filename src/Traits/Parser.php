<?php

namespace Phpsa\LaravelApiController\Traits;

use Gate;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Eloquent\Model;
use Phpsa\LaravelApiController\Exceptions\UnknownColumnException;

trait Parser
{
    /**
     * UriParser instance.
     *
     * @var \Phpsa\LaravelApiController\UriParser
     */
    protected $uriParser;

    /**
     * Holds the available table columns.
     *
     * @var array
     */
    protected $tableColumns = [];

    /**
     * Set which columns area available in the model.
     *
     * @param Model $model
     */
    protected function setTableColumns(Model $model = null) : void
    {
        if (null === $model) {
            $model = $this->model;
        }
        $table = $model->getTable();
        $this->tableColumns[$table] = Schema::getColumnListing($table);
    }

    /**
     * gets avaialble columns for the table.
     *
     * @param Model $model
     *
     * @return array
     */
    protected function getTableColumns(Model $model = null) : array
    {
        if (null === $model) {
            $model = $this->model;
        }

        $table = $model->getTable();

        if (! isset($this->tableColumns[$table])) {
            $this->setTableColumns($model);
        }

        return $this->tableColumns[$table];
    }

    /**
     * Parses our include joins.
     */
    protected function parseIncludeParams() : void
    {
        $field = config('laravel-api-controller.parameters.include');

        if (empty($field)) {
            return;
        }

        $includes = $this->request->input($field);

        if (empty($includes)) {
            return;
        }

        $withs = explode(',', $includes);

        foreach ($withs as $idx => $with) {
            $sub = $this->model->{$with}()->getRelated();
            $fields = $this->getIncludesFields($with);

            if (! empty($fields)) {
                $fields[] = $sub->getKeyName();
                $withs[$idx] = $with . ':' . implode(',', array_unique($fields));
            }
        }

        $this->repository->with($withs);
    }

    /**
     * Parses our sort parameters.
     */
    protected function parseSortParams() : void
    {
        $sorts = $this->getSortValue();

        foreach ($sorts as $sort) {
            $sortP = explode(' ', $sort);
            $sortF = $sortP[0];

            if (empty($sortF) || ! in_array($sortF, $this->getTableColumns())) {
                continue;
            }

            $sortD = ! empty($sortP[1]) && strtolower($sortP[1]) == 'desc' ? 'desc' : 'asc';
            $this->repository->orderBy($sortF, $sortD);
        }
    }

    /**
     * gets the sort value.
     *
     * @returns array
     */
    protected function getSortValue() : array
    {
        $field = config('laravel-api-controller.parameters.sort');
        $sort = $field && $this->request->has($field) ? $this->request->input($field) : $this->defaultSort;

        if (! $sort) {
            return [];
        }

        return is_array($sort) ? $sort : explode(',', $sort);
    }

    /**
     * parses our filter parameters.
     */
    protected function parseFilterParams() : void
    {
        $where = $this->uriParser->whereParameters();

        if (empty($where)) {
            return;
        }

        foreach ($where as $whr) {
            if (strpos($whr['key'], '.') > 0) {
                //@TODO: test if exists in the withs, if not continue out to exclude from the qbuild
                //continue;
            } elseif (! in_array($whr['key'], $this->getTableColumns())) {
                continue;
            }

            $this->setWhereClause($whr);
        }
    }

    /**
     * set the Where clause.
     *
     * @param array $where the where clause
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
     * parses the fields to return.
     *
     * @throws UnknownColumnException
     * @return array
     */
    protected function parseFieldParams() : array
    {
        $fields = $this->request->has('fields') && ! empty($this->request->input('fields')) ? explode(',', $this->request->input('fields')) : $this->defaultFields;
        foreach ($fields as $k => $field) {
            if (
                $field === '*' ||
                in_array($field, $this->getTableColumns())
            ) {
                continue;
            }
            unset($fields[$k]);
        }

        return $fields;
    }

    /**
     * Parses an includes fields and returns as an array.
     * @param string $include - the table definer
     *
     * @return array
     */
    protected function getIncludesFields(string $include) : array
    {
        $fields = $this->request->has('fields') && ! empty($this->request->input('fields')) ? explode(',', $this->request->input('fields')) : $this->defaultFields;
        foreach ($fields as $k => $field) {
            if (strpos($field, $include . '.') === false) {
                unset($fields[$k]);

                continue;
            }
            $fields[$k] = str_replace($include . '.', '', $field);
        }

        return $fields;
    }

    /**
     * parses the limit value.
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

    /**
     * Qualifies the collection query to allow you to add params vai the policy
     * ie to limit to a specific user id mapping.
     */
    protected function qualifyCollectionQuery(): void
    {
        $user = auth()->user();
        $modelPolicy = Gate::getPolicyFor($this->model);

        if ($modelPolicy && method_exists($modelPolicy, 'qualifyCollectionQueryWithUser')) {
            $modelPolicy->qualifyCollectionQueryWithUser($user, $this->repository);
        }
    }

    /**
     * Qualifies the collection query to allow you to add params vai the policy
     * ie to limit to a specific user id mapping
     * This may be overkill but could be usedfull ?
     */
    protected function qualifyItemQuery(): void
    {
        $user = auth()->user();
        $modelPolicy = Gate::getPolicyFor($this->model);

        if ($modelPolicy && method_exists($modelPolicy, 'qualifyItemQueryWithUser')) {
            $modelPolicy->qualifyItemQueryWithUser($user, $this->repository);
        }
    }

    /**
     * Allows you to massage the data when creating a new record.
     *
     * @param array $data
     *
     * @return array
     */
    protected function qualifyStoreQuery(array $data): array
    {
        $user = auth()->user();
        $modelPolicy = Gate::getPolicyFor($this->model);

        if ($modelPolicy && method_exists($modelPolicy, 'qualifyStoreDataWithUser')) {
            $modelPolicy->qualifyStoreDataWithUser($user, $data);
        }

        return $data;
    }

    /**
     * Allows you to massage the data when updating an existing record.
     *
     * @param array $data
     *
     * @return array
     */
    protected function qualifyUpdateQuery(array $data): array
    {
        $user = auth()->user();
        $modelPolicy = Gate::getPolicyFor($this->model);

        if ($modelPolicy && method_exists($modelPolicy, 'qualifyUpdateDataWithUser')) {
            $modelPolicy->qualifyUpdateDataWithUser($user, $data);
        }

        return $data;
	}


	protected function authoriseUserAction($ability, $arguments = [])
	{

		if (is_null($ability)) {
            return true;
		}

		if (! $this->testUserPolicyAction($ability, $arguments)) {
            abort(401, 'Unauthorised');
        }
	}

	protected function testUserPolicyAction($ability, $arguments)
	{
		$user = auth()->user();
        // If no arguments are specified, set it to the controller's model (default)
        if (empty($arguments)) {
            $arguments = $this->model;
		}

		// Get policy for model
        if (is_array($arguments)) {
            $model = reset($arguments);
        } else {
            $model = $arguments;
		}

		$modelPolicy = Gate::getPolicyFor($model);
        // If no policy exists for this model, then there's nothing to check
        if (is_null($modelPolicy)) {
            return true;
		}

        // Check if the authenticated user has the required ability for the model
        if ($user->can($ability, $arguments)) {
            return true;
        }
        return false;
	}
}
