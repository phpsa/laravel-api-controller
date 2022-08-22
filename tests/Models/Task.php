<?php

namespace Phpsa\LaravelApiController\Tests\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Task extends Model
{
    protected $table = 'tasks';

    /**
     * @var array
     */
    protected $fillable = [
        'name',
        'project_id',
        'completed_at',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    // public function projectOwner(): BelongsToThrough
    // {
    //     return $this->belongsToThrough(Project::class, User::class);
    // }
}
