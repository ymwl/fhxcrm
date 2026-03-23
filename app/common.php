<?php
//缓存
use PhpOffice\PhpSpreadsheet\Reader\Csv;
use PhpOffice\PhpSpreadsheet\Reader\Xls;
use PhpOffice\PhpSpreadsheet\Reader\Xlsx;
use think\facade\Lang;
use think\helper\Str;

use think\exception\HttpResponseException;
function myjson($arr){
    throw new HttpResponseException(json($arr));
}
function savecache($name = '', $id='') {
    if($name=='Field'){
        if($id){
            $Model = \think\facade\Db::name($name);
            $data = $Model->order('sort')->where('moduleid='.$id)->column('*', 'field');
            $name=$id.'_'.$name;
            $data = $data ? $data : null;
            cache($name, $data);
        }else{
            $module = cache('Module');
            foreach ( $module as $key => $val ) {
                savecache($name,$key);
            }
        }
    }elseif($name=='System'){
        $list = \think\facade\Db::name('system_config')->where('status','=',1)->column('value','field');
        cache($name, $list);
        return $list;
    }elseif($name=='Module'){
        $Model = \think\facade\Db::name( $name );
        $list = $Model->order('sort')->select ();
        $pkid = $Model->getPk ();
        $data = array ();
        $smalldata= array();
        foreach ( $list as $key => $val ) {
            $data [$val [$pkid]] = $val;
            $smalldata[$val['name']] =  $val [$pkid];
        }
        cache($name, $data);
        cache('Mod', $smalldata);
    }elseif($name == 'cm'){
        $list = \think\facade\Db::name('category')
            ->alias('c')
            ->join('module m','c.moduleid = m.id')
            ->order('c.sort')
            ->field('c.*,m.title as mtitle,m.name as mname')
            ->select();
        cache($name, $list);
    }else{
        $Model = \think\facade\Db::name($name);
        $list = $Model->order('sort')->select ();
        $pkid = $Model->getPk();
        $data = array ();
        foreach ( $list as $key => $val ) {
            $data [$val [$pkid]] = $val;
        }
        cache($name, $data);
    }
    return true;
}

function getsavecache($name){
    $data=cache($name);
    if($data){
        return $data;
    }else{
       return savecache($name);
    }
}
//缘明网络QQ3623820285提供技术驱动
function myurl(string $path = '', array $vars = [], $suffix = true, $domain = false){
    $url=(string)url($path,$vars,$suffix,$domain);
//    如果插件入口文件不是plugins.php  请替换为真实的入口地址
    $url=str_replace(request()->baseFile().'/plugins/','/plugins.php/',$url);
    return $url;
}

/**
 * Notes:返回附件的真实地址
 * Date: 2022/10/4
 * Blog:http://fhy.laikephp.com
 */
function attrUrl($path){
    if(strpos($path,'/')===0){
        $path=__MY_PUBLIC__.$path;
    }
    return $path;

}
//
function style($title_style){
    $title_style = explode(';',$title_style);
    return  $title_style[0].';'.$title_style[1];
}
//请求返回
function callback($status = 0,$msg = '', $url = null, $data = ''){
    $data = array(
        'msg'=>$msg,
        'url'=>$url,
        'data'=>$data,
        'status'=>$status
    );
    return $data;
}

function getvalidate($info){
    $validate_data=array();
    if($info['minlength']) $validate_data['minlength'] = ' minlength:'.$info['minlength'];
    if($info['maxlength']) $validate_data['maxlength'] = ' maxlength:'.$info['maxlength'];
    if($info['required']) $validate_data['required'] = ' required:true';
    if($info['pattern']) $validate_data['pattern'] = ' '.$info['pattern'].':true';
    $errormsg='';
    if($info['errormsg']){
        $errormsg = ' title="'.$info['errormsg'].'"';
    }
    $validate= implode(',',$validate_data);
    $validate= 'validate="'.$validate.'" ';
    $parseStr = $validate.$errormsg;
    return $parseStr;
}
function string2array($info) {
    if($info == '') return array();
    eval("\$r = $info;");
    return $r;
}
function array2string($info) {
    if($info == '') return '';
    if(!is_array($info)){
        $string = stripslashes($info);
    }
    foreach($info as $key => $val){
        $string[$key] = stripslashes($val);
    }
    $setup = var_export($string, true);
    return $setup;
}
//初始表单
function getform($form,$info,$value=''){
    $type = $info['type'];
    return  $form->$type($info,$value);
}
//文件单位换算
function byte_format($input, $dec=0){
    $prefix_arr = array("B", "KB", "MB", "GB", "TB");
    $value = round($input, $dec);
    $i=0;
    while ($value>1024) {
        $value /= 1024;
        $i++;
    }
    $return_str = round($value, $dec).$prefix_arr[$i];
    return $return_str;
}
//时间日期转换
function toDate($time, $format = 'Y-m-d H:i:s') {
    if (empty ( $time )) {
        return '';
    }
    $format = str_replace ( '#', ':', $format );
    return date($format, $time );
}
//地址id转换名称
function toCity($id){
    if (empty ( $id )) {
        return '';
    }
    $name = \think\facade\Db::name('region')->where(['id'=>$id])->value('name');
    return $name;
}
function template_file($module=''){
    $viewPath = config('template.view_path');
    $viewSuffix = config('template.view_suffix');
    $viewPath = $viewPath ? $viewPath : 'view';
    $filepath = think\facade\Env::get('app_path').strtolower(config('default_module')).'/'.$viewPath.'/';
    $tempfiles = dir_list($filepath,$viewSuffix);
    $arr=[];
    foreach ($tempfiles as $key=>$file){
        $dirname = basename($file);
        if($module){
            if(strstr($dirname,$module.'_')) {
                $arr[$key]['value'] =  substr($dirname,0,strrpos($dirname, '.'));
                $arr[$key]['filename'] = $dirname;
                $arr[$key]['filepath'] = $file;
            }
        }else{
            $arr[$key]['value'] = substr($dirname,0,strrpos($dirname, '.'));
            $arr[$key]['filename'] = $dirname;
            $arr[$key]['filepath'] = $file;
        }
    }
    return  $arr;
}
function dir_list($path, $exts = '', $list= array()) {
    $path = dir_path($path);
    $files = glob($path.'*');
    foreach($files as $v) {
        $fileext = fileext($v);
        if (!$exts || preg_match("/\.($exts)/i", $v)) {
            $list[] = $v;
            if (is_dir($v)) {
                $list = dir_list($v, $exts, $list);
            }
        }
    }
    return $list;
}
function dir_path($path) {
    $path = str_replace('\\', '/', $path);
    if(substr($path, -1) != '/') $path = $path.'/';
    return $path;
}
function fileext($filename) {
    return strtolower(trim(substr(strrchr($filename, '.'), 1, 10)));
}
function checkField($table,$value,$field){
    $count = \think\facade\Db::name($table)->where(array($field=>$value))->count();
    if($count>0){
        return true;
    }else{
        return false;
    }
}
/**
+----------------------------------------------------------
 * 产生随机字串，可用来自动生成密码 默认长度6位 字母和数字混合
+----------------------------------------------------------
 * @param string $len 长度
 * @param string $type 字串类型
 * 0 字母 1 数字 其它 混合
 * @param string $addChars 额外字符
+----------------------------------------------------------
 * @return string
+----------------------------------------------------------
 */
