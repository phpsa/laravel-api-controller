<?php

namespace Phpsa\LaravelApiController\Http\Resources\Contracts;

use Illuminate\Support\Str;
use Illuminate\Support\Facades\Gate;
use Phpsa\LaravelApiController\Helpers;
use Illuminate\Contracts\Auth\Authenticatable;

trait AllowableFields
{
    /**
     * Resources to be mapped (ie children).
     *
     * @var array|null
     */
    protected static $mapResources = null;

    /**
     * Default fields to return on request.
     *
     * @var array|null
     */
    protected static $defaultFields = null;

    /**
     * Allowable fields to be used.
     *
     * @var array|null
     */
    protected static $allowedFields = null;

    /**
     * Allowable scopes to be used.
     *
     * @var array|null
     */
    protected static $allowedScopes = null;

    /**
     * There are times where we need to select specific fields that are required
     * but should not be in the response, ie relationship id or calculated
     * attribute dependencies for display under a different name
     *
     * @var array
     */
    protected static array $alwaysSelectFields = [];

    /**
     * which gates to apply to the list of fields available
     *
     * @var array|null
     */
    protected static ?array $fieldGates = null;

    /**
     * Guard used to retrieve the user from the request.
     * null defaults to default guard config (auth.defaults.guard)
     *
     * @var ?string
     */
    protected ?string $guard = null;

    /**
     * Makes sure we only return allowable fields.
     *
     * @param mixed $request
     *
     * @return array
     */
    protected function onlyAllowed($request): array
    {
        $fields =  Helpers::camelCaseArray(
            $this->filterUserViewableFields($request)
        );

        $data = $this->mapFieldData($request, $fields);

        $resources = array_filter($data, function ($key) use ($fields) {
            return in_array(Helpers::camel($key), $fields);
        }, ARRAY_FILTER_USE_KEY);

        return $this->mapRelatedResources($resources, $fields);
    }

    protected function mapRelatedResources($resources, $fields)
    {
        if (empty(static::$mapResources)) {
            return $resources;
        }

        foreach(Helpers::camelCaseArrayKeys(static::$mapResources) as $field => $related){
            if (! in_array($field, $fields)) { continue; }

            $resources[Helpers::snake($field)] = $related::make(is_array($this->resource)
            ? $this->resource[$field]
            : $this->resource->getAttribute($field)
        )->setFieldKey(
            Str::of(Helpers::snake($field ) .  ".")
                ->when(
                    $this->fieldKey,
                    fn($str) => $str->prepend("{$this->fieldKey}")
                )->toString()
            );

        }

        return $resources;
    }

    protected function mapFieldData($request, $fields)
    {
        $data = parent::toArray($request);

        $hidden =  collect(is_array($this->resource) ? [] : $this->resource->getHidden())->filter(
            fn($field) => !in_array(Helpers::snake($field),  static::$allowedFields ?? []) && !in_array(Helpers::camel($field), static::$allowedFields ?? [])
        )->toArray();

        $missing = collect($fields)
        ->filter(
            fn($field) => !in_array(Helpers::snake($field),  $hidden) && !in_array(Helpers::camel($field), $hidden)
            )
        ->filter(
            fn($field) => array_key_exists(Helpers::camel($field), $data) || array_key_exists(Helpers::snake($field), $data) ? false : true
        )->toArray();

        foreach ($missing as $field) {
            $data[Helpers::snake($field)] = is_array($this->resource) ? null : $this->resource->getAttribute($field);
        }

        return $data;
    }

    /**
     * Checks for allowed fields.
     *
     * @param mixed $request
     *
     * @return array
     */
    protected function mapFields($request): array
    {
        $map = self::getDefaultFields($request);

        $defaultFields = $map === ['*'] ? array_keys($this->getResourceFields()) : $map;
        $allowedFields = static::$allowedFields ?? [];
        $fields = Helpers::filterFieldsFromRequest($request, $defaultFields, $allowedFields, $this->fieldKey);

        return $this->filterAllowedFields($fields);
    }

    public function setGuard(?string $guard): static
    {
        $this->guard = $guard;

        return $this;
    }

    public function filterAllowedFields($fields)
    {
        if (empty(static::$allowedFields) || static::$allowedFields === ['*']) {
            return $fields;
        }

        return array_filter($fields, function ($field) {
            return in_array($field, /** @scrutinizer ignore-type */static::$allowedFields);
        });
    }

    /**
     * gets the resource fields as an array.
     *
     * @return array
     */
    protected function getResourceFields(): array
    {
        return is_array($this->resource) ? $this->resource : $this->resource->getAttributes();
    }

    /**
     * Return default fields for this collection.
     *
     * @return array
     */
    public static function getDefaultFields($request): array
    {
        if (method_exists(get_called_class(), 'defaultFields')) {
            $c = get_called_class();

            return $c::defaultFields($request);
        }

        return static::$defaultFields ?? ['*'];
    }

    /**
     * Return allowed scopes for this collection.
     *
     * @return array
     * @author Sam Sehnert <sam@customd.com>
     */
    public static function getAllowedScopes($request): array
    {
        if (method_exists(get_called_class(), 'defaultScopes')) {
            $c = get_called_class();

            return $c::defaultScopes($request);
        }

        return static::$allowedScopes ?? [];
    }

    /**
     * which fields are we using for the select in the actual DB query
     *
     * @return array<int, string>
     */
    public static function getAlwaysSelectFields(): array
    {
        return static::$alwaysSelectFields;
    }

    /**
     * Method to filter the allowed fields through a gate to make sure that they are allowed to be viewed but he current viewer
     *
     * @param \Illuminate\Http\Request|\Illuminate\Foundation\Http\FormRequest $request
     *
     * @return array
     */
    protected function filterUserViewableFields($request): array
    {
        return collect($this->mapFields($request))
        ->when(
            ! empty(static::$fieldGates),
            fn($collection) => $collection->filter(fn($field) => $this->filterUserField($field, $request->user($this->guard)))
        )
        ->toArray();
    }

    protected function filterUserField(string $field, ?Authenticatable $user): bool
    {
        foreach (static::$fieldGates as $gate => $fields) {
            if (in_array($field, $fields)) {
                return Gate::forUser($user)->check($gate, [$this->resource, $field]);
            }
        }
        return true;
    }
}
