<?php
declare (strict_types = 1);

namespace app;

use think\App;
use think\exception\HttpResponseException;
use think\exception\ValidateException;
use think\facade\Db;
use think\Response;
use think\Validate;

/**
 * 控制器基础类
 */
abstract class BaseController
{
    /**
     * Request实例
     * @var \think\Request
     */
    public $request;

    /**
     * 应用实例
     * @var \think\App
     */
    public $app;

    /**
     * 是否批量验证
     * @var bool
     */
    protected $batchValidate = false;

    /**
     * 控制器中间件
     * @var array
     */
    protected $middleware = [];

    /**
     * 构造方法
     * @access public
     * @param  App  $app  应用对象
     */
    public function __construct(App $app)
    {
        $this->app     = $app;
        $this->request = $this->app->request;
        // 控制器初始化
        $this->initialize();
    }

    // 初始化
    protected function initialize()
    {}

    /**
     * 验证数据
     * @access protected
     * @param  array        $data     数据
     * @param  string|array $validate 验证器名或者验证规则数组
     * @param  array        $message  提示信息
     * @param  bool         $batch    是否批量验证
     * @return array|string|true
     * @throws ValidateException
     */
    protected function validate(array $data, $validate, array $message = [], bool $batch = false)
    {
        if (is_array($validate)) {
            $v = new Validate();
            $v->rule($validate);
        } else {
            if (strpos($validate, '.')) {
                // 支持场景
                list($validate, $scene) = explode('.', $validate);
            }
            $class = false !== strpos($validate, '\\') ? $validate : $this->app->parseClass('validate', $validate);
            $v     = new $class();
            if (!empty($scene)) {
                $v->scene($scene);
            }
        }

        $v->message($message);

        // 是否批量验证
        if ($batch || $this->batchValidate) {
            $v->batch(true);
        }

        return $v->failException(true)->check($data);
    }
    public function getGrabCount()
    {
        $more = $this->system;
        $cycleList = \app\admin\model\System::getCycleList();
        if (isset($more['customer_limit_counts']) && $more['customer_limit_counts'] > 0 && isset($cycleList[$more['customer_limit_condition']])) {
            if ($more['customer_limit_condition'] == 'day') {
                $starttime = mktime(0, 0, 0, (int)date('n'), (int)date('j'), (int)date('Y'));
            } elseif ($more['customer_limit_condition'] == 'week') {
                $starttime = mktime(0, 0, 0, (int)date('n'), date('d') - date('w') + 1, (int)date('Y'));
            } else {
                $starttime = mktime(0, 0, 0, (int)date('m'), 1,(int) date('Y'));
            }

            $crmGrabCount = Db::name('crm_grab')->where("`createtime` >= {$starttime} and `admin_id`={$this->admin['admin_id']}")->sum('nums');
            if ($crmGrabCount >= $more['customer_limit_counts']) {
//                return ['code' => false, 'msg' => $cycleList[$more['customer_limit_condition']] .' '. '可领取客户次数已用完'];
                return ['code' => false, 'msg' =>  fy('The number of times customers can be claimed has been used up')];
            } else {
                $shy = $more['customer_limit_counts'] - $crmGrabCount;
                return ['code' => true, 'msg' => $cycleList[$more['customer_limit_condition']] .' '. fy('remaining number of times can be claimed').'：' . $shy,'count'=>$shy];
            }
        } else {
            return ['code' => true, 'msg' => ''];
        }
    }

    /**
     * 操作成功跳转的快捷方法
     * @access protected
     * @param  mixed $msg 提示信息
     * @param  string $url 跳转的URL地址
     * @param  mixed $data 返回的数据
     * @param  integer $wait 跳转等待时间
     * @param  array $header 发送的Header信息
     * @return void
     */
    protected function success($msg = '', string $url = '', $data = '', int $wait = 3, array $header = [])
    {
        if (is_null($url) && isset($_SERVER["HTTP_REFERER"])) {
            $url = $_SERVER["HTTP_REFERER"];
        } elseif ($url) {
            $url = (strpos($url, '://') || 0 === strpos($url, '/')) ? $url : (string)$this->app->route->buildUrl($url);
        }

        $result = [
            'code' => 1,
            'msg' => $msg,
            'data' => $data,
            'url' => $url,
            'wait' => $wait,
        ];

        $type = $this->getResponseType();
        // 把跳转模板的渲染下沉，这样在 response_send 行为里通过getData()获得的数据是一致性的格式
        if ('html' == strtolower($type)) {
            $type = 'view';
            $response = Response::create($this->app->config->get('jump.dispatch_success_tmpl'), $type)->assign($result)->header($header);
        } else {
            $response = Response::create($result, $type)->header($header);
        }

        throw new HttpResponseException($response);
    }