function rand_string($len=6,$type='',$addChars='') {
    $str ='';
    switch($type) {
        case 0:
            $chars='ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz'.$addChars;
            break;
        case 1:
            $chars= str_repeat('0123456789',3);
            break;
        case 2:
            $chars='ABCDEFGHIJKLMNOPQRSTUVWXYZ'.$addChars;
            break;
        case 3:
            $chars='abcdefghijklmnopqrstuvwxyz'.$addChars;
            break;
        case 4:
            $chars = "们以我到他会作时要动国产的一是工就年阶义发成部民可出能方进在了不和有大这主中人上为来分生对于学下级地个用同行面说种过命度革而多子后自社加小机也经力线本电高量长党得实家定深法表着水理化争现所二起政三好十战无农使性前等反体合斗路图把结第里正新开论之物从当两些还天资事队批点育重其思与间内去因件日利相由压员气业代全组数果期导平各基或月毛然如应形想制心样干都向变关问比展那它最及外没看治提五解系林者米群头意只明四道马认次文通但条较克又公孔领军流入接席位情运器并飞原油放立题质指建区验活众很教决特此常石强极土少已根共直团统式转别造切九你取西持总料连任志观调七么山程百报更见必真保热委手改管处己将修支识病象几先老光专什六型具示复安带每东增则完风回南广劳轮科北打积车计给节做务被整联步类集号列温装即毫知轴研单色坚据速防史拉世设达尔场织历花受求传口断况采精金界品判参层止边清至万确究书术状厂须离再目海交权且儿青才证低越际八试规斯近注办布门铁需走议县兵固除般引齿千胜细影济白格效置推空配刀叶率述今选养德话查差半敌始片施响收华觉备名红续均药标记难存测士身紧液派准斤角降维板许破述技消底床田势端感往神便贺村构照容非搞亚磨族火段算适讲按值美态黄易彪服早班麦削信排台声该击素张密害侯草何树肥继右属市严径螺检左页抗苏显苦英快称坏移约巴材省黑武培著河帝仅针怎植京助升王眼她抓含苗副杂普谈围食射源例致酸旧却充足短划剂宣环落首尺波承粉践府鱼随考刻靠够满夫失包住促枝局菌杆周护岩师举曲春元超负砂封换太模贫减阳扬江析亩木言球朝医校古呢稻宋听唯输滑站另卫字鼓刚写刘微略范供阿块某功套友限项余倒卷创律雨让骨远帮初皮播优占死毒圈伟季训控激找叫云互跟裂粮粒母练塞钢顶策双留误础吸阻故寸盾晚丝女散焊功株亲院冷彻弹错散商视艺灭版烈零室轻血倍缺厘泵察绝富城冲喷壤简否柱李望盘磁雄似困巩益洲脱投送奴侧润盖挥距触星松送获兴独官混纪依未突架宽冬章湿偏纹吃执阀矿寨责熟稳夺硬价努翻奇甲预职评读背协损棉侵灰虽矛厚罗泥辟告卵箱掌氧恩爱停曾溶营终纲孟钱待尽俄缩沙退陈讨奋械载胞幼哪剥迫旋征槽倒握担仍呀鲜吧卡粗介钻逐弱脚怕盐末阴丰雾冠丙街莱贝辐肠付吉渗瑞惊顿挤秒悬姆烂森糖圣凹陶词迟蚕亿矩康遵牧遭幅园腔订香肉弟屋敏恢忘编印蜂急拿扩伤飞露核缘游振操央伍域甚迅辉异序免纸夜乡久隶缸夹念兰映沟乙吗儒杀汽磷艰晶插埃燃欢铁补咱芽永瓦倾阵碳演威附牙芽永瓦斜灌欧献顺猪洋腐请透司危括脉宜笑若尾束壮暴企菜穗楚汉愈绿拖牛份染既秋遍锻玉夏疗尖殖井费州访吹荣铜沿替滚客召旱悟刺脑措贯藏敢令隙炉壳硫煤迎铸粘探临薄旬善福纵择礼愿伏残雷延烟句纯渐耕跑泽慢栽鲁赤繁境潮横掉锥希池败船假亮谓托伙哲怀割摆贡呈劲财仪沉炼麻罪祖息车穿货销齐鼠抽画饲龙库守筑房歌寒喜哥洗蚀废纳腹乎录镜妇恶脂庄擦险赞钟摇典柄辩竹谷卖乱虚桥奥伯赶垂途额壁网截野遗静谋弄挂课镇妄盛耐援扎虑键归符庆聚绕摩忙舞遇索顾胶羊湖钉仁音迹碎伸灯避泛亡答勇频皇柳哈揭甘诺概宪浓岛袭谁洪谢炮浇斑讯懂灵蛋闭孩释乳巨徒私银伊景坦累匀霉杜乐勒隔弯绩招绍胡呼痛峰零柴簧午跳居尚丁秦稍追梁折耗碱殊岗挖氏刃剧堆赫荷胸衡勤膜篇登驻案刊秧缓凸役剪川雪链渔啦脸户洛孢勃盟买杨宗焦赛旗滤硅炭股坐蒸凝竟陷枪黎救冒暗洞犯筒您宋弧爆谬涂味津臂障褐陆啊健尊豆拔莫抵桑坡缝警挑污冰柬嘴啥饭塑寄赵喊垫丹渡耳刨虎笔稀昆浪萨茶滴浅拥穴覆伦娘吨浸袖珠雌妈紫戏塔锤震岁貌洁剖牢锋疑霸闪埔猛诉刷狠忽灾闹乔唐漏闻沈熔氯荒茎男凡抢像浆旁玻亦忠唱蒙予纷捕锁尤乘乌智淡允叛畜俘摸锈扫毕璃宝芯爷鉴秘净蒋钙肩腾枯抛轨堂拌爸循诱祝励肯酒绳穷塘燥泡袋朗喂铝软渠颗惯贸粪综墙趋彼届墨碍启逆卸航衣孙龄岭骗休借".$addChars;
            break;
        default :
            // 默认去掉了容易混淆的字符oOLl和数字01，要添加请使用addChars参数
            $chars='ABCDEFGHIJKMNPQRSTUVWXYZabcdefghijkmnpqrstuvwxyz23456789'.$addChars;
            break;
    }
    if($len>10 ) {//位数过长重复字符串一定次数
        $chars= $type==1? str_repeat($chars,$len) : str_repeat($chars,5);
    }
    if($type!=4) {
        $chars   =   str_shuffle($chars);
        $str     =   substr($chars,0,$len);
    }else{
        // 中文随机字
        for($i=0;$i<$len;$i++){
            $str.= msubstr($chars, floor(mt_rand(0,mb_strlen($chars,'utf-8')-1)),1);
        }
    }
    return $str;
}

