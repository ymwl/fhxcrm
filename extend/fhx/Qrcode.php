<?php
namespace fhx;

use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\RoundBlockSizeMode\RoundBlockSizeModeMargin;
use Endroid\QrCode\RoundBlockSizeMode\RoundBlockSizeModeNone;

/**
* 技术 zrwx978
 **/

/**
 * 内容转二维吗
 */
 
class Qrcode {
    public static $config=[
        'foreground'=>'#000000',
        'background'=>'#ffffff',
    ];
    /**
     * 生成二维码
     * @param $params
     * @return \Endroid\QrCode\QrCode
     * @throws \Endroid\QrCode\Exception\InvalidPathException
     */
    public static function qrcode($params)
    {
        $config = self::$config;
        $params = is_array($params) ? $params : [$params];
        $params = array_merge($config, $params);

//        $params['labelfontpath'] = isset($params['labelfontpath']) && is_file($params['labelfontpath']) ? $params['labelfontpath'] : app()->getRootPath() . 'public' . $config['labelfontpath'];
//        $params['logopath'] = isset($params['logopath']) && is_file($params['logopath']) ? $params['logopath'] : app()->getRootPath() . 'public' . $config['logopath'];




        // 创建实例
        $qrCode = new \Endroid\QrCode\QrCode($params['text']);
        $qrCode->setSize($params['size']);

        // 高级选项
//        $qrCode->setWriterByName($params['format']);
        $qrCode->setMargin($params['padding']);
        $qrCode->setEncoding(new Encoding('UTF-8'));

        $qrCode->setErrorCorrectionLevel(new \Endroid\QrCode\ErrorCorrectionLevel\ErrorCorrectionLevelLow());
        list($r, $g, $b) = sscanf($params['foreground'], "#%02x%02x%02x");
        $qrCode->setForegroundColor(new \Endroid\QrCode\Color\Color($r, $g, $b));
        list($r, $g, $b) = sscanf($params['background'], "#%02x%02x%02x");
        $qrCode->setBackgroundColor(new \Endroid\QrCode\Color\Color($r, $g, $b));

        // 设置标签
        if (isset($params['label']) && $params['label']) {
            $qrCode->setLabel($params['label'], $params['labelfontsize'], $params['labelfontpath'], $params['labelalignment']);
        }

        // 设置Logo
        if (isset($params['logo']) && $params['logo']) {
            $qrCode->setLogoPath($params['logopath']);
            $qrCode->setLogoSize($params['logosize'], $params['logosize']);
        }

        $qrCode->setRoundBlockSizeMode(new RoundBlockSizeModeNone());

//        $qrCode->setValidateResult(false);
//        $qrCode->setWriterOptions(['exclude_xml_declaration' => true]);

        return $qrCode;
    }
}

//\fhx\Qrcode::qrcode([]);
