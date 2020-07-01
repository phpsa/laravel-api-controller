<?php

namespace Phpsa\LaravelApiController\Http\Api\Contracts;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;
use Phpsa\LaravelApiController\Exceptions\ApiException;
use Phpsa\LaravelApiController\Helpers;

trait HasModel
{
    /**
     * Eloquent model instance.
     *
     * @var mixed|Model instance
     */
    protected static $model;

    /**
     * Do we need to unguard the model before create/update?
     *
     * @var bool
     */
    protected $unguard = false;

    /**
     * Holds the available table columns.
     *
     * @var array
     */
    protected $tableColumns = [];

    /**
     * Eloquent model.
     *
     * @return string (model classname)
     */
    abstract protected function model();

    /**
     * @throws ApiException
     */
    protected function makeModel(): void
    {
        $model = resolve($this->model());

        if (! $model instanceof Model) {
            throw new ApiException("Class {$this->model()} must be an instance of ".Model::class);
        }

        self::$model = $model;
    }

    /**
     * Unguard eloquent model if needed.
     */
    protected function unguardIfNeeded()
    {
        if ($this->unguard) {
            self::$model->unguard();
        }
    }

    /**
     * Checks if attribute has a mutator.
     *
     * @param array                                    $data
     * @param \Illuminate\Database\Eloquent\Model|null $model
     *
     * @return array
     */
    protected function addTableData(array $data = [], ?Model $model = null): array
    {
        if (is_null($model)) {
            $model = self::$model;
        }

        $columns = $this->getTableColumns($model);
        $diff = array_diff(array_keys($data), $columns);

        foreach ($diff as $key) {
            if ($model->hasSetMutator($key)) {
                $columns[] = $key;
            }
        }

        return array_intersect_key($data, array_flip(array_unique($columns)));
    }

    /**
     * Set which columns area available in the model.
     *
     * @param Model $model
     */
    protected function setTableColumns(?Model $model = null): void
    {
        if (is_null($model)) {
            $model = self::$model;
        }
        $table = $model->getTable();
        $this->tableColumns[$table] = Schema::connection($model->getConnectionName())->getColumnListing($table);
    }

    /**
     * gets avaialble columns for the table.
     *
     * @param Model $model
     *
     * @return array
     */
    protected function getTableColumns(?Model $model = null): array
    {
        if (is_null($model)) {
            $model = self::$model;
        }

        $table = $model->getTable();

        if (! isset($this->tableColumns[$table])) {
            $this->setTableColumns($model);
        }

        return $this->tableColumns[$table];
    }

    /**
     * Gets related model.
     *
     * @param string $name
     *
     * @return mixed
     */
    protected function getRelatedModel(string $name): Model
    {
        $with = Helpers::camel($name);

        return self::$model->{$with}()->getRelated();
    }
}