/**
 * 验证输入的邮件地址是否合法
 */
function is_email($user_email)
{
    $chars = "/^([a-z0-9+_]|\\-|\\.)+@(([a-z0-9_]|\\-)+\\.)+[a-z]{2,6}\$/i";
    if (strpos($user_email, '@') !== false && strpos($user_email, '.') !== false) {
        if (preg_match($chars, $user_email)) {
            return true;
        } else {
            return false;
        }
    } else {
        return false;
    }
}

/**
 * 验证输入的手机号码是否合法
 */
function is_mobile_phone($mobile_phone)
{
    $chars = "/^13[0-9]{1}[0-9]{8}$|15[0-9]{1}[0-9]{8}$|18[0-9]{1}[0-9]{8}$|17[0-9]{1}[0-9]{8}$/";
    if (preg_match($chars, $mobile_phone)) {
        return true;
    }
    return false;
}
/**
 * 获取真实IP
 * @return mixed
 */
function getRealIp()
{
    static $ip = null;
    if (null !== $ip) {
        return $ip;
    }
    $ip = $_SERVER['REMOTE_ADDR'];
    if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $arr = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
        $pos = array_search('unknown', $arr);
        if (false !== $pos) {
            unset($arr[$pos]);
        }
        $ip = trim(current($arr));
    } elseif (isset($_SERVER['HTTP_CLIENT_IP'])) {
        $ip = $_SERVER['HTTP_CLIENT_IP'];
    } elseif (isset($_SERVER['HTTP_CF_CONNECTING_IP'])) {
        $ip = $_SERVER['HTTP_CF_CONNECTING_IP'];
    } elseif (isset($_SERVER['HTTP_X_REAL_IP'])) {
        $ip = $_SERVER['HTTP_X_REAL_IP'];
    }
    return $ip;
}

/**
 * 获取 CDN 后的真实客户端 IP
 * 优先级：CDN 专用头 > X-Forwarded-For 首段 > X-Real-IP > REMOTE_ADDR
 */
function getRealClientIp()
{
    $ipHeaders = [
        'Ali-CDN-Real-IP',      // 阿里云
        'TX-Client-IP',         // 腾讯云
        'CF-Connecting-IP',     // Cloudflare
        'X-Real-IP',            // 又拍云/Nginx
        'Client-IP',            // 百度云
        'X-Forwarded-For',      // 通用（需取首段）
    ];

    foreach ($ipHeaders as $header) {
        if (!empty($_SERVER['HTTP_' . strtoupper(str_replace('-', '_', $header))])) {
            $ip = $_SERVER['HTTP_' . strtoupper(str_replace('-', '_', $header))];

            // X-Forwarded-For 可能是逗号分隔的链，取第一个（最近代理）
            if ($header === 'X-Forwarded-For') {
                $ip = trim(explode(',', $ip)[0]);
            }

            // 验证 IP 格式
            if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                return $ip;
            }
        }
    }

    // 兜底：直接连接 IP（无 CDN 时）
    return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
}

