<?php

namespace User\Eloquent;

use Illuminate\Database\Capsule\Manager as DB;
use Illuminate\Database\Eloquent\Model;

class FunctionObject extends Model
{
    // use TreeEloquent;
    public $table = 'wu_obj_role_function_relation';
    // public $timestamps   = false;
    public $primaryKey   = ['UNION_ID','SYS_ID','ROLE_FUNC_ID','ROLE_FUNC_TYPE'];
    public $incrementing = false;
    const UPDATED_AT     = 'UPDATE_TIME';
    const CREATED_AT     = 'CREATE_TIME';
    public $guarded = [];

    public static function boot()
    {
        parent::boot();

        static::addGlobalScope('SYS_ID', function (Builder $builder) {
            $builder->where('SYS_ID', '=', C('SYS_ID'))
            // ->where('SYS_ID',C('MODULE'))
            ;
        });
    }

    // public function childs()
    // {
    //     return $this->hasMany(Functions::class, 'PARENT_ID', 'ID')->select('ID', 'PARENT_ID');
    // }

    // public function allChilds()
    // {
    //     return $this->childs()->with('allChilds');
    // }

    // public function childsByCondition()
    // {
    //     return $this->hasMany(Functions::class, 'PARENT_ID', 'ID')->select('ID', 'PARENT_ID');
    // }

    // public function allChildsByCondition()
    // {
    //     return $this->childsByCondition()->with('allChildsByCondition');
    // }

    public static function getTree($sys_id, $user_id, $union_id, $module, $handler)
    {
        $allFunction    = static::allFunction($sys_id, $union_id, $module);
        $allFunctionArr = static::transferToArray($allFunction);
        if ($handler == null) {
            $handler = function ($key, $value) use ($sys_id, $user_id) {
                $url = $value['url'];
                $pos = strpos($url, '?');
                if ($position === false) {
                    $value['url'] = $url . '?arg1=' . $user_id . '&arg2=' . $sys_id;
                } else {
                    $value['url'] = $url . '&arg1=' . $user_id . '&arg2=' . $sys_id;
                }
                return $value;
            };
        }

        foreach ($allFunctionArr as $key => $value) {
            $temp                 = $handler($key, $value);
            $allFunctionArr[$key] = $temp;
        }

        $allFunctionTree = static::generateTreeForArray($allFunctionArr, 'parent_id', 'id', 'items', '0');
        return $allFunctionTree;

    }

