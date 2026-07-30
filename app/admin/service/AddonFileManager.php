<?php

namespace app\admin\service;

use RecursiveIteratorIterator;
use RecursiveDirectoryIterator;
use Exception;

/**
 * Addon file move / cleanup service.
 *
 * Provides safe file operations for plugin installation/uninstallation:
 * - move() moves files from the addon directory to the project root.
 * - moveBack() moves files from the project root back to the addon directory.
 * - cleanup() removes target files that are identical to the source files (legacy uninstall fallback).
 */
class AddonFileManager
{

    /**
     * Recursively move all files from $source to $target.
     *
     * Files are renamed (moved) from the source directory to the target directory.
     * If any target file already exists, the entire operation is aborted and already-moved
     * files are moved back.
     *
     * Empty directories remaining in the source after move are cleaned up.
     *
     * @param string $source Source directory (addon directory, e.g. addons/xxx/app).
     * @param string $target Target directory (project directory, e.g. app).
     * @return array Contains:
     *               - files: list of moved files with source/target absolute paths
     *               - map: associative array of relative_path => relative_path for file_map.json
     * @throws Exception
     */
    public function move(string $source, string $target): array
    {
        $source = rtrim(realpath($source), '/\\');
        $target = rtrim($target, '/\\');

        if (!is_dir($source)) {
            return ['files' => [], 'map' => []];
        }

        $this->ensureDir($target);

        // First pass: check for conflicts (target files that already exist)
        $files = $this->scanFiles($source);
        $conflicts = [];
        foreach ($files as $relativePath) {
            $targetFile = $target . DIRECTORY_SEPARATOR . $relativePath;
            if (is_file($targetFile)) {
                $conflicts[] = $targetFile;
            }
        }
        if (!empty($conflicts)) {
            throw new Exception('File conflict detected: ' . implode(', ', $conflicts));
        }

        // Second pass: move files
        $moved = [];
        $map = [];
        try {
            foreach ($files as $relativePath) {
                $sourceFile = $source . DIRECTORY_SEPARATOR . $relativePath;
                $targetFile = $target . DIRECTORY_SEPARATOR . $relativePath;

                $this->ensureDir(dirname($targetFile));

                if (!rename($sourceFile, $targetFile)) {
                    throw new Exception("Failed to move: {$sourceFile}");
                }

                $moved[] = [
                    'source' => $sourceFile,
                    'target' => $targetFile,
                ];
                // file_map.json: key = relative to addon app/public, value = relative to project root
                $map[$relativePath] = $relativePath;
            }
        } catch (Exception $e) {
            // Rollback: move files back to source
            foreach ($moved as $file) {
                if (is_file($file['target'])) {
                    $this->ensureDir(dirname($file['source']));
                    rename($file['target'], $file['source']);
                }
            }
            throw $e;
        }

        // Clean up empty directories in source
        $this->cleanupEmptyDirs($source);

        return ['files' => $moved, 'map' => $map];
    }

    /**
     * Move files from target back to source based on a file map (uninstall scenario).
     *
     * @param array $fileMap Associative array of relativePath => relativePath from file_map.json.
     * @param string $targetBase Absolute base path for target files (e.g. root_path()).
     * @param string $sourceBase Absolute base path for source files (e.g. addons/xxx/).
     * @return array List of moved-back files.
     * @throws Exception
     */
    public function moveBack(array $fileMap, string $targetBase, string $sourceBase): array
    {
        $targetBase = rtrim($targetBase, '/\\');
        $sourceBase = rtrim($sourceBase, '/\\');
        $moved = [];

        foreach ($fileMap as $relSource => $relTarget) {
            // 规范化相对路径中的混合分隔符（file_map.json 可能存在 / 和 \ 混用）
            $relSource = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $relSource);
            $relTarget = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $relTarget);

            $targetFile = $targetBase . DIRECTORY_SEPARATOR . $relTarget;
            $sourceFile = $sourceBase . DIRECTORY_SEPARATOR . $relSource;

            if (!is_file($targetFile)) {
                continue;
            }

            $this->ensureDir(dirname($sourceFile));

            if (!rename($targetFile, $sourceFile)) {
                throw new Exception("Failed to move back: {$targetFile}");
            }