//字符串截取
function str_cut($sourcestr,$cutlength,$suffix='...')
{
    $returnstr='';
    $i=0;
    $n=0;
    $str_length=strlen($sourcestr);//字符串的字节数
    while (($n<$cutlength) and ($i<=$str_length))
    {
        $temp_str=substr($sourcestr,$i,1);
        $ascnum=Ord($temp_str);//得到字符串中第$i位字符的ascii码
        if ($ascnum>=224)    //如果ASCII位高与224，
        {
            $returnstr=$returnstr.substr($sourcestr,$i,3); //根据UTF-8编码规范，将3个连续的字符计为单个字符
            $i=$i+3;            //实际Byte计为3
            $n++;            //字串长度计1
        }
        elseif ($ascnum>=192) //如果ASCII位高与192，
        {
            $returnstr=$returnstr.substr($sourcestr,$i,2); //根据UTF-8编码规范，将2个连续的字符计为单个字符
            $i=$i+2;            //实际Byte计为2
            $n++;            //字串长度计1
        }
        elseif ($ascnum>=65 && $ascnum<=90) //如果是大写字母，
        {
            $returnstr=$returnstr.substr($sourcestr,$i,1);
            $i=$i+1;            //实际的Byte数仍计1个
            $n++;            //但考虑整体美观，大写字母计成一个高位字符
        }
        else                //其他情况下，包括小写字母和半角标点符号，
        {
            $returnstr=$returnstr.substr($sourcestr,$i,1);
            $i=$i+1;            //实际的Byte数计1个
            $n=$n+0.5;        //小写字母和半角标点等与半个高位字符宽...
        }
    }
    if ($n>$cutlength){
        $returnstr = $returnstr . $suffix;//超过长度时在尾处加上省略号
    }
    return $returnstr;
}
//删除目录及文件
function dir_delete($dir) {
    $dir = dir_path($dir);
    if (!is_dir($dir)) return false;
    $list = glob($dir.'*');
    foreach($list as $v) {
        is_dir($v) ? dir_delete($v) : @unlink($v);
    }
    return @rmdir($dir);
}
/**
 * CURL请求
 * @param $url 请求url地址
 * @param $method 请求方法 get post
 * @param null $postfields post数据数组
 * @param array $headers 请求header信息
 * @param bool|false $debug  调试开启 默认false
 * @return mixed
 */
function httpRequest($url, $method, $postfields = null, $headers = array(), $debug = false) {
    $method = strtoupper($method);
    $ci = curl_init();
    /* Curl settings */
    curl_setopt($ci, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_0);
    curl_setopt($ci, CURLOPT_USERAGENT, "Mozilla/5.0 (Windows NT 6.2; WOW64; rv:34.0) Gecko/20100101 Firefox/34.0");
    curl_setopt($ci, CURLOPT_CONNECTTIMEOUT, 60); /* 在发起连接前等待的时间，如果设置为0，则无限等待 */
    curl_setopt($ci, CURLOPT_TIMEOUT, 7); /* 设置cURL允许执行的最长秒数 */
    curl_setopt($ci, CURLOPT_RETURNTRANSFER, true);
    switch ($method) {
        case "POST":
            curl_setopt($ci, CURLOPT_POST, true);
            if (!empty($postfields)) {
                $tmpdatastr = is_array($postfields) ? http_build_query($postfields) : $postfields;
                curl_setopt($ci, CURLOPT_POSTFIELDS, $tmpdatastr);
            }
            break;
        default:
            curl_setopt($ci, CURLOPT_CUSTOMREQUEST, $method); /* //设置请求方式 */
            break;
    }
    $ssl = preg_match('/^https:\/\//i',$url) ? true : false;
    curl_setopt($ci, CURLOPT_URL, $url);
    if($ssl){
        curl_setopt($ci, CURLOPT_SSL_VERIFYPEER, false); // https请求 不验证证书和hosts
        curl_setopt($ci, CURLOPT_SSL_VERIFYHOST, false); // 不从证书中检查SSL加密算法是否存在
    }
    //curl_setopt($ci, CURLOPT_HEADER, true); /*启用时会将头文件的信息作为数据流输出*/
    //curl_setopt($ci, CURLOPT_FOLLOWLOCATION, 1);
    curl_setopt($ci, CURLOPT_MAXREDIRS, 2);/*指定最多的HTTP重定向的数量，这个选项是和CURLOPT_FOLLOWLOCATION一起使用的*/
    curl_setopt($ci, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ci, CURLINFO_HEADER_OUT, true);
    /*curl_setopt($ci, CURLOPT_COOKIE, $Cookiestr); * *COOKIE带过去** */
    $response = curl_exec($ci);
    $requestinfo = curl_getinfo($ci);
    $http_code = curl_getinfo($ci, CURLINFO_HTTP_CODE);
    if ($debug) {
        echo "=====post data======\r\n";
        var_dump($postfields);
        echo "=====info===== \r\n";
        print_r($requestinfo);
        echo "=====response=====\r\n";
        print_r($response);
    }
    curl_close($ci);
    return $response;
    //return array($http_code, $response,$requestinfo);
}
/**
 * @param $arr
 * @param $key_name
 * @return array
 * 将数据库中查出的列表以指定的 id 作为数组的键名
 */
function convert_arr_key($arr, $key_name)
{
    $arr2 = array();
    foreach($arr as $key => $val){
        $arr2[$val[$key_name]] = $val;
    }
    return $arr2;
}
//查询IP地址
function getCity($ip = ''){

    $res = @file_get_contents('http://int.dpool.sina.com.cn/iplookup/iplookup.php?format=js&ip=' . $ip);
    if(empty($res)){ return false; }
    $jsonMatches = array();
    preg_match('#\{.+?\}#', $res, $jsonMatches);
    if(!isset($jsonMatches[0])){ return false; }
    $json = json_decode($jsonMatches[0], true);
    if(isset($json['ret']) && $json['ret'] == 1){
        $json['ip'] = $ip;
        unset($json['ret']);
    }else{
        return false;
    }
    return $json;
}
//判断图片的类型从而设置图片路径

/**
 * PHP格式化字节大小
 * @param  number $size      字节数
 * @param  string $delimiter 数字和单位分隔符
 * @return string            格式化后的带单位的大小
 */
function format_bytes($size, $delimiter = '') {
    $units = array('B', 'KB', 'MB', 'GB', 'TB', 'PB');
    for ($i = 0; $size >= 1024 && $i < 5; $i++) $size /= 1024;
    return round($size, 2) . $delimiter . $units[$i];
}

function is_weixin() {
    if (strpos($_SERVER['HTTP_USER_AGENT'], 'MicroMessenger') !== false) {
        return true;
    } return false;
}

