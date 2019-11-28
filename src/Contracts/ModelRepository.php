<?php

namespace Phpsa\LaravelApiController\Contracts;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;
use Phpsa\LaravelApiController\Exceptions\ApiException;
use Phpsa\LaravelApiController\Repository\BaseRepository;

trait ModelRepository
{
    /**
     * Eloquent model instance.
     *
     * @var mixed|Model instance
     */
    protected static $model;

    /**
     * Repository instance.
     *
     * @var mixed|BaseRepository
     */
    protected $repository;

    /**
     * Holds the available table columns.
     *
     * @var array
     */
    protected $tableColumns = [];

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
     * Creates our repository linkage.
     */
    protected function makeRepository()
    {
        $this->repository = BaseRepository::withModel($this->model());
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
     * Eloquent model.
     *
     * @return string (model classname)
     */
    abstract protected function model();

    /**
     * Unguard eloquent model if needed.
     */
    protected function unguardIfNeeded()
    {
        if ($this->unguard) {
            self::$model->unguard();
        }
    }
}
