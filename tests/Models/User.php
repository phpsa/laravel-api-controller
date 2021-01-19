<?php

namespace Phpsa\LaravelApiController\Tests\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Phpsa\LaravelApiController\Tests\Factories\UserFactory as FactoriesUserFactory;

class User extends Authenticatable
{

    use HasFactory, Notifiable;

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
        return FactoriesUserFactory::new();
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
}