function is_qq() {
    if (strpos($_SERVER['HTTP_USER_AGENT'], 'QQ') !== false) {
        return true;
    } return false;
}
function is_alipay() {
    if (strpos($_SERVER['HTTP_USER_AGENT'], 'AlipayClient') !== false) {
        return true;
    } return false;
}

/**
 * 获取用户信息
 * @param $user_id_or_name  用户id 邮箱 手机 第三方id
 * @param int $type  类型 0 user_id查找 1 邮箱查找 2 手机查找 3 第三方唯一标识查找
 * @param string $oauth  第三方来源
 * @return mixed
 */
function get_user_info($user_id_or_name,$type = 0,$oauth=''){
    $map = array();
    if($type == 0){
        $map[] = ['user_id','=',$user_id_or_name];
    }
    if($type == 1){
        $map[] = ['email','=',$user_id_or_name];
    }
    if($type == 2){
        $map[] = ['mobile','=',$user_id_or_name];
    }
    if($type == 3){
        $map[] = ['openid','=',$user_id_or_name];
        $map[] = ['oauth','=',$oauth];
    }
    if($type == 4){
        $map[] = ['unionid','=',$user_id_or_name];
        $map[] = ['oauth','=',$oauth];
    }
    if($type == 5){
        $map[] = ['nickname','=',$user_id_or_name];
    }
    $user = \think\facade\Db::name('users')->where($map)->find();
    return $user;
}
/**
 * 过滤数组元素前后空格 (支持多维数组)
 * @param $array 要过滤的数组
 * @return array|string
 */
function trim_array_element($array){
    if(!is_array($array))
        return trim($array);
    return array_map('trim_array_element',$array);
}
/**
 * @param $arr
 * @param $key_name
 * @return array
 * 将数据库中查出的列表以指定的 值作为数组的键名，并以另一个值作为键值
 */
function convert_arr_kv($arr,$key_name,$value){
    $arr2 = array();
    foreach($arr as $key => $val){
        $arr2[$val[$key_name]] = $val[$value];
    }
    return $arr2;
}

/**
 * 邮件发送
 * @param $to    接收人
 * @param string $subject   邮件标题
 * @param string $content   邮件内容(html模板渲染后的内容)
 * @throws Exception
 * @throws phpmailerException
 */
