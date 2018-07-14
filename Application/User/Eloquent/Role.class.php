<?php
namespace User\Eloquent;
use Illuminate\Database\Eloquent\Model;

class Role extends Model
{
    public $table        = 'wu_role';
    // public $timestamps   = false;
    public $incrementing = false;
    const UPDATED_AT= 'UPDATE_TIME';
    const CREATED_AT = 'CREATE_TIME';

    public $guarded = [];

    /**
     * 数据模型的启动方法
     *
     * @return void
     */
    protected static function boot()
    {
        parent::boot();

        static::addGlobalScope('sys_id', function (Builder $builder) {
            $builder->where('SYS_ID', '=', C('SYS_ID'))
            ->where('MODULE','=',C('MODULE'));
        });
    }
}
