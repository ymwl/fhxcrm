<?php
namespace app\admin\model\publicuse;
class HerTime
{
    /**获取今天的开始和结束时间
     * @return array
     */
    public static function today($time=''){
        if(!$time){
            $time = time();
        }
        $start = strtotime(date('Y-m-d',$time).' 00:00:00');
        $end = $start+86399;
        return [$start,$end];
    }

    /**昨日
     * @param string $time
     * @return int[]
     */
    public static function yesterday($time=''){
        if(!$time){
            $time = time();
        }
        $start = strtotime(date('Y-m-d',$time).' 00:00:00')-86400;
        $end = $start+86399;
        return [$start,$end];
    }

    /**获取本周开始和结束时间
     * @return array
     */
    public static function week($time=''){
        if(!$time){
            $time = time();
        }
        //当前日期
        $sdefaultDate = date("Y-m-d",$time);
        //$first =1 表示每周星期一为开始日期 0表示每周日为开始日期
        $first=1;
        //获取当前周的第几天 周日是 0 周一到周六是 1 - 6
        $w=date('w',strtotime($sdefaultDate));
        //获取本周开始日期，如果$w是0，则表示周日，减去 6 天
        $week_start=date('Y-m-d',strtotime("$sdefaultDate -".($w ? $w - $first : 6).' days'));
        //本周结束日期
        $week_end=date('Y-m-d',strtotime("$week_start +6 days"));
        return [strtotime($week_start),strtotime($week_end)+86399];
    }

    /**获取上周时间
     * @param $time
     * @return array|float[]|int[]
     */
    public static function lastWeek($time=''){
       if(!$time){
           $time = time();
       }
       list($start,$end) = self::week($time);
       $start = $start-7*86400;
       $end = $start+7*86400-1;
       return [$start,$end];
    }

    /**获取每月的开始和结束
     * @return array
     */
    public static function month($time=''){
        if(!$time){
            $time = time();
        }
        $date = date('Y-m-',$time)."01 00:00:00";
        $start = strtotime($date);
        $t = date('t',$time);
        $end = $start + $t*86400-1;
        return [$start,$end];
    }

    /**获取上月时间
     * @param string $time
     * @return array
     */
    public static function lastMonth($time=''){
        if(!$time){
            $time = time();
        }
        list($start,$end) = self::month($time);
        $time = $start - 3;
        list($start,$end) = self::month($time);
        return [$start,$end];
    }

    /**今年的开始和结束时间
     * @param string $time
     * @return array
     */
    public static function year($time=''){
        if(!$time){
            $time = time();
        }
        $y = date('Y',$time);
        $start = strtotime($y.'-01-01');
        list($starts,$end) = self::month(strtotime($y.'-12'));
        return [$start,$end];
    }
    public static function getWeek($time='',$type='int'){
        if(!$time){
            $time = time();
        }
        $weekarray = array("一", "二", "三", "四", "五", "六", "日");
        if($type=='int'){
            return date('w',$time);
        }else{
            $da = date('w',$time);
            $ri = '';
            switch ($da){
                case 0:$ri=$weekarray[6];break;
                case 1:$ri=$weekarray[0];break;
                case 2:$ri=$weekarray[1];break;
                case 3:$ri=$weekarray[2];break;
                case 4:$ri=$weekarray[3];break;
                case 5:$ri=$weekarray[4];break;
                case 6:$ri=$weekarray[5];break;
            }
            return  $ri;
        }
    }
}