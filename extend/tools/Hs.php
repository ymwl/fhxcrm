<?php

namespace tools;
/**
 * 技术 zrwx978
 **/

/**
 * 函数工具类
 */
class Hs
{
//格式化连续时间段
    static function format_lx_time($starttime, $endtime)
    {
        $totalseconds = $endtime - $starttime;
        if ($totalseconds > 86400 * 30 * 2) {
            $format = '%Y-%m';
        } else {
            if ($totalseconds > 86400) {
                $format = '%Y-%m-%d';
            } else {
                $format = '%H:00';
            }
        }
        if ($totalseconds > 84600 * 30 * 2) {
            $starttime = strtotime('last month', $starttime);
            while (($starttime = strtotime('next month', $starttime)) <= $endtime) {
                $column[] = date('Y-m', $starttime);
            }
        } else {
            if ($totalseconds > 86400) {
                for ($time = $starttime; $time <= $endtime;) {
                    $column[] = date("Y-m-d", $time);
                    $time += 86400;
                }
            } else {
                for ($time = $starttime; $time <= $endtime;) {
                    $column[] = date("H:00", $time);
                    $time += 3600;
                }
            }
        }
        return [$format, $column];
    }

    public static function lineToHump($str)
    {
        $str = preg_replace_callback('/([-_]+([a-z]{1}))/i', function ($matches) {
            return strtoupper($matches[2]);
        }, $str);
        return $str;
    }

    /**
     * 驼峰转下划线
     * @param $str
     * @return null|string|string[]
     */
    public static function humpToLine($str)
    {
        $str = preg_replace_callback('/([A-Z]{1})/', function ($matches) {
            return '_' . strtolower($matches[0]);
        }, $str);
        return $str;
    }
    /**
     * 模板值替换
     * @param $string
     * @param $array
     * @return mixed
     */
    public static function replaceTemplate($string, $array)
    {
        foreach ($array as $key => $val) {
            $string = str_replace("{{" . $key . "}}", $val, $string);
        }
        return $string;
    }

    /**
     * 检查系统命令是否存在
     */
    public static function command_exists($cmd) {
        $where = (PHP_OS_FAMILY === 'Windows') ? 'where' : 'which';
        exec("$where $cmd", $output, $return);
        return $return === 0;
    }

    /**
     * 查找命令的完整路径（支持 npm 全局安装和本地 node_modules）
     * @param string $cmd 命令名称
     * @return string|false 返回完整命令路径或 false
     */
    public static function findCommand($cmd) {
        // 1. 先尝试直接使用命令（PATH 中已存在）
        if (self::command_exists($cmd)) {
            return $cmd;
        }

        // 2. 尝试 npx 方式
        if (self::command_exists('npx')) {
            return 'npx ' . $cmd;
        }

        // 3. Windows 下查找 npm 全局目录
        if (PHP_OS_FAMILY === 'Windows') {
            $npmGlobalPaths = [
                getenv('APPDATA') . '\\npm',
                getenv('LOCALAPPDATA') . '\\npm',
                'C:\\Users\\' . getenv('USERNAME') . '\\AppData\\Roaming\\npm',
            ];

            foreach ($npmGlobalPaths as $path) {
                $cmdPath = $path . '\\' . $cmd . '.cmd';
                if (file_exists($cmdPath)) {
                    return '"' . $cmdPath . '"';
                }
                // 尝试不带 .cmd 后缀
                $cmdPath = $path . '\\' . $cmd;
                if (file_exists($cmdPath)) {
                    return '"' . $cmdPath . '"';
                }
            }
        }

        // 4. 查找项目本地 node_modules
        $localPaths = [
            app()->getRootPath() . 'node_modules/.bin/' . $cmd,
            app()->getRootPath() . 'node_modules/.bin/' . $cmd . '.cmd',
        ];

        foreach ($localPaths as $path) {
            if (file_exists($path)) {
                return '"' . $path . '"';
            }
        }

        return false;
    }

    /**
     * 复制文件夹
     * @param string $source 源文件夹
     * @param string $dest   目标文件夹
     */
    public static function copydirs($source, $dest)
    {
        if (!is_dir($dest)) {
            mkdir($dest, 0755, true);
        }
        foreach (
            $iterator = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($source, \RecursiveDirectoryIterator::SKIP_DOTS),
                \RecursiveIteratorIterator::SELF_FIRST
            ) as $item
        ) {
            if ($item->isDir()) {
                $sontDir = $dest . DS . $iterator->getSubPathName();
                if (!is_dir($sontDir)) {
                    mkdir($sontDir, 0755, true);
                }
            } else {
                copy($item, $dest . DS . $iterator->getSubPathName());
            }
        }
    }

    public static function sql($file){
        $sql=file_get_contents($file);
        $prefix=getDataBaseConfig('prefix');
        $sql = str_replace(" `ymwl_", " `{$prefix}", $sql);
        \think\facade\Db::connect('mysql')->getPdo()->exec($sql);
    }

    /**
     * 根据原合同时间计算续签合同的生效时间、到期时间及期限描述
     *
     * @param int $start_time 原合同生效时间戳（例如 strtotime('2026-01-01')）
     * @param int $end_time   原合同到期时间戳（例如 strtotime('2027-12-31')）
     * @return array|null     返回包含 start_date, end_date, duration_label 的数组，无效时返回 null
     */
    public static function calcRenewalDates($start_time=null, $end_time=null)
    {
        // 参数有效性检查
        if (empty($start_time) || empty($end_time) || $end_time < $start_time) {

            return [
                'start_date'     => date('Y-m-d'),
                'end_date'       => '',
                'duration_label' =>''
            ];
        }

        $start = $start_time;
        $end   = $end_time;

        // 续签生效日 = 原到期日 + 1天
        $newStart = strtotime('+1 day', $end);

        // 判断原合同期限：通过 end+1天 是否等于 start + 整数年/月
        $endPlusOne = strtotime('+1 day', $end);

        $years  = 0;
        $months = 0;
        $found  = false;

        // 先尝试整年（最大尝试10年）
        for ($y = 1; $y <= 10; $y++) {
            $test = strtotime("+{$y} year", $start);
            if ($test === $endPlusOne) {
                $years = $y;
                $found = true;
                break;
            }
        }

        // 如果不是整年，尝试整月（最大尝试120个月 = 10年）
        if (!$found) {
            for ($m = 1; $m <= 120; $m++) {
                $test = strtotime("+{$m} month", $start);
                if ($test === $endPlusOne) {
                    $months = $m;
                    $found = true;
                    break;
                }
            }
        }

        if ($found) {
            if ($years > 0) {
                // 续签到期日 = 生效日 + N年 - 1天
                $newEnd = strtotime("+{$years} year -1 day", $newStart);
                $durationLabel = $years . '年';
            } else {
                // 续签到期日 = 生效日 + M个月 - 1天
                $newEnd = strtotime("+{$months} month -1 day", $newStart);
                $durationLabel = $months . '个月';
            }
        } else {
            // 非标准整年/整月，按实际天数计算（保持相同持续天数）
            $diffDays = round(($end - $start) / 86400);
            $newEnd = strtotime("+{$diffDays} day", $newStart);
            $durationLabel = ($diffDays + 1) . '天';
        }

        return [
            'start_date'     => date('Y-m-d', $newStart),
            'end_date'       => date('Y-m-d', $newEnd),
            'duration_label' => $durationLabel,
        ];
    }


}

