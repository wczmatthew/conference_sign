<?php

namespace User\Eloquent;

use Illuminate\Database\Capsule\Manager as DB;
use Illuminate\Database\Eloquent\Model;

class RoleFunction extends Model
{
    // use TreeEloquent;
    public $table = 'wu_role_function';
    public $timestamps   = false;
    public $primaryKey   = ['ROLE_ID','FUNC_ID'];
    public $incrementing = false;
    // const UPDATED_AT     = 'UPDATE_TIME';
    // const CREATED_AT     = 'CREATE_TIME';
    public $guarded = [];

    public static function boot()
    {
        parent::boot();

        // static::addGlobalScope('SYS_ID', function (Builder $builder) {
        //     $builder->where('SYS_ID', '=', C('SYS_ID'))
        //     // ->where('SYS_ID',C('MODULE'))
        //     ;
        // });
    }
}