function send_email($to,$subject='',$content='',$exceptions=true,$record=0,$type=''){
    $mail = new \PHPMailer\PHPMailer\PHPMailer($exceptions);
    $admin=session('admin');
    try {

        $arr = \think\facade\Db::name('config')->where('inc_type','smtp')->select();
        $config = convert_arr_kv($arr,'name','value');

        $mail->CharSet  = 'UTF-8'; //设定邮件编码，默认ISO-8859-1，如果发中文此项必须设置，否则乱码
        $mail->isSMTP();
        //    $mail->SMTPDebug = 4;
        //调试输出格式
        //$mail->Debugoutput = 'html';
        //smtp服务器
        $mail->Host = $config['smtp_server'];
        //端口 - likely to be 25, 465 or 587
        $mail->Port = $config['smtp_port'];

        if($mail->Port == '465') {
            $mail->SMTPSecure = 'ssl';
        }// 使用安全协议
        //Whether to use SMTP authentication
        $mail->SMTPAuth = true;
        //发送邮箱
        $mail->Username = $config['smtp_user'];
        //密码
        $mail->Password = $config['smtp_pwd'];
        //Set who the message is to be sent from
        $mail->setFrom($config['smtp_user'],$config['email_id']);
        //回复地址
        //$mail->addReplyTo('replyto@example.com', 'First Last');
        //接收邮件方
        if(is_array($to)){
            foreach ($to as $v){
                $mail->addAddress($v);
            }
        }else{
            $mail->addAddress($to);
        }

        $mail->isHTML(true);// send as HTML
        //标题
        $mail->Subject = $subject;
        //HTML内容转换
        $mail->msgHTML($content);
        $res=$mail->send();

        if($record){

            if(is_alipay($to)){
                $email=implode(',',$to);
            }else{
                $email=$to;
            }
            $ins=[
                'email'=>$email,
                'type'=>$type,
                'create_admin_id'=>$admin['admin_id'],
                'create_username'=>$admin['username'],
                'title'=>$subject,
                'content'=>$content,
                'status'=>'success',
                'notes'=>$res=='1'?'':$res,
                'create_time'=>time(),
            ];
            \think\facade\Db::name('email_log')->insert($ins);
        }
        return $res;
    } catch (Exception $e) {
        if($record){
            if(is_alipay($to)){
                $email=implode(',',$to);
            }else{
                $email=$to;
            }
            $ins=[
                'email'=>$email,
                'type'=>$type,
                'create_admin_id'=>$admin['admin_id'],
                'create_username'=>$admin['username'],
                'title'=>$subject,
                'content'=>$content,
                'status'=>'fail',
                'notes'=>$mail->ErrorInfo,
                'create_time'=>time(),
            ];
            \think\facade\Db::name('email_log')->insert($ins);
        }
        throw new \Exception($mail->ErrorInfo, 101);
    }
}
function safe_html($html){
    $elements = [
        'html'      =>  [],
        'body'      =>  [],
        'a'         =>  ['target', 'href', 'title', 'class', 'style'],
        'abbr'      =>  ['title', 'class', 'style'],
        'address'   =>  ['class', 'style'],
        'area'      =>  ['shape', 'coords', 'href', 'alt'],
        'article'   =>  [],
        'aside'     =>  [],
        'audio'     =>  ['autoplay', 'controls', 'loop', 'preload', 'src', 'class', 'style'],
        'b'         =>  ['class', 'style'],
        'bdi'       =>  ['dir'],
        'bdo'       =>  ['dir'],
        'big'       =>  [],
        'blockquote'=>  ['cite', 'class', 'style'],
        'br'        =>  [],
        'caption'   =>  ['class', 'style'],
        'center'    =>  [],
        'cite'      =>  [],
        'code'      =>  ['class', 'style'],
        'col'       =>  ['align', 'valign', 'span', 'width', 'class', 'style'],
        'colgroup'  =>  ['align', 'valign', 'span', 'width', 'class', 'style'],
        'dd'        =>  ['class', 'style'],
        'del'       =>  ['datetime'],
        'details'   =>  ['open'],
        'div'       =>  ['class', 'style'],
        'dl'        =>  ['class', 'style'],
        'dt'        =>  ['class', 'style'],
        'em'        =>  ['class', 'style'],
        'font'      =>  ['color', 'size', 'face'],
        'footer'    =>  [],
        'h1'        =>  ['class', 'style'],
        'h2'        =>  ['class', 'style'],
        'h3'        =>  ['class', 'style'],
        'h4'        =>  ['class', 'style'],
        'h5'        =>  ['class', 'style'],
        'h6'        =>  ['class', 'style'],
        'header'    =>  [],
        'hr'        =>  [],
        'i'         =>  ['class', 'style'],
        'img'       =>  ['src', 'alt', 'title', 'width', 'height', 'id', 'class'],
        'ins'       =>  ['datetime'],
        'li'        =>  ['class', 'style'],
        'mark'      =>  [],
        'nav'       =>  [],
        'ol'        =>  ['class', 'style'],
        'p'         =>  ['class', 'style'],
        'pre'       =>  ['class', 'style'],
        's'         =>  [],
        'section'   =>  [],
        'small'     =>  [],
        'span'      =>  ['class', 'style'],
        'sub'       =>  ['class', 'style'],
        'sup'       =>  ['class', 'style'],
        'strong'    =>  ['class', 'style'],
        'table'     =>  ['width', 'border', 'align', 'valign', 'class', 'style'],
        'tbody'     =>  ['align', 'valign', 'class', 'style'],
        'td'        =>  ['width', 'rowspan', 'colspan', 'align', 'valign', 'class', 'style'],
        'tfoot'     =>  ['align', 'valign', 'class', 'style'],
        'th'        =>  ['width', 'rowspan', 'colspan', 'align', 'valign', 'class', 'style'],
        'thead'     =>  ['align', 'valign', 'class', 'style'],
        'tr'        =>  ['rowspan', 'align', 'valign', 'class', 'style'],
        'tt'        =>  [],
        'u'         =>  [],
        'ul'        =>  ['class', 'style'],
        'video'     =>  ['autoplay', 'controls', 'loop', 'preload', 'src', 'height', 'width', 'class', 'style'],
        'embed'     =>  ['src', 'height','align', 'width', 'class', 'style','type','pluginspage','wmode','play','loop','menu','allowscriptaccess','allowfullscreen'],
        'source'    =>  ['src', 'type']
    ];
    $html = strip_tags($html,'<'.implode('><', array_keys($elements)).'>');
    $xml = new \DOMDocument();
    libxml_use_internal_errors(true);
    if (!strlen($html)){
        return '';
    }
    if ($xml->loadHTML('<meta http-equiv="Content-Type" content="text/html; charset=utf-8">' . $html)){
        foreach ($xml->getElementsByTagName("*") as $element){
            if (!isset($elements[$element->tagName])){
                $element->parentNode->removeChild($element);
            }else{
                for ($k = $element->attributes->length - 1; $k >= 0; --$k) {
                    if (!in_array($element->attributes->item($k) -> nodeName, $elements[$element->tagName])){
                        $element->removeAttributeNode($element->attributes->item($k));
                    }elseif (in_array($element->attributes->item($k) -> nodeName, ['href','src','style','background','size'])) {
                        $_keywords = ['javascript:','javascript.:','vbscript:','vbscript.:',':expression'];
                        $find = false;
                        foreach ($_keywords as $a => $b) {
                            if (false !== strpos(strtolower($element->attributes->item($k)->nodeValue),$b)) {
                                $find = true;
                            }
                        }
                        if ($find) {
                            $element->removeAttributeNode($element->attributes->item($k));
                        }
                    }
                }
            }
        }
    }
    $html = substr($xml->saveHTML($xml->documentElement), 12, -14);
    $html = strip_tags($html,'<'.implode('><', array_keys($elements)).'>');
    return $html;
}


function get_server_ip(){
    if(isset($_SERVER)){
        if(array_key_exists("SERVER_ADDR",$_SERVER)){//改这里
            $server_ip=$_SERVER['SERVER_ADDR'];
        }else{
            $server_ip=$_SERVER['LOCAL_ADDR'];
        }
    }else{
        $server_ip = getenv('SERVER_ADDR');
    }
    return $server_ip;
}
//后台鉴权是否有访问权限
function auth($uri){
    if(!$uri)return 1;
    //当前管理员权限
    $admin=session('admin');

//    authopen
    $auth_rule_id = \think\facade\Db::name('auth_rule')->cache('auth_rule_'.$uri.'_'.$admin['admin_id'])->where(['href'=>$uri,'authopen'=>1])->value('id');
    if(!$auth_rule_id)return 1;
    $prefix = getDataBaseConfig('prefix');

    $map['a.admin_id'] = $admin['admin_id'];
    $rules=\think\facade\Db::name('admin')->alias('a')->field('a.group_id,ag.rules')->cache($uri.'_'.$admin['admin_id'])
        ->join($prefix.'auth_group ag','a.group_id = ag.id','left')
        ->where($map)
        ->find();
    if($rules['group_id']==1)return 1;
    $adminRules = explode(',',$rules['rules']);
    if(!in_array($auth_rule_id,$adminRules)){
//            说明无权限访问
        return 0;
    }else{
        return 1;
    }
}

//返回某个方法对应有权限的用户组
function actiongroup($action){
    if(empty($action))return '';
    $group_ids='';
    $rule_id=\think\facade\Db::name('auth_rule')->where('href',$action)->value('id');
    if($rule_id){
        $group_ids=\think\facade\Db::name('auth_group')->field('group_concat(`id`) as group_ids')->whereRaw('FIND_IN_SET(:rules,`rules`) OR id=1',['rules'=>$rule_id])->find();

//        管理员组永远有审核权限
        if(!empty($group_ids['group_ids'])){
            $group_ids=$group_ids['group_ids'];
        }else{
            $group_ids='1';
        }
    }
    return $group_ids;
}

