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
        // 一键压缩 JS:  php think min -r js
        // 一键压缩 CSS: php think min -r css
        // 全部压缩:      php think min -r all

        $resource = $input->getOption('resource') ?: '';
        if (! $resource || ! in_array($resource, ['js', 'css', 'all'])) {
            throw new Exception('Please input correct resource name');
        }
        $resourceArr = $resource == 'all' ? ['js', 'css'] : [$resource];

        if (in_array('js', $resourceArr)) {
            $this->minifyJs();
        }
        if (in_array('css', $resourceArr)) {
            $this->minifyCss();
        }

        echo "所有文件处理完成!\n";
    }

    /**
     * 压缩 JS 文件（依赖 uglify-js）.
     */
    protected function minifyJs()
    {
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

        $uglifyCmd = \tools\Hs::findCommand('uglifyjs');
        if (! $uglifyCmd) {
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

        echo "开始压缩 JS 文件...\n";
        echo "========================================\n";

        foreach ($js as $filePath) {
            $sourceFile = app()->getRootPath().'public/static/'.$filePath . '.js';
            $minFile =  app()->getRootPath().'public/static/'.$filePath . '.min.js';

            if (!file_exists($sourceFile)) {
                echo "跳过: $sourceFile (文件不存在)\n";
                echo "----------------------------------------\n";
                continue;
            }

            $command = sprintf('%s "%s" -o "%s" --compress --comments "/^!/"', $uglifyCmd, $sourceFile, $minFile);

            $out = [];
            $returnVar = 0;
            echo "执行命令: $command\n";
            exec($command, $out, $returnVar);

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
                $this->printExecOutput($out);
                echo "   请尝试手动执行以下命令排查问题：\n";
                echo "   $command\n";
            }
            echo "----------------------------------------\n";
        }
    }

    /**
     * 压缩 CSS 文件（依赖 csso-cli）.
     */
    protected function minifyCss()
    {
        $css = [
            'admin/css/public',
            'plugs/lay-module/layuimini/layuimini',
            'plugs/lay-module/layuimini/themes/default',
            'plugs/zTree/css/zTreeStyle',
            'plugs/lay-module/selectPage/selectpage',
        ];

        $cssoCmd = \tools\Hs::findCommand('csso');
        if (! $cssoCmd) {
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

        echo "开始压缩 CSS 文件...\n";
        echo "========================================\n";

        foreach ($css as $filePath) {
            $sourceFile = app()->getRootPath() . 'public/static/' . $filePath . '.css';
            $minFile = app()->getRootPath() . 'public/static/' . $filePath . '.min.css';

            if (!file_exists($sourceFile)) {
                echo "跳过: $sourceFile (文件不存在)\n";
                echo "----------------------------------------\n";
                continue;
            }

            $command = sprintf($cssoCmd . ' -i "%s" -o "%s"', $sourceFile, $minFile);

            $out = [];
            $returnVar = 0;
            echo "执行命令: $command\n";
            exec($command, $out, $returnVar);

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
                echo "   1. csso 命令执行异常\n";
                echo "   2. CSS 文件存在语法错误\n";
                echo "   3. 文件路径包含特殊字符\n\n";
                $this->printExecOutput($out);
                echo "   请尝试手动执行以下命令排查问题：\n";
                echo "   $command\n";
            }
            echo "----------------------------------------\n";
        }
    }

    /**
     * 回显 exec 捕获的命令行输出，便于排查失败原因.
     *
     * @param array $out
     */
    protected function printExecOutput(array $out)
    {
        if (empty($out)) {
            return;
        }
        echo "   命令输出：\n";
        foreach ($out as $line) {
            echo "   " . trim($line) . "\n";
        }
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