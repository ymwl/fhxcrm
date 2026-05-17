<?php


namespace app\admin\command;

use think\Exception;
use think\console\Input;
use think\console\Output;
use think\console\Command;
use think\console\input\Option;

class Min extends Command
{
    /**
     * 路径和文件名配置.
     */
    protected $options = [
        'cssBaseUrl'  => 'public/assets/css/',
        'cssBaseName' => '{module}',
        'jsBaseUrl'   => 'public/assets/js/',
        'jsBaseName'  => 'require-{module}',
    ];

    protected function configure()
    {
        $this
            ->setName('min')
            ->addOption('module', 'm', Option::VALUE_REQUIRED,
                'module name(frontend or backend),use \'all\' when build all modules', null)
            ->addOption('resource', 'r', Option::VALUE_REQUIRED,
                'resource name(js or css),use \'all\' when build all resources', null)
            ->addOption('optimize', 'o', Option::VALUE_OPTIONAL, 'optimize type(uglify|closure|none)', 'none')
            ->addOption('is_debug', 'd', Option::VALUE_OPTIONAL, 'is open debug', '0')
            ->setDescription('Compress js and css file');
    }


    protected function execute(Input $input, Output $output)
    {
//        //一键压缩JS
//php think min -r js
        //一键压缩css
//php think min -r css

        //php think min -r all

        $resource = $input->getOption('resource') ?: '';
        if (! $resource || ! in_array($resource, ['js', 'css', 'all'])) {
            throw new Exception('Please input correct resource name');
        }
        $resourceArr = $resource == 'all' ? ['js', 'css'] : [$resource];
            if (strpos(PHP_OS, 'WIN') !== false) {
                // Winsows下请手动配置配置该值,一般将该值配置为 '"C:\Program Files\nodejs\node.exe"'，除非你的Node安装路径有变更
                $nodeExec = 'C:\Program Files\nodejs\node.exe';
                if (file_exists($nodeExec)) {
                    $nodeExec = '"'.$nodeExec.'"';
                } else {
                    // 如果 '"C:\Program Files\nodejs\node.exe"' 不存在，可能是node安装路径有变更
                    // 但安装node会自动配置环境变量，直接执行 '"node.exe"' 提高第一次使用压缩打包的成功率
                    $nodeExec = '"node.exe"';
                }
            } else {
                try {
                    $node_version = exec('node -v');
                    $nodeExec = 'node';
                    if (! $node_version) {
                        throw new Exception('node environment not found!please install node first!');
                    }
                } catch (Exception $e) {
                    throw new Exception($e->getMessage());
                }
            }


        if(in_array('js', $resourceArr)){
            $js = [
                'plugs/easy-admin/easy-admin',
                'plugs/webupload/uploader/webuploader',
                'plugs/lay-module/selectPage/selectpage',
                'plugs/echarts/echarts-theme',
                'plugs/xm-select/xm-select',
                'plugs/lay-module/layuimini/miniAdmin',
                'plugs/lay-module/layuimini/miniMenu',
                'plugs/lay-module/layuimini/miniTab',
                'plugs/lay-module/layuimini/miniTheme',
                'plugs/lay-module/treetable-lay/treetable',
                'plugs/lay-module/tableSelect/tableSelect',
                'plugs/lay-module/iconPicker/iconPickerFa',
                'plugs/lay-module/autocomplete/autocomplete',
                'ueditor/ueditor.all',
                'config-admin'
            ];
            // 检查 uglifyjs 是否可用
            if (!\tools\Hs::command_exists('uglifyjs')) {
                echo "========================================\n";
                echo "⚠️  缺少依赖：uglifyjs\n";
                echo "----------------------------------------\n";
                echo "未找到 uglifyjs 命令，无法压缩 JS 文件。\n\n";
                echo "【解决方法】请安装 uglify-js 包：\n\n";
                echo "  方式1 - 全局安装（推荐）:\n";
                echo "    npm install -g uglify-js\n\n";
                echo "  方式2 - 本地安装（当前项目）:\n";
                echo "    npm install uglify-js --save-dev\n";
                echo "========================================\n";
                return;
            }

// 开始压缩过程
            echo "开始压缩 JS 文件...\n";
            echo "========================================\n";

            foreach ($js as $filePath) {
                // 添加 .js 扩展名（因为数组中存储的是无扩展名的路径）
                $sourceFile = app()->getRootPath().'public/static/'.$filePath . '.js';
                $minFile =  app()->getRootPath().'public/static/'.$filePath . '.min.js';

                // 检查源文件是否存在
                if (!file_exists($sourceFile)) {
                    echo "跳过: $sourceFile (文件不存在)\n";
                    continue;
                }

                // 执行压缩命令  uglifyjs contentimport.js -o contentimport.min.js --compress --comments "/^!/"
                $command = sprintf(
                    'uglifyjs "%s" -o "%s" --compress --comments "/^!/"',
                    $sourceFile,
                    $minFile
                );

                // 执行命令并获取返回状态
                $output = [];
                $returnVar = 0;
                exec($command, $output, $returnVar);

                // 检查压缩结果
                if ($returnVar === 0 && file_exists($minFile)) {
                    $originalSize = filesize($sourceFile);
                    $minSize = filesize($minFile);
                    $saved = round(($originalSize - $minSize) / $originalSize * 100, 1);

                    echo "✅ 压缩成功: $minFile\n";
                    echo "   原始大小: " . format_bytes($originalSize) . "\n";
                    echo "   压缩大小: " . format_bytes($minSize) . " (节省 $saved%)\n";
                } else {
                    echo "❌ 压缩失败: $sourceFile\n";
                    echo "   可能原因：\n";
                    echo "   1. uglifyjs 命令执行异常\n";
                    echo "   2. JS 文件存在语法错误\n";
                    echo "   3. 文件路径包含特殊字符\n\n";
                    echo "   请尝试手动执行以下命令排查问题：\n";
                    echo "   uglifyjs \"$sourceFile\" -o \"$minFile\" --compress --comments \"/^!/\"\n";
                }
                echo "----------------------------------------\n";
            }
        }
        if(in_array('css', $resourceArr)) {
            $css = [
                'admin/css/public',
                'plugs/lay-module/layuimini/layuimini',
                'plugs/lay-module/layuimini/themes/default',
                'plugs/zTree/css/zTreeStyle',
                'plugs/lay-module/selectPage/selectpage',

            ];
            // 检查 csso 是否可用（支持全局和本地安装）
            $cssoCmd = \tools\Hs::findCommand('csso');
            if (!$cssoCmd) {
                echo "========================================\n";
                echo "⚠️  缺少依赖：csso\n";
                echo "----------------------------------------\n";
                echo "未找到 csso 命令，无法压缩 CSS 文件。\n\n";
                echo "【解决方法】请安装 csso-cli 包（注意：不是 csso）:\n\n";
                echo "  方式1 - 全局安装（推荐）:\n";
                echo "    npm install -g csso-cli\n\n";
                echo "  方式2 - 本地安装（当前项目）:\n";
                echo "    npm install csso-cli --save-dev\n";
                echo "========================================\n";
                return;
            }

// 开始压缩过程
            echo "开始压缩 CSS 文件...\n";
            echo "========================================\n";

            foreach ($css as $filePath) {
                // 生成目标文件路径（.css -> .min.css）
                $sourceFile = app()->getRootPath() . 'public/static/' . $filePath . '.css';
                $minFile = app()->getRootPath() . 'public/static/' . $filePath . '.min.css';

                // 检查源文件是否存在
                if (!file_exists($sourceFile)) {
                    echo "跳过: $sourceFile (文件不存在)\n";
                    echo "----------------------------------------\n";
                    continue;
                }

                // 执行压缩命令
                $command = sprintf(
                    $cssoCmd . ' -i "%s" -o "%s"',
                    $sourceFile,
                    $minFile
                );

                // 执行命令并获取返回状态
                $output = [];
                $returnVar = 0;
                echo "执行命令: $command\n";
                exec($command, $output, $returnVar);

                // 检查压缩结果
                if ($returnVar === 0 && file_exists($minFile)) {
                    $originalSize = filesize($sourceFile);
                    $minSize = filesize($minFile);
                    $saved = round(($originalSize - $minSize) / $originalSize * 100, 1);

                    echo "✅ 压缩成功: $minFile\n";
                    echo "   原始大小: " . format_bytes($originalSize) . "\n";
                    echo "   压缩大小: " . format_bytes($minSize) . " (节省 $saved%)\n";
                } else {
                    echo "❌ 压缩失败: $sourceFile\n";
                    echo "   可能原因：\n";
                    echo "   1. csso 命令执行异常（若提示 'could not determine executable'，请运行: npm install csso-cli --save-dev）\n";
                    echo "   2. CSS 文件存在语法错误\n";
                    echo "   3. 文件路径包含特殊字符\n\n";
                    echo "   请尝试手动执行以下命令排查问题：\n";
                    echo "   $cssoCmd -i \"$sourceFile\" -o \"$minFile\"\n";
                }
                echo "----------------------------------------\n";
            }
        }



        echo "所有文件处理完成!\n";

    }

    /**
     * 写入到文件.
     *
     * @param  string  $name
     * @param  array  $data
     * @param  string  $pathname
     *
     * @return mixed
     */
    protected function writeToFile($name, $data, $pathname)
    {
        $search = $replace = [];
        foreach ($data as $k => $v) {
            $search[] = "{%{$k}%}";
            $replace[] = $v;
        }
        $stub = file_get_contents($this->getStub($name));
        $content = str_replace($search, $replace, $stub);

        if (! is_dir(dirname($pathname))) {
            mkdir(strtolower(dirname($pathname)), 0755, true);
        }

        return file_put_contents($pathname, $content);
    }

    /**
     * 获取基础模板
     *
     * @param  string  $name
     *
     * @return string
     */
    protected function getStub($name)
    {
        return __DIR__.DIRECTORY_SEPARATOR.'Min'.DIRECTORY_SEPARATOR.'stubs'.DIRECTORY_SEPARATOR.$name.'.stub';
    }
}