if (!function_exists('rmdirs')) {

    /**
     * 删除文件夹
     * @param string $dirname  目录
     * @param bool   $withself 是否删除自身
     * @return boolean
     */
    function rmdirs($dirname, $withself = true)
    {
        if (!is_dir($dirname)) {
            return false;
        }
        $files = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($dirname, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST
        );

        foreach ($files as $fileinfo) {
            $todo = ($fileinfo->isDir() ? 'rmdir' : 'unlink');
            $todo($fileinfo->getRealPath());
        }
        if ($withself) {
            @rmdir($dirname);
        }
        return true;
    }
}

function real_field_val($field,$value,$tpl=0){

    if($field['formtype']==='datetime'){
        if(trim($value)=='0'){
            $value='';
        }elseif(is_numeric($value)){
            $value=date('Y-m-d H:i:s',$value);
        }
    }elseif($field['formtype']==='date'){
        if(trim($value)=='0'){
            $value='';
        }elseif(is_numeric($value)){
            $value=date('Y-m-d',$value);
        }
    }elseif($field['formtype']==='file' || $field['formtype']==='image'){
        if($tpl && !empty($value)){
//            public/static/admin/images/upload-icons/doc.png
//            获取文件的后缀名
            $ext=strtolower(pathinfo($value, PATHINFO_EXTENSION));
            if(in_array($ext,['jpg','jpeg','png','gif'])){
                $value='<img src="'.attrUrl($value).'" data-image>';
            }else{
                if(file_exists(app()->getRootPath().'public'.'/static/admin/images/upload-icons/'.$ext.'.png')){
                    $icons=__MY_PUBLIC__.'/static/admin/images/upload-icons/'.$ext.'.png';

                }else{
                    $icons=__MY_PUBLIC__.'/static/admin/images/upload-icons/file.png';
                }
                $value='<a href="'.attrUrl($value).'" target="_blank" download><img src="'.$icons.'"></a>';
            }
        }
    }elseif($field['formtype']==='files' || $field['formtype']==='images'){
        if($tpl && !empty($value)){
            $value_arr=explode('|',$value);
            $temp='';
            foreach ($value_arr as $k=>$v){
                $ext=strtolower(pathinfo($v, PATHINFO_EXTENSION));
                if(in_array($ext,['jpg','jpeg','png','gif'])){
                    $temp.='<img src="'.attrUrl($v).'" data-image>';
                }else{
                    if(file_exists(app()->getRootPath().'public'.'/static/admin/images/upload-icons/'.$ext.'.png')){
                        $icons=__MY_PUBLIC__.'/static/admin/images/upload-icons/'.$ext.'.png';

                    }else{
                        $icons=__MY_PUBLIC__.'/static/admin/images/upload-icons/file.png';
                    }
                    $temp.='<a href="'.attrUrl($v).'" target="_blank" download><img src="'.$icons.'"></a>';
                }
            }
            $value=$temp;
        }
    }elseif(($field['formtype']==='select' || $field['formtype']==='radio') && is_numeric($value) && !empty(trim($field['option']))){
        $option=explode(',',$field['option']);
        $selectList=[];
        foreach ($option as $v){
            $vv=explode(':',$v);
            if($vv){
                $selectList[trim($vv[0])]=isset($vv[1])?trim($vv[1]):trim($vv[0]);
            }
        }
        if(isset($selectList[$value])){
            $value= $selectList[$value];
        }
    }
    return $value;
}



if (! function_exists('parseName')) {
    function parseName($name, $type = 0, $ucfirst = true)
    {
        if ($type) {
            $name = preg_replace_callback('/_([a-zA-Z])/', function ($match) {
                return strtoupper($match[1]);
            }, $name);

            return $ucfirst ? ucfirst($name) : lcfirst($name);
        }

        return strtolower(trim(preg_replace('/[A-Z]/', '_\\0', $name), '_'));
    }
}
if (! function_exists('__')) {

    /**
     * 获取语言变量值
     *
     * @param  string  $name  语言变量名
     * @param  array  $vars  动态变量值
     * @param  string  $lang  语言
     *
     * @return mixed
     */
    function __($name, $vars = [], $lang = '')
    {
        if (is_numeric($name) || ! $name) {
            return $name;
        }
        if (! is_array($vars)) {
            $vars = func_get_args();
            array_shift($vars);
            $lang = '';
        }

        return Lang::get($name, $vars, $lang);
    }
}

if (! function_exists('mb_ucfirst')) {
    function mb_ucfirst($string)
    {
        return mb_strtoupper(mb_substr($string, 0, 1)).mb_strtolower(mb_substr($string, 1));
    }
}

//获取数据库表前缀
function getDataBaseConfig($name=''){
    $default=config('database.default');
    if($name){
        return config('database.connections.'.$default.'.'.$name);
    }else{
        return config('database.connections.'.$default);
    }
}



function post_convert($post,$fields){
//    对提交的数据类型进行转换
    foreach ($fields as $v){
        if($v['formtype']=='checkbox' || $v['formtype']=='lcheckbox'){
            //            针对多选无值赋空
            if(!isset($post[$v['field']])){
                $post[$v['field']]='';
            }
        }elseif($v['formtype']=='datetime' || $v['formtype']=='month' || $v['formtype']=='date'){
            if(isset($post[$v['field']])){
                $post[$v['field']]=strtotime($post[$v['field']]);
            }
            if(empty($post[$v['field']])){
                $post[$v['field']]=null;
            }
        }
    }
    return $post;
}

//自定义的date函数
function mydate($f,$t){
    if(!$t)return '';
    return date($f,$t);
}

function fy($str,$vars=[]){
    return lang($str,$vars);
}

