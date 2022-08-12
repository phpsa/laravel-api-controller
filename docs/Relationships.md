## Relationships

- Using the relationships defined in your models, you can pass a comma delimited list eg `include=join1,join2` which will return those joins (one or many).

Simply add a `protected static $mapResources` to your `Resource` to define which resources to assign your related data. E.e., for a one to many relationship, you should specify a collection, and a one-to-one relationship specify the related resource directly. This will allow the API to properly format the related record.

```
    protected static $mapResources = [
        'notes' => NotesCollection::class,
        'owner' => OwnerResource::class
    ];
```

- You can automatically update and create related records for most types of relationships. Just include the related resource name in your POST or PUT request.
- Important: if you are using `$defaultFields` and/or `$allowedFields` in your resource, the related resource key from `$mapResources` must also be included in those lists for that related resource to be included.

For `BelongsToMany` or `MorphToMany` relationships, you can choose the sync strategy. By default, this will take an _additive_ strategy. That is to say, related records sent will be ADDED to any existing related records. On a request-by-request basis, you can opt for a _sync_ strategy which will remove the pivot for any related records not listed in the request. Note the actual related record will not be removed, just the pivot entry.

To opt for the _sync_ behavaiour, set `?sync[field]=true` in your request.

### HasOne (ONE_TO_ONE)
//function xxx() :HasOne

### BelongsTo - OneToOne Inverse

### HasMany - oneToMany
// consider pivots

### BelongsToMany - onetoManyInverse
// consider pivots

### Has One Through (new)
### Has Many Through (new)

### HasOneThrough

### BelongsToThrough
### MorphOne

### MorphMany

### Polymorphic One To One

### Polymorphic One To Many–

### Polymorphic One Of Many

### Polymorphic Many To Many

### #syncing-associations
