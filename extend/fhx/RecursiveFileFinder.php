<?php
namespace fhx;
class RecursiveFileFinder {
    private $rootPath;
    private $excludedDirs = ['.', '..'];

    public function __construct($rootPath) {
        $this->rootPath = realpath($rootPath);
        if (!$this->rootPath) {
            throw new Exception("指定的路径不存在或无法访问");
        }
    }

    public function findFiles() {
        $files = [];
        $this->scanDirectory($this->rootPath, $files);
        return $files;
    }

    private function scanDirectory($dir, &$files) {
        $items = scandir($dir);

        foreach ($items as $item) {
            if (in_array($item, $this->excludedDirs)) continue;

            $fullPath = $dir . DIRECTORY_SEPARATOR . $item;

            if (is_dir($fullPath)) {
                $this->scanDirectory($fullPath, $files);
            } else {
                $relativePath = str_replace($this->rootPath . DIRECTORY_SEPARATOR, '', $fullPath);
                $files[] = [
                    'path' => $fullPath,
                    'relative_path' => $relativePath,
//                    'size' => $this->formatSize(filesize($fullPath)),目前没有需求暂时注释掉
//                    'modified' => date("Y-m-d H:i:s", filemtime($fullPath))
                ];
            }
        }
    }

    public function formatSize($bytes) {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $index = 0;

        while ($bytes >= 1024 && $index < count($units) - 1) {
            $bytes /= 1024;
            $index++;
        }

        return round($bytes, 2) . ' ' . $units[$index];
    }
}