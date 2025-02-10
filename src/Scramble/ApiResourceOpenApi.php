<?php

namespace Phpsa\LaravelApiController\Scramble;

use ReflectionProperty;
use Dedoc\Scramble\Infer\Scope\Scope;
use Dedoc\Scramble\Support\Type\Type;
use Dedoc\Scramble\Infer\Scope\GlobalScope;
use Dedoc\Scramble\Support\Type\ObjectType;
use Dedoc\Scramble\Support\Type\ArrayItemType_;
use Dedoc\Scramble\Support\Type\KeyedArrayType;
use Dedoc\Scramble\Support\Helpers\JsonResourceHelper;
use Dedoc\Scramble\Infer\Services\ReferenceTypeResolver;
use Phpsa\LaravelApiController\Http\Resources\ApiResource;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Dedoc\Scramble\Support\Type\UnknownType as UnknownTypeObject;
use Dedoc\Scramble\Support\Type\Reference\PropertyFetchReferenceType;
use Dedoc\Scramble\Support\PhpDoc;
use Dedoc\Scramble\Support\TypeToSchemaExtensions\JsonResourceTypeToSchema;
use ReflectionClass;

class ApiResourceOpenApi extends JsonResourceTypeToSchema
{
    public function shouldHandle(Type $type): bool
    {

        return $type instanceof ObjectType
        && $type->isInstanceOf(ApiResource::class)
        && ! $type->isInstanceOf(AnonymousResourceCollection::class);
    }

    public function toSchema(Type $type)
    {
        $x = $this->infer->analyzeClass($type->name);

        $scope = new GlobalScope();

        $modelType = JsonResourceHelper::modelType($scope->index->getClassDefinition($type->name), $scope);

       /** @var \Dedoc\Scramble\Support\Type\ObjectType $type */

        $array = $type->getMethodDefinition('toArray')
        ?->type
        ?->getReturnType();

        if ($array && ! $array instanceof UnknownTypeObject) {
            return $this->openApiTransformer->transform($array);
        }

        $fields = [...$this->getPropertyArray($type, 'allowedFields', $modelType, $scope, true), ...$this->getPropertyArray($type, 'defaultFields', $modelType, $scope, false)];

        $file_url = $this->getModelPropertyType($type, 'file_url', $scope);

        if (blank($fields) && $modelType instanceof ObjectType) {
            $fields = $modelType->getMethodReturnType('toArray', [], $scope)->items; //@phpstan-ignore property.notFound
        }

        //@todo -- check the mapResources property and add those fields to the array

        $array = new KeyedArrayType(array_values($fields));

        return $this->openApiTransformer->transform($array);
    }

    private function getPropertyArray(Type $type, string $propertyName, Type $modelType, Scope $scope, bool $isOptional): array
    {
        $prop = new ReflectionProperty($type->name, $propertyName); //@phpstan-ignore property.notFound
        $val = $prop->getValue();
        if (! is_array($val)) {
            return [];
        }
        return collect($val)->mapWithKeys(function ($item) use ($modelType, $scope, $isOptional) {
            return [$item => new ArrayItemType_($item, $this->getModelPropertyType($modelType, $item, $scope), $isOptional) ];
        })->toArray();
    }

    private function getModelPropertyType(Type $modeltype, string $name, $scope): Type
    {
        return ReferenceTypeResolver::getInstance()->resolve(
            $scope,
            new PropertyFetchReferenceType(
                $modeltype,
                $name,
            ),
        );
    }
}
