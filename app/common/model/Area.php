<?php


namespace app\common\model;

use think\facade\Cache;
use think\Model;

/**
 * 地区模型
 */
class Area extends Model
{

    protected static $cacheAll;
    protected static $cacheTree;

    /**
     * 根据经纬度获取当前地区信息
     *
     * @param string $lng 经度
     * @param string $lat 纬度
     * @return Area 城市信息
     */
    public static function getAreaFromLngLat($lng, $lat, $level = 3)
    {
        $namearr = [1 => 'geo:province', 2 => 'geo:city', 3 => 'geo:district'];
        $rangearr = [1 => 15000, 2 => 1000, 3 => 200];
        $geoname = isset($namearr[$level]) ? $namearr[$level] : $namearr[3];
        $georange = isset($rangearr[$level]) ? $rangearr[$level] : $rangearr[3];
        // 读取范围内的ID
        $redis = Cache::store('redis')->handler();
        $georadiuslist = [];
        if (method_exists($redis, 'georadius')) {
            $georadiuslist = $redis->georadius($geoname, $lng, $lat, $georange, 'km', ['WITHDIST', 'COUNT' => 5, 'ASC']);
        }

        if ($georadiuslist) {
            list($id, $distance) = $georadiuslist[0];
        }
        $id = isset($id) && $id ? $id : 3;
        return self::get($id);
    }

    /**
     * 根据经纬度获取省份
     *
     * @param string $lng 经度
     * @param string $lat 纬度
     * @return Area
     */
    public static function getProvinceFromLngLat($lng, $lat)
    {
        $provincedata = null;
        $citydata = self::getCityFromLngLat($lng, $lat);
        if ($citydata) {
            $provincedata = self::get($citydata['pid']);
        }
        return $provincedata;
    }

    /**
     * 根据经纬度获取城市
     *
     * @param string $lng 经度
     * @param string $lat 纬度
     * @return Area
     */
    public static function getCityFromLngLat($lng, $lat)
    {
        $citydata = null;
        $districtdata = self::getDistrictFromLngLat($lng, $lat);
        if ($districtdata) {
            $citydata = self::get($districtdata['pid']);
        }
        return $citydata;
    }

    /**
     * 根据经纬度获取地区
     *
     * @param string $lng 经度
     * @param string $lat 纬度
     * @return Area
     */
    public static function getDistrictFromLngLat($lng, $lat)
    {
        $districtdata = self::getAreaFromLngLat($lng, $lat, 3);
        return $districtdata;
    }

    /**
     * 根据id获取地区名称
     * @param $id
     * @return string
     */
    public static function getNameById($id)
    {
        return $id > 0 ? self::getCacheAll()[$id]['name'] : '其他';
    }

    /**
     * 根据名称获取地区id
     * @param $name
     * @param int $level
     * @param int $pid
     * @return mixed
     */
    public static function getIdByName($name, $level = 0, $pid = 0)
    {
        $data = self::getCacheAll();
        foreach ($data as $item) {
            if ($item['name'] == $name && $item['level'] == $level && $item['pid'] == $pid)
                return $item['id'];
        }
        return 0;
    }

    /**
     * 获取所有地区(树状结构)
     * @return mixed
     */
    public static function getCacheTree()
    {
        empty(static::$cacheTree) && (static::$cacheTree = self::regionCache()['tree']);
        return static::$cacheTree;
    }

    /**
     * 获取所有地区
     * @return mixed
     */
    public static function getCacheAll()
    {
        empty(static::$cacheAll) && (static::$cacheAll = self::regionCache()['all']);
        return static::$cacheAll;
    }

    /**
     * 获取地区缓存
     * @return mixed
     */
    private static function regionCache()
    {
        if (!Cache::get('region_cache')) {
            // 所有地区
            $all = $allData = self::withoutGlobalScope()->column('id, pid, name, level', 'id');
            // 格式化
            $tree = [];
            foreach ($allData as $pKey => $province) {
                if ($province['level'] == 1) {    // 省份
                    $tree[$province['id']] = $province;
                    unset($allData[$pKey]);
                    foreach ($allData as $cKey => $city) {
                        if ($city['level'] == 2 && $city['pid'] == $province['id']) {    // 城市
                            $tree[$province['id']]['city'][$city['id']] = $city;
                            unset($allData[$cKey]);
                            foreach ($allData as $rKey => $region) {
                                if ($region['level'] == 3 && $region['pid'] == $city['id']) {    // 地区
                                    $tree[$province['id']]['city'][$city['id']]['region'][$region['id']] = $region;
                                    unset($allData[$rKey]);
                                }
                            }
                        }
                    }
                }
            }
            Cache::tag('cache')->set('region_cache', compact('all', 'tree'));
        }
        return Cache::get('region_cache');
    }

    /**
     * 以children形式获取
     * @return mixed
     */
    public static function getAllCacheChildren($array_values=1){

        $cache_name="getAllCacheTree".$array_values;
        //if (!Cache::get($cache_name)) {
        if (true) {
            // 所有地区
            $allData = self::withoutGlobalScope()->column('id, pid, name, level', 'id');
            // 格式化
            $tree = [];
            foreach ($allData as $pKey => $province) {
                if ($province['level'] == 1) {
                    $temp1 = array();
                    $temp1['value'] = $province['id'];
                    $temp1['name'] = $province['name'];
                    $tree[$province['id']] = $temp1;
                    unset($allData[$pKey]);
                    foreach ($allData as $cKey => $city) {
                        if ($city['level'] == 2 && $city['pid'] == $province['id']) {    // 城市
                            $temp1['value'] = $city['id'];
                            $temp1['name'] = $city['name'];
                            $tree[$province['id']]['children'][$city['id']] = $temp1;
                            unset($allData[$cKey]);
                            foreach ($allData as $rKey => $region) {
                                if ($region['level'] == 3 && $region['pid'] == $city['id']) {    // 地区
                                    $temp1['value'] = $region['id'];
                                    $temp1['name'] = $region['name'];
                                    $tree[$province['id']]['children'][$city['id']]['children'][$region['id']] = $temp1;
                                    unset($allData[$rKey]);
                                }
                            }
                        }
                    }
                }
            }
            if ($array_values){
                self::forArrayValues($tree);
            }
            Cache::tag('cache')->set($cache_name, $tree);
        }

        return Cache::get($cache_name);

    }
    private static function forArrayValues(&$tree){
        $tree=array_values($tree);
        foreach ($tree as &$row){
            if (isset($row['children'])){
                self::forArrayValues($row['children']);
            }
        }

    }



}
