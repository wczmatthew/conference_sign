<?php

namespace User\Eloquent;

use Illuminate\Database\Capsule\Manager as DB;

class TreeEloquent
{
    public static function getTree($table = 'table', $foreignKey = 'parent_id', $localKey = 'id', $condition = [], $childName = 'childs', $parentArr = null, $rootId = 'rootId')
    {
        if ($parentArr == null || count($parentArr) == 0) {
            $parentArr = DB::table($table)->where($condition)->where($foreignKey, $rootId)->get();
        }
        $keyArr = [];
        foreach ($parentArr as $item) {
            $keyArr[] = $item->$localKey;
        }
        $childs = DB::table($table)->where($condition)->whereIn($foreignKey, $keyArr)->get();
        if (count($childs) != 0) {
            // 为子再添加子
            $childs = TreeEloquent::getTree($table, $foreignKey, $localKey, $condition, $childName, $childs);
            foreach ($parentArr as $key => $item) {
                // $item->$childName = [];
                $childArr = [];
                foreach ($childs as $child) {
                    if ($item->$localKey == $child->$foreignKey) {
                        $childArr[] = $child;
                    }
                }
                $parentArr[$key]->$childName = $childArr;
            }
        }
        return $parentArr;
    }

    /**
     * 有错还不能使用
     * @param  string $foreignKey   [description]
     * @param  string $localKey     [description]
     * @param  [type] $queryBuilder [description]
     * @param  string $childName    [description]
     * @param  [type] $parentArr    [description]
     * @param  string $rootId       [description]
     * @return [type]               [description]
     */
    public static function getTreeByQueryBuild($foreignKey = 'parent_id', $localKey = 'id', $queryBuilder = null, $childName = 'childs', $parentArr = null, $rootId = 'rootId')
    {
        if ($parentArr == null || count($parentArr) == 0) {
            // clone 是浅拷贝
            // $tempoQueryBuilder = clone $queryBuilder;
            $tempoQueryBuilder = unserialize(serialize($queryBuilder));
            $parentArr         = $tempoQueryBuilder->where($foreignKey, $rootId)->get();
        }
        $keyArr = [];
        foreach ($parentArr as $item) {
            $keyArr[] = $item->$localKey;
        }
        $tempoQueryBuilder = unserialize(serialize($queryBuilder));

        $childs = $tempoQueryBuilder->whereIn($foreignKey, $keyArr)->get();
        if (count($childs) != 0) {
            // 为子再添加子
            $childs = TreeEloquent::getTree($foreignKey, $localKey, $queryBuilder, $childName, $childs);
            foreach ($parentArr as $key => $item) {
                // $item->$childName = [];
                $childArr = [];
                foreach ($childs as $child) {
                    if ($item->$localKey == $child->$foreignKey) {
                        $childArr[] = $child;
                    }
                }
                $parentArr[$key]->$childName = $childArr;
            }
        }
        return $parentArr;

    }

    /**
     * 有错还不能使用
     * @param  string $table       [description]
     * @param  string $foreignKey  [description]
     * @param  string $localKey    [description]
     * @param  [type] $queryString [description]
     * @param  string $childName   [description]
     * @param  [type] $parentArr   [description]
     * @param  string $rootId      [description]
     * @return [type]              [description]
     */
    // public static function getTreeByQueryString($table = 'table', $foreignKey = 'parent_id', $localKey = 'id', $queryString = null, $childName = 'childs', $parentArr = null, $rootId = 'rootId')
    // {
    //     if ($parentArr == null || count($parentArr) == 0) {

    //         // $parentArr = DB::table($table)$queryString->where($foreignKey,$rootId)->get();
    //     }
    //     $keyArr = [];
    //     foreach ($parentArr as $item) {
    //         $keyArr[] = $item->$localKey;
    //     }
    //     $queryString = '->where()';
    //     childs = DB::table($table) $queryString ->whereIn($foreignKey, $keyArr)->get();
    //     if (count($childs) != 0) {
    //         // 为子再添加子
    //         $childs = TreeEloquent::getTree($table, $foreignKey, $localKey, $queryBuilder, $childName, $childs);
    //         foreach ($parentArr as $key => $item) {
    //             $item->$childName = [];
    //             $childArr = [];
    //             foreach ($childs as $child) {
    //                 if ($item->$localKey == $child->$foreignKey) {
    //                     $childArr[] = $child;
    //                 }
    //             }
    //             $parentArr[$key]->$childName = $childArr;
    //         }
    //     }
    //     return $parentArr;
    // }

