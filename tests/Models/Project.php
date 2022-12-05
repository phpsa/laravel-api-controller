<?php

namespace Phpsa\LaravelApiController\Tests\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Phpsa\LaravelApiController\Tests\Models\User;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Phpsa\LaravelApiController\Tests\Models\Avatar;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Project extends Model
{

    use HasFactory;

    protected $table = 'projects';

    /**
     * @var array
     */
    protected $fillable = [
        'name',
        'description',
        'cost_per_hour',
    ];

    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class);
    }
}