    /**
     * 操作错误跳转的快捷方法
     * @access protected
     * @param  mixed $msg 提示信息
     * @param  string $url 跳转的URL地址
     * @param  mixed $data 返回的数据
     * @param  integer $wait 跳转等待时间
     * @param  array $header 发送的Header信息
     * @return void
     */
    protected function error($msg = '', string $url = '', $data = [], int $wait = 3, array $header = [],$code=0)
    {
        if (is_null($url)) {
            $url = $this->request->isAjax() ? '' : 'javascript:history.back(-1);';
        } elseif ($url) {
            $url = (strpos($url, '://') || 0 === strpos($url, '/')) ? $url : (string)$this->app->route->buildUrl($url);
        }
        if($this->request->isAjax()){
            $data['token']= token();
        }
        $result = [
            'code' => $code,
            'msg' => $msg,
            'data' => $data,
            'url' => $url,
            'wait' => $wait,
        ];

        $type = $this->getResponseType();

        if ('html' == strtolower($type)) {
            $type = 'view';
            $response = Response::create($this->app->config->get('jump.dispatch_error_tmpl'), $type)->assign($result)->header($header);
        } else {
            $response = Response::create($result, $type)->header($header);
        }

        throw new HttpResponseException($response);
    }

    /**
     * 返回封装后的API数据到客户端
     * @access protected
     * @param  mixed $data 要返回的数据
     * @param  integer $code 返回的code
     * @param  mixed $msg 提示信息
     * @param  string $type 返回数据格式
     * @param  array $header 发送的Header信息
     * @return void
     */
    protected function result($data, $code = 0, $msg = '', $type = '', array $header = [])
    {
        $result = [
            'code' => $code,
            'msg' => $msg,
            'time' => time(),
            'data' => $data,
        ];

        $type = $type ?: $this->getResponseType();
        $response = Response::create($result, $type)->header($header);

        throw new HttpResponseException($response);
    }

    /**
     * URL重定向
     * @access protected
     * @param  string $url 跳转的URL表达式
     * @param  integer $code http code
     * @param  array $with 隐式传参
     * @return void
     */
    protected function redirect($url, $code = 302, $with = [])
    {
        $response = Response::create($url, 'redirect');

        $response->code($code)->with($with);

        throw new HttpResponseException($response);
    }

    /**
     * 获取当前的response 输出类型
     * @access protected
     * @return string
     */
    protected function getResponseType()
    {
        return $this->request->isJson() || $this->request->isAjax() ? 'json' : 'html';
    }

    protected function verifyFields($post,$fields,$table){
        $rule=[];
        foreach ($fields as $v){
            $v['rule']=trim($v['rule'],',');
            if($v['rule']){
                $msg=!empty(trim($v['xsname']))?'|'.fy($v['xsname']):'|'.fy($v['name']);
                $ruleKey=$v['field'].$msg;
                // 将 unique 替换为带表名的唯一验证
                $ruleStr = str_replace('unique', 'unique:'.$table, $v['rule']);
                // 仅替换规则间的逗号为竖线，保留 between/in/notin/等参数内的逗号
                // 例如: "require|number|between:1,30" → "require|number|between:1,30"
                // 例如: "require,in:1,2,3" → "require|in:1,2,3"
                $ruleStr = preg_replace('/(?<![a-z\d_:])\s*,\s*(?![a-z\d_:])/i', '|', trim($ruleStr));
                // 如果上述正则未匹配到（旧格式纯逗号分隔），使用安全替换：跳过冒号后的内容
                if (strpos($ruleStr, '|') === false && strpos($ruleStr, ',') !== false) {
                    $parts = explode(',', $ruleStr);
                    $safeParts = [];
                    foreach ($parts as $idx => $part) {
                        // 如果当前部分以包含 : 且上一个部分是 between/in/notin 等，则合并
                        if (preg_match('/^(between|notbetween|in|notin):.+/i', $part) && count($safeParts) > 0) {
                            $last = array_pop($safeParts);
                            $safeParts[] = $last . ',' . $part;
                        } else {
                            $safeParts[] = $part;
                        }
                    }
                    $ruleStr = implode('|', $safeParts);
                }
                $rule[$ruleKey] = $ruleStr;
            }

        }
        if($rule){
            $this->validater($post, $rule);
        }
    }
    /**
     * 重写验证规则
     * @param array $data
     * @param array|string $validate
     * @param array $message
     * @param bool $batch
     * @return array|bool|string|true
     */
    protected function validater(array $data, $validate, array $message = [], bool $batch = false)
    {
        $this->validate($data, $validate, $message, $batch);

        return true;
    }

    protected function param_to_str($params){
        foreach ($params as $k => $v) {
            //数组的字段处理成字符串,主要处理是自定义字段多选的值
            if (!empty($v) && is_array($v)) $params[$k] = implode(',', $v);
        }
        return $params;
    }

}