    public static function allFunction($sys_id, $union_id, $module)
    {

        $temp = DB::table('wu_obj_role_function_relation')
            ->join('wu_function', function ($join) {
                $join->on([
                    ['wu_obj_role_function_relation.RELATION_NAME', '=', 'wu_function.MODULE'],
                    ['wu_obj_role_function_relation.SYS_ID', '=', 'wu_function.SYS_ID'],
                ]);
                // $join->whereIn('wu_function.TYPE', [0, 1]);
                $join->where('wu_function.TYPE', '!=', 2);
            })
            ->whereIn('UNION_ID', [$union_id, '*'])
            ->where('wu_obj_role_function_relation.SYS_ID', $sys_id)
            ->where('wu_obj_role_function_relation.RELATION_NAME', $module)
        // ->where('wu_obj_role_function_relation.ROLE_FUNC_TYPE', '0')
            ->where('wu_obj_role_function_relation.ROLE_FUNC_ID', '*')

            ->select('wu_function.ID as id',
                'wu_function.SYS_ID as sys_id',
                'wu_function.MODULE_ID as module_id',
                'wu_function.PARENT_ID as parent_id',
                'wu_function.NAME as name',
                'wu_function.DESCRIPTION as description',
                'wu_function.MODULE as module',
                'wu_function.ISURL as isurl',
                'wu_function.URL as url',
                'wu_function.TYPE as type',
                'wu_function.ICON as icon',
                'wu_function.ORDER as order',
                'wu_function.CREATE_TIME as create_time',
                'wu_function.UPDATE_TIME as update_time',
                'wu_function.CREATE_BY as create_time',
                'wu_function.IS_TOP as is_top')
            ->orderBy('wu_function.ORDER', 'asc')
            ->orderBy('wu_function.CREATE_TIME', 'desc')
            ->get();
        if (count($temp) != 0) {
            return $temp;
            // return static::generateTree($result);
        }

        $queryBuilder1 = DB::table('wu_obj_role_function_relation')
            ->join('wu_role', function ($join) {
                $join->on([
                    ['wu_obj_role_function_relation.ROLE_FUNC_ID', '=', 'wu_role.ID'],
                    ['wu_obj_role_function_relation.SYS_ID', '=', 'wu_role.SYS_ID'],
                ]);
                $join->where('wu_role.STATUS', '=', '1');
            })
            ->join('wu_role_function', function ($join) {
                $join->on([
                    ['wu_obj_role_function_relation.ROLE_FUNC_ID', '=', 'wu_role_function.ROLE_ID'],
                ]);
            })
            ->join('wu_function', function ($join) {
                $join->on([
                    ['wu_obj_role_function_relation.RELATION_NAME', '=', 'wu_function.MODULE'],
                    ['wu_obj_role_function_relation.SYS_ID', '=', 'wu_function.SYS_ID'],
                    ['wu_role_function.FUNC_ID', '=', 'wu_function.ID'],
                ]);
                // $join->where('wu_function.TYPE', '=', '1');
                $join->where('wu_function.TYPE', '!=', '2');
            })
            ->whereIn('UNION_ID', [$union_id, '*'])
            ->where('wu_obj_role_function_relation.SYS_ID', $sys_id)
            ->where('wu_obj_role_function_relation.RELATION_NAME', $module)
            ->where('wu_obj_role_function_relation.ROLE_FUNC_TYPE', '1')
            ->select('wu_function.ID as id',
                'wu_function.SYS_ID as sys_id',
                'wu_function.MODULE_ID as module_id',
                'wu_function.PARENT_ID as parent_id',
                'wu_function.NAME as name',
                'wu_function.DESCRIPTION as description',
                'wu_function.MODULE as module',
                'wu_function.ISURL as isurl',
                'wu_function.URL as url',
                'wu_function.TYPE as type',
                'wu_function.ICON as icon',
                'wu_function.ORDER as order',
                'wu_function.CREATE_TIME as create_time',
                'wu_function.UPDATE_TIME as update_time',
                'wu_function.CREATE_BY as create_time',
                'wu_function.IS_TOP as is_top');

        $queryBuilder2 = DB::table('wu_function')
            ->where('wu_function.SYS_ID', $sys_id)
            ->where('wu_function.MODULE', $module)
            ->where('wu_function.TYPE', '0')
            ->select('wu_function.ID as id',
                'wu_function.SYS_ID as sys_id',
                'wu_function.MODULE_ID as module_id',
                'wu_function.PARENT_ID as parent_id',
                'wu_function.NAME as name',
                'wu_function.DESCRIPTION as description',
                'wu_function.MODULE as module',
                'wu_function.ISURL as isurl',
                'wu_function.URL as url',
                'wu_function.TYPE as type',
                'wu_function.ICON as icon',
                'wu_function.ORDER as order',
                'wu_function.CREATE_TIME as create_time',
                'wu_function.UPDATE_TIME as update_time',
                'wu_function.CREATE_BY as create_time',
                'wu_function.IS_TOP as is_top');

        $temp = DB::table('wu_obj_role_function_relation')
            ->join('wu_function', function ($join) {
                $join->on([
                    ['wu_obj_role_function_relation.RELATION_NAME', '=', 'wu_function.MODULE'],
                    ['wu_obj_role_function_relation.SYS_ID', '=', 'wu_function.SYS_ID'],
                    ['wu_obj_role_function_relation.ROLE_FUNC_ID', '=', 'wu_function.ID'],
                ]);
                $join->where('wu_function.TYPE', '!=', '2');
            })
            ->whereIn('UNION_ID', [$union_id, '*'])
            ->where('wu_obj_role_function_relation.SYS_ID', $sys_id)
            ->where('wu_obj_role_function_relation.RELATION_NAME', $module)
            ->where('wu_obj_role_function_relation.ROLE_FUNC_TYPE', '0')

            ->select('wu_function.ID as id',
                'wu_function.SYS_ID as sys_id',
                'wu_function.MODULE_ID as module_id',
                'wu_function.PARENT_ID as parent_id',
                'wu_function.NAME as name',
                'wu_function.DESCRIPTION as description',
                'wu_function.MODULE as module',
                'wu_function.ISURL as isurl',
                'wu_function.URL as url',
                'wu_function.TYPE as type',
                'wu_function.ICON as icon',
                'wu_function.ORDER as order',
                'wu_function.CREATE_TIME as create_time',
                'wu_function.UPDATE_TIME as update_time',
                'wu_function.CREATE_BY as create_time',
                'wu_function.IS_TOP as is_top')
            ->union($queryBuilder1)
        // ->union($queryBuilder2)
        // 会报错
        // ->orderBy('wu_function.ORDER', 'asc')
        // ->orderBy('wu_function.CREATE_TIME', 'desc')
            ->get()
            ->sortBy('ORDER')
            ->sortBy('CREATE_TIME', desc);
        if (count($temp) != 0) {
            return $temp;
            // return static::generateTree($result);
        }

        $temp = DB::table('wu_role_function')
            ->join('wu_function', function ($join) {
                $join->on([
                    ['wu_role_function.FUNC_ID', '=', 'wu_function.ID'],
                ]);
                $join->where('wu_function.TYPE', '!=', '2');
            })
            ->where('wu_role_function.ROLE_ID', 'default')
            ->where('wu_function.MODULE', $module)
            ->select('wu_function.ID as id',
                'wu_function.SYS_ID as sys_id',
                'wu_function.MODULE_ID as module_id',
                'wu_function.PARENT_ID as parent_id',
                'wu_function.NAME as name',
                'wu_function.DESCRIPTION as description',
                'wu_function.MODULE as module',
                'wu_function.ISURL as isurl',
                'wu_function.URL as url',
                'wu_function.TYPE as type',
                'wu_function.ICON as icon',
                'wu_function.ORDER as order',
                'wu_function.CREATE_TIME as create_time',
                'wu_function.UPDATE_TIME as update_time',
                'wu_function.CREATE_BY as create_time',
                'wu_function.IS_TOP as is_top')
            ->orderBy('wu_function.ORDER', 'asc')
            ->orderBy('wu_function.CREATE_TIME', 'desc')
            ->get();
        if (count($temp) != 0) {
            return $temp;
            // return static::generateTree($result);
        }
        return $temp;

    }

    public static function generateTreeForObject($result = null, $foreignKey = 'parent_id', $localKey = 'id', $childName = 'childs', $rootId = '0')
    {
        if (count($result) != 0) {
            $result = TreeEloquent::generateTreeForObject($result, $foreignKey, $localKey, $childName, $rootId);
            $result;
        }
        return $result;
    }

    public static function generateTreeForArray($result = null, $foreignKey = 'parent_id', $localKey = 'id', $childName = 'childs', $rootId = '0')
    {
        if (count($result) != 0) {
            $result = TreeEloquent::generateTreeForArray($result, $foreignKey, $localKey, $childName, $rootId);
            return $result;
        }
        return $result;
    }

    public static function transferToArray($result)
    {
        return json_decode(json_encode($result), true);
    }
}
