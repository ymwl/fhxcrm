<?php
namespace app\admin\model\traits;

use think\facade\Db;

class MyDb
{
    /**排名函数
     * @param string $table_name
     * @param string $sort
     * @param array $where
     * @param array $has
     * @param string $field
     * @param string $rownum
     * @return mixed
     */
    public static function GetRanking(string $table_name,string $sort,array $where=[],array $has=[],string $field='*',string $rownum='rownum'){
        $prefix = getDataBaseConfig('prefix');
        if(strpos($table_name,$prefix)===false){
            $table_name = $prefix.$table_name;
        }
        $prefix = getDataBaseConfig('prefix');
        if(strpos($table_name,$prefix)===false){
            $table_name = $prefix.$table_name;
        }
        $whereSql = "";
        if(!empty($where)){
            $whereSql .= " WHERE";
            foreach ($where as $key=>$value){
                $whereSql.= " {$key}=? AND";
                $wheres[] = $value;
            }
            $whereSql = trim($whereSql,'AND');
        }
        $hasSql = "";
        if(!empty($has)){
            $hasSql .= " WHERE";
            foreach ($has as $key=>$value){
                $hasSql .= " {$key}=? AND";
                $wheres[] = $value;
            }
            $hasSql = trim($hasSql,'AND');
        }

        if(empty($hasSql)){
            $sql = "SELECT t.$field, @$rownum := @$rownum + 1 AS $rownum FROM (SELECT @$rownum := 0) r, (SELECT * FROM $table_name $whereSql ORDER BY $sort) AS t;";
//            $array = Db::query($sql);
        }else{
            $sql = "SELECT b.$field FROM (SELECT t.*, @$rownum := @$rownum + 1 AS $rownum FROM (SELECT @$rownum := 0) r,(SELECT * FROM $table_name $whereSql ORDER BY $sort) AS t) AS b $hasSql";
//            $wheres = [];
//            foreach ($where as $key=>$value){
//                $sql.= " {$key}=? AND";
//                $wheres[] = $value;
//            }
//            $sql = trim($sql,'AND');
//            $array = Db::query($sql,$wheres);
        }
        $array = Db::query($sql,$wheres);
        return $array;
    }

    /**求距离
     * @param string $table_name
     * @param array|int[] $location
     * @param array $where
     * @param string $having
     * @param string $lng
     * @param string $lat
     * @param string $field
     * @param string $distance
     * @return Db
     */
    public static function Distance(string $table_name,array $location=['lng'=>0,'lat'=>0],array $where=[],string $having='',string $lng='lng',string $lat='lat',string $field='*',string $distance='distance'){
        $data = Db::name($table_name)
            ->where($where)
            ->field($field.",ROUND(
        6378.138 * 2 * ASIN(
            SQRT(
                POW(
                    SIN(
                        (
                            {$location['lat']} * PI() / 180 - {$lat} * PI() / 180
                        ) / 2
                    ),
                    2
                ) + COS({$location['lat']} * PI() / 180) * COS({$lat} * PI() / 180) * POW(
                    SIN(
                        (
                            {$location['lng']} * PI() / 180 - {$lng} * PI() / 180
                        ) / 2
                    ),
                    2
                )
            )
        ) * 1000
    ) AS {$distance}")
            ->having($having);
        return $data;
    }

    /**求取经纬度转直线距离
     * @param array|int[] $location1
     * @param array|int[] $location2
     * @return float|int
     */
    public static function DegreeDistance(array $location1=['lng'=>0,'lat'=>0],array $location2=['lng'=>0,'lat'=>0]){
        $EARTH_RADIUS = 6378.137;

        $radLat1 = self::rad($location1['lat']);
        $radLat2 = self::rad($location2['lat']);
        $a = $radLat1 - $radLat2;
        $b = self::rad($location1['lng']) - self::rad($location2['lng']);
        $s = 2 * asin(sqrt(pow(sin($a / 2), 2) + cos($radLat1) * cos($radLat2) * pow(sin($b / 2), 2)));
        $s = $s * $EARTH_RADIUS;
        $s = round($s * 10000) / 10000;

        return $s*1000;
    }
    protected static function rad($d){
        return $d * M_PI / 180.0;
    }

}