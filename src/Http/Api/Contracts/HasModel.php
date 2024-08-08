<?php

namespace Phpsa\LaravelApiController\Http\Api\Contracts;

use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Cache;
use Illuminate\Database\Eloquent\Model;
use Phpsa\LaravelApiController\Helpers;
use Phpsa\LaravelApiController\Exceptions\ApiException;
use Phpsa\LaravelApiController\Http\Api\Contracts\HasQueryBuilder;
use RuntimeException;
use Throwable;

trait HasModel
{

    use HasQueryBuilder;

    /**
     * @var string|class-string
     */
    protected string $resourceModel;

    /**
     * Eloquent model instance.
     *
     * @var Model instance
     */
    protected Model $model;

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
     * @return class-string (model classname)
     */
    protected function model()
    {
        throw_if(! property_exists($this, 'resourceModel') || empty($this->resourceModel), RuntimeException::class, 'Api Controller requires the model to be listed on the resourceModel property of your Controller');
        return $this->resourceModel;
    }

    /**
     * @throws ApiException
     * @deprecated 4.x
     */
    protected function makeModel(): void
    {
        $this->getBuilder();
    }

    protected function getModel(): Model
    {
        return $this->model ??= resolve($this->model());
    }

    /**
     * Undocumented function
     *
     * @return class-string
     */
    public function getPostmanModel(): string
    {
        return $this->model();
    }

    /**
     * Unguard eloquent model if needed.
     */
    protected function unguardIfNeeded()
    {
        if ($this->unguard) {
            $this->getModel()::unguard();
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
            $model = $this->getModel();
        }

        $columns = $this->getTableColumns($model);
        $diff = array_diff(array_keys($data), $columns);

        foreach ($diff as $key) {
            if ($model->hasSetMutator($key) || $model->hasAttributeSetMutator($key)) {
                $columns[] = $key;
            }
        }

        return array_intersect_key($data, array_flip(array_unique($columns)));
    }

    /**
     * Gets the table name without database identifier.
     *
     * @param Model $model
     */
    protected function getUnqualifiedTableName(?Model $model = null): string
    {
        if (is_null($model)) {
            $model = $this->getModel();
        }
        $table = explode('.', $model->getTable());

        return end($table);
    }

    /**
     * Set which columns area available in the model.
     *
     * @param Model $model
     */
    protected function setTableColumns(?Model $model = null): void
    {
        if (is_null($model)) {
            $model = $this->getModel();
        }
        $table = $this->getUnqualifiedTableName($model);

        if (config('laravel-api-controller.cache_table_columns')) {
            $columns = Cache::remember(config('laravel-api-controller.cache_table_columns_prefix').$table, config('laravel-api-controller.cache_table_columns_ttl'), function() use ($model, $table) { return Schema::connection($model->getConnectionName())->getColumnListing($table);});
        } else {
            $columns = Schema::connection($model->getConnectionName())->getColumnListing($table);
        }
        $this->tableColumns[$table] = $columns;
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
            $model = $this->getModel();
        }

        $table = $this->getUnqualifiedTableName($model);

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
     * @return Model
     */
    protected function getRelatedModel(string $name): Model
    {
        $with = Helpers::camel($name);

        return $this->getModel()->{$with}()->getRelated();
    }
}
