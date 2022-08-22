<?php

namespace Phpsa\LaravelApiController\Tests\Models;

use Illuminate\Database\Eloquent\Model;
use Phpsa\LaravelApiController\Tests\Models\User;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserProfile extends Model
{
    protected $table = 'user_profiles';

    /**
     * @var array
     */
    protected $fillable = [
        'user_id',
        'phone',
        'address',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
