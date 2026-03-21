<?php

namespace app\common\model;

use app\admin\library\Auth;
use think\Model;
use think\Loader;

class AdminLog extends Model
{

    // 开启自动写入时间戳字段
    protected $autoWriteTimestamp = 'int';
    // 定义时间戳字段名
    protected $createTime = 'create_time';
    protected $updateTime = '';
    //自定义日志标题
    protected static $title = '';
    //自定义日志内容
    protected static $content = '';
    //忽略的链接正则列表
    protected static $ignoreRegex = [
        '/^(.*)\/(selectpage|index|adminRule)$/i',
    ];

    public static function setTitle($title)
    {
        self::$title = $title;
    }

    public static function setContent($content)
    {
        self::$content = $content;
    }

    public static function setIgnoreRegex($regex = [])
    {
        $regex = is_array($regex) ? $regex : [$regex];
        self::$ignoreRegex = array_merge(self::$ignoreRegex, $regex);
    }

    /**
     * 记录日志
     * @param string $title
     * @param string $content
     */
    public static function record($admin,$path,$hrefId,$title = '', $content = '')
    {
        $realname = empty($admin['realname'])? $admin['username'] : $admin['realname'];

        if (self::$ignoreRegex) {
            foreach (self::$ignoreRegex as $index => $item) {
                if (preg_match($item, $path)) {
                    return;
                }
            }
        }
        $content = $content ? $content : self::$content;
        if (!$content) {
            $content = request()->param('', null, 'trim');
            $content = self::getPureContent($content);
        }
        $title = $title ? $title : self::$title;

        if (!$title) {

            // 不再一次性拿全表，也不用 Tree.php
            $parents = [];
            $id      = $hrefId;
            $visited = [];                 // 用来检测环路
            while ($id > 0) {
                // 环路检测：如果 id 出现过，直接抛异常或 break
                if (isset($visited[$id])) {
                    // 你可以选：
                    // 1. 直接中断：break;
                    // 2. 记录日志后中断
                    // 3. 抛异常让上层捕获
                    \think\facade\Log::error('菜单环路：节点 ' . $id);
                    break;
                }
                $visited[$id] = true;
                $row =  \think\facade\Db::name('auth_rule')
                    ->where('id', $id)
                    ->cache("auth_rule_{$id}", 86400)   // 按需缓存
                    ->field('id,pid,title')
                    ->find();
                if (!$row) {
                    break;
                }
                array_unshift($parents, $row);   // 祖先顺序
                $id = $row['pid'];
            }

            $title = implode(' / ', array_column($parents, 'title'));

        }
        self::create([
            'title'     => $title,
            'content'   => !is_scalar($content) ? json_encode($content, JSON_UNESCAPED_UNICODE) : $content,
            'url'       => substr(request()->url(), 0, 1500),
            'admin_id'  => $admin['admin_id'],
            'realname'  => $realname,
            'useragent' => substr(request()->server('HTTP_USER_AGENT'), 0, 255),
            'ip'        => getRealIp()
        ]);

    }

    /**
     * 获取已屏蔽关键信息的数据
     * @param $content
     * @return false|string
     */
    protected static function getPureContent($content)
    {
        if (!is_array($content)) {
            return $content;
        }
        foreach ($content as $index => &$item) {
            if (preg_match("/(password|salt|token)/i", $index)) {
                $item = "***";
            } else {
                if (is_array($item)) {
                    $item = self::getPureContent($item);
                }
            }
        }
        return $content;
    }

    public function admin()
    {
        return $this->belongsTo('Admin', 'admin_id')->setEagerlyType(0);
    }
}