    public static function generateTreeForObject($result = null,$foreignKey = 'parent_id', $localKey = 'id', $childName = 'childs',  $rootId = '0', &$parentArr = null)
    {
        $isRoot        = false;
        $allChildArr   = [];
        $unsetIndexArr = [];
        $keyArr        = [];
        $itemArr       = [];
        // 根据rootId获取第一层
        if ($parentArr === null || count($parentArr) === 0) {
            $isRoot    = true;
            $parentArr = [];
            foreach ($result as $key => $item) {
                if ($item->$foreignKey === $rootId) {
                    $parentArr[] = $item;
                    // 保存已设置的key
                    $unsetIndexArr[] = $key;
                }
            }
        }
        //删除已设置的数据
        foreach ($unsetIndexArr as $key => $value) {
            unset($result[$value]);
        }

        $unsetIndexArr = [];
        foreach ($parentArr as $key => $parent) {
            unset($tempParent);
            $tempParent = $parent;
            unset($childArr);
            $childArr = [];
            // $tempParent->$childName = &$childArr;
            $tempParent->$childName = &$childArr;
            foreach ($result as $childKey => $item) {
                if ($item->$foreignKey === $tempParent->$localKey) {
                    // 由于allChildArr和childArr保存的是temp变量的别名，所以每次temp重新赋值会影响到其中的数据
                    // 使用unset删除变量就会取消这种关系
                    unset($temp);
                    $temp = $item;
                    // 保存所有子
                    $allChildArr[] = &$temp;
                    // 保存当前父的所有子
                    $childArr[] = &$temp;

                    // 保存已设置的key
                    $unsetIndexArr[] = $childKey;
                }
            }
            $parentArr[$key] = &$tempParent;
        }

        foreach ($unsetIndexArr as $value) {
            unset($result[$value]);
        }

        if (count($allChildArr) == 0) {
            if ($isRoot) {
                return $parentArr;
            }
        }

        static::generateTreeForObject($result,$foreignKey, $localKey, $childName, $rootId, $allChildArr );
        if ($isRoot) {
            return $parentArr;
        }
    }

    public static function generateTreeForArray( $result = null,$foreignKey = 'parent_id', $localKey = 'id', $childName = 'childs', $rootId = '0', &$parentArr = null)
    {
        $isRoot        = false;
        $allChildArr   = [];
        $unsetIndexArr = [];
        $keyArr        = [];
        $itemArr       = [];
        // 根据rootId获取第一层
        if ($parentArr === null || count($parentArr) === 0) {
            $isRoot    = true;
            $parentArr = [];
            foreach ($result as $key => $item) {
                if ($item[$foreignKey] === $rootId) {
                    $parentArr[] = $item;
                    // 保存已设置的key
                    $unsetIndexArr[] = $key;
                }
            }
        }
        //删除已设置的数据
        foreach ($unsetIndexArr as $key => $value) {
            unset($result[$value]);
        }

        $unsetIndexArr = [];
        foreach ($parentArr as $key => $parent) {
            unset($tempParent);
            $tempParent = $parent;
            unset($childArr);
            $childArr = [];
            // $tempParent[$childName] = &$childArr;
            $tempParent[$childName] = &$childArr;
            foreach ($result as $childKey => $item) {
                if ($item[$foreignKey] === $tempParent[$localKey]) {
                    // 由于allChildArr和childArr保存的是temp变量的别名，所以每次temp重新赋值会影响到其中的数据
                    // 使用unset删除变量就会取消这种关系
                    unset($temp);
                    $temp = $item;
                    // 保存所有子
                    $allChildArr[] = &$temp;
                    // 保存当前父的所有子
                    $childArr[] = &$temp;

                    // 保存已设置的key
                    $unsetIndexArr[] = $childKey;
                }
            }
            $parentArr[$key] = &$tempParent;
        }

        foreach ($unsetIndexArr as $value) {
            unset($result[$value]);
        }

        if (count($allChildArr) == 0) {
            if ($isRoot) {
                return $parentArr;
            }
        }

        static::generateTreeForArray($result,$foreignKey, $localKey, $childName, $rootId, $allChildArr );
        if ($isRoot) {
            return $parentArr;
        }
    }

}