//加密函数，可用cpDecode()函数解密，$data：待加密的字符串或数组；$key：密钥；$expire 过期时间
function cpEncode($data,$key='',$expire = 0){
    $string=serialize($data);
    $ckey_length = 4;
    $key = md5($key);
    $keya = md5(substr($key, 0, 16));
    $keyb = md5(substr($key, 16, 16));
    $keyc = substr(md5(microtime()), -$ckey_length);

    $cryptkey = $keya.md5($keya.$keyc);
    $key_length = strlen($cryptkey);

    $string =  sprintf('%010d', $expire ? $expire + time() : 0).substr(md5($string.$keyb), 0, 16).$string;
    $string_length = strlen($string);
    $result = '';
    $box = range(0, 255);

    $rndkey = array();
    for($i = 0; $i <= 255; $i++) {
        $rndkey[$i] = ord($cryptkey[$i % $key_length]);
    }

    for($j = $i = 0; $i < 256; $i++) {
        $j = ($j + $box[$i] + $rndkey[$i]) % 256;
        $tmp = $box[$i];
        $box[$i] = $box[$j];
        $box[$j] = $tmp;
    }

    for($a = $j = $i = 0; $i < $string_length; $i++) {
        $a = ($a + 1) % 256;
        $j = ($j + $box[$a]) % 256;
        $tmp = $box[$a];
        $box[$a] = $box[$j];
        $box[$j] = $tmp;
        $result .= chr(ord($string[$i]) ^ ($box[($box[$a] + $box[$j]) % 256]));
    }
    return $keyc.str_replace('=', '', base64_encode($result));
}

//cpEncode之后的解密函数，$string待解密的字符串，$key，密钥
function cpDecode($string,$key=''){
    if (empty($string)) {
        return '';
    }
    $ckey_length = 4;
    $key = md5($key);
    $keya = md5(substr($key, 0, 16));
    $keyb = md5(substr($key, 16, 16));
    $keyc = substr($string, 0, $ckey_length);

    $cryptkey = $keya.md5($keya.$keyc);
    $key_length = strlen($cryptkey);

    $string =  base64_decode(substr($string, $ckey_length));
    $string_length = strlen($string);

    $result = '';
    $box = range(0, 255);

    $rndkey = array();
    for($i = 0; $i <= 255; $i++) {
        $rndkey[$i] = ord($cryptkey[$i % $key_length]);
    }

    for($j = $i = 0; $i < 256; $i++) {
        $j = ($j + $box[$i] + $rndkey[$i]) % 256;
        $tmp = $box[$i];
        $box[$i] = $box[$j];
        $box[$j] = $tmp;
    }

    for($a = $j = $i = 0; $i < $string_length; $i++) {
        $a = ($a + 1) % 256;
        $j = ($j + $box[$a]) % 256;
        $tmp = $box[$a];
        $box[$a] = $box[$j];
        $box[$j] = $tmp;
        $result .= chr(ord($string[$i]) ^ ($box[($box[$a] + $box[$j]) % 256]));
    }
    if((substr($result, 0, 10) == 0 || substr($result, 0, 10) - time() > 0) && substr($result, 10, 16) == substr(md5(substr($result, 26).$keyb), 0, 16)) {
        return unserialize(substr($result, 26));
    }else{
        return '';
    }
}

function change_success_customer($customer_id){
//    更改客户成交状态

    $c=\think\facade\Db::name('crm_client_order')->where([['customer_id','=',$customer_id],['status','=',1]])->count();
    if($c>0){
        \think\facade\Db::name('crm_customer')->where('id',$customer_id)->update(['issuccess'=>1]);
        return true;
    }
    $c=\think\facade\Db::name('crm_contract')->where([['customer_id','=',$customer_id],['check_status','=',3]])->count();
    if($c>0){
        \think\facade\Db::name('crm_customer')->where('id',$customer_id)->update(['issuccess'=>1]);
        return true;
    }
    \think\facade\Db::name('crm_customer')->where('id',$customer_id)->update(['issuccess'=>0]);
    return false;

}

/**
 * 安全插入：自动过滤非表字段，返回最新 ID
 * @param string $table 表名（不含前缀）
 * @param array  $data  待插入数据
 * @return int 最新主键 ID
 * @throws \think\db\exception\DbException
 */
function safeInsert(string $table, array $data): int
{
    $prefix=getDataBaseConfig('prefix');
    $fields = \think\facade\Db::getTableFields($prefix.$table);     // 真实字段
    $clean  = array_intersect_key($data, array_flip($fields));
    $id=\think\facade\Db::name($table)->insertGetId($clean);
    return $id;
}

/**
 * 先嗅探是否为富文本，再决定是否净化
 */
function xss_clean($html)
{
    /* 空值直接返回 */
    if ($html === '' || $html === []) {
        return $html;
    }

    static $antiXss = null;
    if ($antiXss === null) {
        $antiXss = new \voku\helper\AntiXSS();
        /* 放行常用富文本标签 */
        $antiXss->removeEvilHtmlTags([
            'p','br','strong','em','u','span','div',
            'h1','h2','h3','h4','h5','h6',
            'ul','ol','li','img','a','table','thead','tbody','tr','td','th'
        ]);
        $antiXss->removeEvilAttributes(['style','class']);
    }

    /* 数组递归 */
    if (is_array($html)) {
        foreach ($html as $k => $v) {
            $html[$k] = xss_clean($v);
        }
        return $html;
    }

    /* 字符串场景 */
    $html = trim($html);
    if ($html === '') {
        return '';
    }

    /* --- 嗅探：出现 <xxx> 或常用富文本标签才净化，否则跳过 --- */
    $isRich = preg_match('/<[a-zA-Z]+[>\s]/', $html)          // 任意成对标签
        || preg_match('/<(p|br|strong|em|u|h[1-6]|ul|ol|li|img|a|div|span|table)(\s|>)/i', $html);

    return $isRich ? $antiXss->xss_clean($html) : $html;
}

function real_resourse($domain,$url){

    if(Str::endsWith($url,'http')){
        return $url;
    }
    //   增加如果后台没有设置$domain，就获取当前访问域名和协议
    $domain=empty($domain)?request()->domain():$domain;
    return trim($domain,'/').$url;
}