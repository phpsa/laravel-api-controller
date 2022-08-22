<?php

namespace Phpsa\LaravelApiController\Tests\Models;

use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Builder;
//use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Phpsa\LaravelApiController\Tests\Models\Avatar;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Phpsa\LaravelApiController\Tests\Factories\UserFactory;
use Phpsa\LaravelApiController\Tests\Factories\UserFactory as FactoriesUserFactory;

class User extends Authenticatable
{
    use HasFactory;
    use Notifiable;

    protected $table = 'users';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    protected static function newFactory()
    {
        return UserFactory::new();
    }

    public function profile(): HasOne
    {
        return $this->hasOne(UserProfile::class);
    }

    public function avatar(): MorphOne
    {
        return $this->morphOne(Avatar::class, 'avatarable');
    }

    public function projects(): BelongsToMany
    {
        return $this->belongsToMany(Project::class)->withPivot('team_leader');
    }

    public function scopeHas2Fa(Builder $builder, ?bool $on = null)
    {
        switch ($on) {
            case false:
                $builder->whereNull('two_factor_secret');
                $builder->whereNull('two_factor_recovery_codes');
                break;
            default:
                $builder->whereNotNull('two_factor_secret');
                $builder->whereNotNull('two_factor_recovery_codes');
                break;
        }
    }

    public function scopeNotCallable(Builder $builder)
    {
    }
}
