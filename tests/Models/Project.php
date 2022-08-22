<?php

namespace Phpsa\LaravelApiController\Tests\Models;

use Illuminate\Database\Eloquent\Model;
use Phpsa\LaravelApiController\Tests\Models\User;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Phpsa\LaravelApiController\Tests\Models\Avatar;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Project extends Model
{
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

    public function avatar(): MorphOne
    {
        return $this->morphOne(Avatar::class, 'avatarable');
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class)->withPivot('team_leader');
    }
}
