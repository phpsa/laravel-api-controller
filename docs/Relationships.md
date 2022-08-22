# Relationships

- Using the relationships defined in your models, you can pass a comma delimited list eg `include=join1,join2` which will return those joins (one or many).

Simply add a `protected static $mapResources` to your `Resource` to define which resources to assign your related data. E.e., for a one to many relationship, you should specify a collection, and a one-to-one relationship specify the related resource directly. This will allow the API to properly format the related record.

```php
    protected static $mapResources = [
        'notes' => NotesCollection::class,
        'owner' => OwnerResource::class
    ];
```

- You can automatically update and create related records for most types of relationships. Just include the related resource name in your POST or PUT request.
- Important: if you are using `$defaultFields` and/or `$allowedFields` in your resource, the related resource key from `$mapResources` must also be included in those lists for that related resource to be included.

For `BelongsToMany` or `MorphToMany` relationships, you can choose the sync strategy. By default, this will take an _additive_ strategy. That is to say, related records sent will be ADDED to any existing related records. On a request-by-request basis, you can opt for a _sync_ strategy which will remove the pivot for any related records not listed in the request. Note the actual related record will not be removed, just the pivot entry.

To opt for the _sync_ behavaiour, set `?sync[field]=true` in your request.

---

## One-to-One (HasOne)

A User _has one_ Profile.

<details><summary>View model structure and classes</summary>

```
users
    id      - integer
    name    - string
profiles
    id      - integer
    user_id - integer
    phone   - string
```

```php
class User extends Model
{
    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasOne<Profile>
     */
    public function profile(): HasOne
    {
        return $this->hasOne(Profile::class);
    }
}
```

```php
class Profile extends Model
{
    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo<User,Profile>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
```

</details>

#### Ensure Profiles are included in response by `include`-ing the relationship name (`profile`)

`GET: api/v1/users?include=profile`

#### Get Users matching a search on the Profile phone field

`GET: api/v1/users?include=profile&filter[profile.phone~]=1234567`

#### Create a new User with Profile data

`POST: api/v1/users`

```json
{
  "name": "MyName",
  "profile": {
    "phone": "0123456"
  }
}
```

#### Update field on Profile

`PUT: api/v1/users/1`

```json
{
  "profile": {
    "phone": "0123456"
  }
}
```

#### Delete related Profile

`PUT: api/v1/users/1`

```json
{
  "profile": null
}
```

---

## One-to-One Inverse, Many-to-One Inverse (BelongsTo)

In this example, a Task _belongs to_ the Project and the Project _has many_ Tasks. In other examples, a Profile could _belong to_ a User and the User could \_ could BelongTo

<details><summary>View model structure and classes</summary>

```
projects
    id         - integer
    name       - string (just for example)
tasks
    id         - integer
    project_id - integer
    name       - string (just for example)
```

```php
class Project extends Model
{
    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany<Task>
     */
    public function tasks(): HasOne
    {
        return $this->hasMany(Task::class);
    }
}
```

```php
class Task extends Model
{
    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo<Project,Task>
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }
}
```

</details>

#### Ensure Tasks are included in response by `include`-ing the relationship name (`tasks`)

`GET: api/v1/projects?include=tasks`

#### Get Projects matching a search on the Task name field

`GET: api/v1/projects?include=tasks&filter[tasks.name~]=launch`

#### Create a new Project with Tasks

`POST: api/v1/projects`

```json
{
  "name": "MyName",
  "tasks": [
    {
      "phone": "0123456"
    }
  ]
}
```

#### Update field on Profile

`PUT: api/v1/users/1`

```json
{
  "profile": {
    "phone": "0123456"
  }
}
```

#### Delete related Profile

`PUT: api/v1/users/1`

```json
{
  "profile": null
}
```

---

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

### (laravel docs) #syncing-associations