            $moved[] = [
                'source' => $sourceFile,
                'target' => $targetFile,
            ];
        }

        return $moved;
    }

    /**
     * Uninstall cleanup: remove target files that are identical to the source files.
     *
     * Compares files by size and md5. Only files that match exactly are deleted.
     * Empty directories created by the original installation are removed, but originally empty
     * directories and the target root itself are preserved.
     *
     * @param string $source Source directory (addon directory).
     * @param string $target Target directory (project directory).
     * @return array List of deleted files. Each element contains:
     *               - source: absolute source path
     *               - target: absolute target path
     * @throws Exception
     */
    public function cleanup(string $source, string $target): array
    {
        $source = rtrim(realpath($source), '/\\');
        $target = rtrim(realpath($target), '/\\');

        if (!is_dir($source)) {
            throw new Exception("Source directory does not exist: {$source}");
        }

        if (!is_dir($target)) {
            return [];
        }

        $originallyEmptyDirs = $this->findEmptyDirs($target);

        $deleted = [];
        $files = $this->scanFiles($source);

        foreach ($files as $relativePath) {
            $sourceFile = $source . DIRECTORY_SEPARATOR . $relativePath;
            $targetFile = $target . DIRECTORY_SEPARATOR . $relativePath;

            if (!is_file($targetFile)) {
                continue;
            }

            if ($this->filesEqual($sourceFile, $targetFile)) {
                unlink($targetFile);
                $deleted[] = [
                    'source' => $sourceFile,
                    'target' => $targetFile,
                ];
            }
        }

        // Remove directories that became empty because of this cleanup, excluding originally empty dirs.
        $dirsToCheck = [];
        foreach ($deleted as $file) {
            $dir = dirname($file['target']);
            while ($dir !== $target && strpos($dir, $target . DIRECTORY_SEPARATOR) === 0) {
                if (!isset($originallyEmptyDirs[$dir])) {
                    $dirsToCheck[$dir] = true;
                }
                $dir = dirname($dir);
            }
        }

        $dirs = array_keys($dirsToCheck);
        usort($dirs, function ($a, $b) {
            return strlen($b) <=> strlen($a);
        });
        foreach ($dirs as $dir) {
            $this->removeDirIfEmpty($dir);
        }

        return $deleted;
    }

    /**
     * Recursively scan a directory and return relative file paths.
     *
     * @param string $dir Directory to scan.
     * @return array Relative file paths.
     */
    private function scanFiles(string $dir): array
    {
        $files = [];
        $base = rtrim(realpath($dir), '/\\');

        if (!is_dir($base)) {
            return $files;
        }

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($base, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::SELF_FIRST
        );

        foreach ($iterator as $item) {
            if ($item->isFile()) {
                $files[] = $this->relativePath($item->getPathname(), $base);
            }
        }

        sort($files);
        return $files;
    }

    /**
     * Ensure a directory exists, creating it recursively if necessary.
     *
     * @param string $path Directory path.
     * @return void
     */
    private function ensureDir(string $path): void
    {
        if (!is_dir($path)) {
            mkdir($path, 0755, true);
        }
    }

    /**
     * Compute the relative path of $path against $base.
     *
     * @param string $path Full path.
     * @param string $base Base directory.
     * @return string Relative path.
     */
    private function relativePath(string $path, string $base): string
    {
        $path = rtrim($path, '/\\');
        $base = rtrim($base, '/\\');

        if (strpos($path, $base . DIRECTORY_SEPARATOR) === 0) {
            return substr($path, strlen($base) + 1);
        }

        return $path;
    }

    /**
     * Compare two files by size and md5.
     *
     * @param string $file1
     * @param string $file2
     * @return bool True if both files exist and are identical.
     */
    private function filesEqual(string $file1, string $file2): bool
    {
        if (!is_file($file1) || !is_file($file2)) {
            return false;
        }

        if (filesize($file1) !== filesize($file2)) {
            return false;
        }

        return md5_file($file1) === md5_file($file2);
    }

    /**
     * Remove a directory only if it is empty.
     *
     * @param string $dir Directory path.
     * @return void
     */
    private function removeDirIfEmpty(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }

        $items = scandir($dir);
        $items = array_diff($items, ['.', '..']);

        if (empty($items)) {
            rmdir($dir);
        }
    }

    /**
     * Find all empty directories under a directory.
     *
     * @param string $dir Base directory.
     * @return array Associative array with empty directory absolute paths as keys.
     */
    private function findEmptyDirs(string $dir): array
    {
        $empty = [];
        $base = rtrim(realpath($dir), '/\\');

        if (!is_dir($base)) {
            return $empty;
        }

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($base, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::SELF_FIRST
        );

        foreach ($iterator as $item) {
            if ($item->isDir()) {
                $path = $item->getPathname();
                $items = scandir($path);
                $items = array_diff($items, ['.', '..']);
                if (empty($items)) {
                    $empty[$path] = true;
                }
            }
        }

        return $empty;
    }

    /**
     * Remove all empty directories under a given directory recursively (bottom-up).
     *
     * The base directory itself is never removed.
     *
     * @param string $dir Base directory path.
     * @return void
     */
    private function cleanupEmptyDirs(string $dir): void
    {
        $base = rtrim($dir, '/\\');
        if (!is_dir($base)) {
            return;
        }

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($base, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST
        );

        foreach ($iterator as $item) {
            if ($item->isDir()) {
                $path = $item->getPathname();
                $items = scandir($path);
                $items = array_diff($items, ['.', '..']);
                if (empty($items)) {
                    rmdir($path);
                }
            }
        }
    }

}
