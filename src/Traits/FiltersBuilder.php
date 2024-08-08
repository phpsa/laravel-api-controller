<?php

namespace Phpsa\LaravelApiController\Traits;

use Illuminate\Support\Collection;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Phpsa\LaravelApiController\Helpers;

trait FiltersBuilder
{

    protected function parseFiltersArray(array $filters = []): Collection
    {
        return collect($filters)
            ->mapWithKeys(fn($value, $key) => is_array($value) ? [$key => $value] : [
               $key => [ 'equals' => $value]
            ]
        );


    }

    protected function buildQuery(string $column, string $comparison, mixed $value, Builder $builder ): void
    {
       $model = $builder->getModel();

        if(str($column)->contains(".") === true){
            [$relation, $key] = str($column)->explode(".");
            if ($model->isRelation($relation)) {


                $value = match($comparison) {
                    'is','equals','=' => [
                            $key => $value
                        ],
                    '!equals','not_equals','!is','not_is','!','!=','<>' => [
                        $key  => $value

                        ],
                    default => [
                        $key => [
                            $comparison => $value
                            ]
                        ]
                };

                $comparison = match($comparison){
                    'is','equals','=' => 'has',
                    '!equals','not_equals','!is','not_is','!','!=','<>' => 'not_has',
                    default => $comparison
                };

                $column = $relation;
            }
        }

        if ($model->isRelation($column)) {
            if ($comparison !== 'has' && $comparison !== 'not_has') {
                $comparison = is_array($value) || filter_var($value, FILTER_VALIDATE_BOOL) === true ? 'has' : 'not_has';
            }
        }

        match($comparison){
            'ends_with', '$' => $builder->where($column, 'like',  "%{$value}"),
            '!ends_with','not_ends_with', '!$' => $builder->where($column, 'not like',  "%{$value}"),

            'starts_with', '^' => $builder->where($column, 'like',  "{$value}%"),
            '!starts_with','not_starts_with', '!^' => $builder->where($column, 'not like',  "{$value}%"),

            'less','less_than','lt', '<' => $builder->where($column, '<', $value),
            'less_than_or_equal_to','lte','less_or_equal', '<=' => $builder->where($column, '<=', $value),

            'greater','greater_than', 'gt', '>' => $builder->where($column, '>', $value),
            'greater_than_or_equal_to','gte','greater_or_equal', '>=' => $builder->where($column, '>=', $value),

            'contains','~' => $builder->where($column, 'like',  "%{$value}%"),
            '!contains','not_contains','!~' => $builder->where($column, 'not like',  "%{$value}%"),

            'is','equals','=' => $builder->where($column, str($value)->upper()->exactly('NULL') ? null : $value),
            '!equals','not_equals','!is','not_is','!','!=','<>' => $builder->where($column, '!=', str($value)->upper()->exactly('NULL') ? null : $value),

            'in' => $builder->whereIn($column, is_array($value) ? $value : str($value)->replace(["||","|"], ",")->explode(",")->filter()),
            'not_in',"!in" => $builder->whereNotIn($column, is_array($value) ? $value : str($value)->replace(["||","|"], ",")->explode(",")->filter()),

            'has' => $this->filtersHasClause($column, 'whereHas', $value, $builder),
            'not_has', '!has' =>  $this->filtersHasClause($column, 'whereDoesntHave', $value, $builder),

            default => throw new \RuntimeException("Unknown comparison operator {$comparison}"),
        };
    }

    protected function filtersHasClause(string $relation, string $method, mixed $value, mixed $builder): void
    {
        $rel = Helpers::camel($relation);
        $relatedModel = $builder->getModel()->$rel()->getModel();
        $callback = is_array($value);

        $builder->{$method}(
            $rel,
            $callback
                ? fn($q) =>  $this->parseFiltersArray($value)
                    ->each(
                        fn($filter, $column) => collect($filter)->each(fn($subvalue, $comparison) => $this->buildQuery($relatedModel->qualifyColumn($column), $comparison, $subvalue, $q))
                    )
                : null
        );

    }
}
