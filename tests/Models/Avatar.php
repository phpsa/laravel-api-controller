<?php

namespace Phpsa\LaravelApiController\Tests\Models;

use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Foundation\Auth\User as Authenticatable;

class Avatar extends Authenticatable
{
    protected $table = 'avatars';
    public $timestamps = false;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'id',
        'url',
        'avatarable_id',
        'avatarable_type',
    ];

    public function avatarable(): MorphTo
    {
        return $this->morphTo();
    }
}
