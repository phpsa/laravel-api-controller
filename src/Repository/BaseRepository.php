<?php

namespace Phpsa\LaravelApiController\Repository;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Collection as BaseCollection;
use Phpsa\LaravelApiController\Exceptions\ApiException;

/**
 * Class BaseRepository.
 */
class BaseRepository
{
    /**
     * The repository model.
     *
     * @var \Illuminate\Database\Eloquent\Model
     */
    protected $model;

    /**
     * The query builder.
     *
     * @var \Illuminate\Database\Eloquent\Builder
     */
    protected $query;

    /**
     * Alias for the query limit.
     *
     * @var int
     */
    protected $take;

    /**
     * Array of related models to eager load.
     *
     * @var array
     */
    protected $with = [];

    /**
     * Array of one or more where clause parameters.
     *
     * @var array
     */
    protected $wheres = [];

    /**
     * Array of one or more where in clause parameters.
     *
     * @var array
     */
    protected $whereIns = [];

    /**
     * Array of one or more where in clause parameters.
     *
     * @var array
     */
    protected $whereNotIns = [];

    /**
     * Array of one or more ORDER BY column/value pairs.
     *
     * @var array
     */
    protected $orderBys = [];

    /**
     * Array of scope methods to call on the model.
     *
     * @var array
     */
    protected $scopes = [];

    public static function withModel(string $model)
    {
        $instance = new self();
        $instance->makeModel($model);

        return $instance;
    }

    /**
     * @throws ApiException
     */
    public function makeModel(string $model): void
    {
        $obj = resolve($model);

        if (! $obj instanceof Model) {
            throw new ApiException("Class {$model} must be an instance of ".Model::class);
        }

        $this->model = $obj;
    }

    /**
     * Get all the model records in the database.
     *
     * @param array $columns
     *
     * @return Collection
     */
    public function all(array $columns = ['*'])
    {
        $this->newQuery()->eagerLoad();

        $models = $this->query->get($columns);

        $this->unsetClauses();

        return $models;
    }

    public function allRaw(array $columns = ['*'])
    {
        $this->newQuery()->eagerLoad();
        $result = $this->query->toBase()->get($columns);
        $this->unsetClauses();

        return $result ? $this->rawToArray($result) : $result;
    }

    protected function rawToArray(BaseCollection $result): array
    {
        return $result->map(function ($res) {
            return (array) $res;
        })->toArray();
    }

    /**
     * Count the number of specified model records in the database.
     *
     * @return int
     */
    public function count(): int
    {
        return $this->model->count();
    }

    /**
     * Create a new model record in the database.
     *
     * @param array $data
     *
     * @return \Illuminate\Database\Eloquent\Model
     */
    public function create(array $data)
    {
        $this->unsetClauses();

        return $this->model->create($data);
    }

    /**
     * Create one or more new model records in the database.
     *
     * @param array $data
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function createMultiple(array $data)
    {
        $models = new Collection();

        foreach ($data as $record) {
            $models->push($this->create($record));
        }

        return $models;
    }

    /**
     * Delete one or more model records from the database.
     *
     * @return mixed
     */
    public function delete()
    {
        $this->newQuery()->setClauses()->setScopes();

        $result = $this->query->delete();

        $this->unsetClauses();

        return $result;
    }

    /**
     * Delete the specified model record from the database.
     *
     * @param $id
     *
     * @throws ModelNotFoundException
     *
     * @return bool
     */
    public function deleteById($id): bool
    {
        $this->unsetClauses();

        return $this->getById($id)->delete();
    }

    /**
     * Delete multiple records.
     *
     * @param array $ids
     *
     * @return int
     */
    public function deleteMultipleById(array $ids): int
    {
        return $this->model->destroy($ids);
    }

    /**
     * Get the first specified model record from the database.
     *
     * @param array $columns
     *
     * @return Model|static
     */
    public function first(array $columns = ['*'])
    {
        $this->newQuery()->eagerLoad()->setClauses()->setScopes();

        $model = $this->query->firstOrFail($columns);

        $this->unsetClauses();

        return $model;
    }

    /**
     * Get all the specified model records in the database.
     *
     * @param array $columns
     *
     * @return Collection
     */
    public function get(array $columns = ['*'])
    {
        $this->newQuery()->eagerLoad()->setClauses()->setScopes();

        $models = $this->query->get($columns);

        $this->unsetClauses();

        return $models;
    }

    public function dd(array $columns = ['*'])
    {
        $this->newQuery()->eagerLoad()->setClauses()->setScopes();
        $this->query->dd($columns);
    }

    public function getRaw(array $columns = ['*'])
    {
        $this->newQuery()->eagerLoad()->setClauses()->setScopes();

        $result = $this->query->toBase()->get($columns);

        $this->unsetClauses();

        return $result ? $this->rawToArray($result) : $result;
    }

    /**
     * Get the specified model record from the database.
     *
     * @param       $id
     * @param array $columns
     *
     * @throws ModelNotFoundException
     *
     * @return Model
     */
    public function getById($id, array $columns = ['*']): Model
    {
        $this->unsetClauses();

        $this->newQuery()->eagerLoad();

        return $this->query->findOrFail($id, $columns);
    }

    public function find($id, array $columns = ['*']): Model
    {
        $this->newQuery()->eagerLoad()->setClauses()->setScopes();

        $model = $this->query->findOrFail($id, $columns);

        $this->unsetClauses();

        return $model;
    }

    /**
     * @param       $item
     * @param       $column
     * @param array $columns
     *
     * @return Model|null
     */
    public function getByColumn($item, $column, array $columns = ['*'])
    {
        $this->unsetClauses();

        $this->newQuery()->eagerLoad();

        return $this->query->where($column, $item)->first($columns);
    }

    /**
     * @param int    $limit
     * @param array  $columns
     * @param string $pageName
     * @param int   $page
     *
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function paginate($limit = 25, array $columns = ['*'], $pageName = 'page', $page = null)
    {
        $this->newQuery()->eagerLoad()->setClauses()->setScopes();

        $models = $this->query->paginate($limit, $columns, $pageName, $page);

        $this->unsetClauses();

        return $models;
    }

    public function paginateRaw($limit = 25, array $columns = ['*'], $pageName = 'page', $page = null)
    {
        $this->newQuery()->eagerLoad()->setClauses()->setScopes();

        $models = $this->query->toBase()->paginate($limit, $columns, $pageName, $page);

        $this->unsetClauses();

        $collection = $this->rawToArray($models->getCollection());
        $models->setCollection(collect($collection));

        return $models;
    }

    /**
     * Update the specified model record in the database.
     *
     * @param       $id
     * @param array $data
     * @param array $options
     *
     * @return Collection|Model
     */
    public function updateById($id, array $data, array $options = [])
    {
        $this->unsetClauses();

        $model = $this->getById($id);

        $model->update($data, $options);

        return $model;
    }

    /**
     * Set the query limit.
     *
     * @param int $limit
     *
     * @return $this
     */
    public function limit($limit)
    {
        $this->take = $limit;

        return $this;
    }

    /**
     * Set an ORDER BY clause.
     *
     * @param string $column
     * @param string $direction
     *
     * @return $this
     */
    public function orderBy($column, $direction = 'asc')
    {
        $this->orderBys[] = compact('column', 'direction');

        return $this;
    }

    /**
     * Add a simple where clause to the query.
     *
     * @param string $column
     * @param string $value
     * @param string $operator
     *
     * @return $this
     */
    public function where(string $column, ?string $operator = null, ?string $value = null)
    {
        $this->wheres[] = func_get_args(); //compact('column', 'operator', 'value');

        return $this;
    }

    /**
     * Add a simple where in clause to the query.
     *
     * @param string $column
     * @param mixed  $values
     *
     * @return $this
     */
    public function whereIn($column, $values)
    {
        $values = is_array($values) ? $values : [$values];

        $this->whereIns[] = compact('column', 'values');

        return $this;
    }

    /**
     * Add a simple where not in clause to the query.
     *
     * @param string $column
     * @param mixed  $values
     *
     * @return $this
     */
    public function whereNotIn($column, $values)
    {
        $values = is_array($values) ? $values : [$values];

        $this->whereNotIns[] = compact('column', 'values');

        return $this;
    }

    /**
     * Set Eloquent relationships to eager load.
     *
     * @param $relations
     *
     * @return $this
     */
    public function with($relations)
    {
        if (is_string($relations)) {
            $relations = func_get_args();
        }

        $this->with = array_merge($this->with, $relations);

        return $this;
    }

    /**
     * Create a new instance of the model's query builder.
     *
     * @return $this
     */
    protected function newQuery()
    {
        $this->query = $this->model->newQuery();

        return $this;
    }

    /**
     * Add relationships to the query builder to eager load.
     *
     * @return $this
     */
    protected function eagerLoad()
    {
        foreach ($this->with as $key => $relation) {
            if (is_numeric($key)) {
                $this->query->with($relation);
            } else {
                $this->query->with([$key => $relation]);
            }
        }

        return $this;
    }

    /**
     * Set clauses on the query builder.
     *
     * @return $this
     */
    protected function setClauses()
    {
        foreach ($this->wheres as $where) {
            $this->query->where(...$where);
        }

        foreach ($this->whereIns as $whereIn) {
            $this->query->whereIn($whereIn['column'], $whereIn['values']);
        }

        foreach ($this->whereNotIns as $whereIn) {
            $this->query->whereNotIn($whereIn['column'], $whereIn['values']);
        }

        foreach ($this->orderBys as $orders) {
            $this->query->orderBy($orders['column'], $orders['direction']);
        }

        if (isset($this->take) and ! is_null($this->take)) {
            $this->query->take($this->take);
        }

        return $this;
    }

    /**
     * Set query scopes.
     *
     * @return $this
     */
    protected function setScopes()
    {
        foreach ($this->scopes as $scope) {
            foreach ($scope as $method => $args) {
                $this->query->$method(...$args);
            }
        }

        return $this;
    }

    /**
     * Reset the query clause parameter arrays.
     *
     * @return $this
     */
    protected function unsetClauses()
    {
        $this->wheres = [];
        $this->whereIns = [];
        $this->scopes = [];
        $this->take = null;

        return $this;
    }

    /**
     * Add the given query scope.
     *
     * @param string $scope
     * @param array $args
     *
     * @return $this
     */
    public function __call($scope, $args)
    {
        $this->scopes[][$scope] = $args;

        return $this;
    }
}
