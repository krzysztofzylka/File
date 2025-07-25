<?php

namespace Krzysztofzylka\File;

use Exception;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

class File
{

    /**
     * Repair path
     * @param string $path The path to be repaired
     * @return string The repaired path
     */
    public static function repairPath(string $path): string
    {
        $path = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $path);

        do {
            $path = str_replace(DIRECTORY_SEPARATOR . DIRECTORY_SEPARATOR, DIRECTORY_SEPARATOR, $path);
        } while (str_contains($path, DIRECTORY_SEPARATOR . DIRECTORY_SEPARATOR));

        return $path;
    }

    /**
     * Creates a new directory or directories.
     * @param string|array $paths the path(s) of the directory to be created.
     * @param int $permission Optional. The permissions to set for the new directory. Default is 0755.
     * @return ?bool Returns true if the directory is successfully created, false if the directory already exists,
     *               or null if an array of paths is provided (recursive directory creation).
     * @throws Exception If the directory creation failed.
     */
    public static function mkdir(string|array $paths, int $permission = 0755): ?bool
    {
        if (is_array($paths)) {
            foreach ($paths as $path) {
                self::mkdir(self::repairPath($path), $permission);
            }

            return null;
        }

        if (file_exists($paths)) {
            return false;
        }

        try {
            if (@mkdir(self::repairPath($paths), $permission, true)) {
                return true;
            } else {
                $error = error_get_last();

                throw new Exception('Failed create directory ' . realpath($paths) . ', Error: ' . $error['message']);
            }
        } catch (\Throwable $exception) {
            throw new Exception($exception->getMessage() . '(' . realpath('/') . ')');
        }
    }

    /**
     * Deletes a file.
     * @param string $path The path of the file to be deleted.
     * @return bool Returns true if the file is successfully deleted, or false if the file does not exist.
     */
    public static function unlink(string $path): bool
    {
        if (!file_exists($path)) {
            return false;
        }

        return unlink($path);
    }

    /**
     * Scans a directory and its subdirectories recursively, returning an array of all file paths.
     * @param string $dir The directory to scan.
     * @return array An array of file paths within the directory and its subdirectories.
     */
    public static function scanDir(string $dir): array
    {
        $result = [];

        foreach(scandir($dir) as $filename) {
            if (str_starts_with($filename, '.')) {
                continue;
            }

            $filePath = $dir . '/' . $filename;

            if (is_dir($filePath)) {
                foreach (self::scanDir($filePath) as $childFilename) {
                    $result[] = $filename . '/' . $childFilename;
                }
            } else {
                $result[] = $filename;
            }
        }

        return $result;
    }

    /**
     * Creates a new file at the specified path and sets its content.
     * @param string $path The path of the file to be created.
     * @param ?string $value Optional. The content to be set for the file. Default is null.
     * @return bool Returns true if the file is successfully created and its content is set, false if the file already exists
     *              or an exception occurs while creating or setting the content for the file.
     */
    public static function touch(string $path, ?string $value = null): bool
    {
        if (file_exists($path)) {
            return false;
        }

        try {
            if (!$value) {
                touch($path);
            }

            file_put_contents($path, $value);

            return true;
        } catch (Exception) {
            return false;
        }
    }

    /**
     * Copies a file from the source path to the destination path.
     * @param string $sourcePath The path of the source file to be copied.
     * @param string $destinationPath The path where the file should be copied to.
     * @return bool Returns true if the file is successfully copied, false if the file already exists and has the same or newer modification time as the source file.
     */
    public static function copy(string $sourcePath, string $destinationPath): bool
    {
        $destinationModify = file_exists($destinationPath) ? filemtime($destinationPath) : 0;
        $sourceModify = file_exists($sourcePath) ? filemtime($sourcePath) : 0;

        if (!file_exists($destinationPath) || $sourceModify > $destinationModify) {
            return copy($sourcePath, $destinationPath);
        }

        return false;
    }

    /**
     * Copies a directory and its contents to a destination directory.
     * @param string $source The source directory path.
     * @param string $destination The destination directory path.
     * @param string $permission
     * @throws Exception If the directory copying fails.
     */
    public static function copyDirectory(string $source, string $destination, string $permission = '0755'): void
    {
        if (is_dir($source) && is_dir($destination)) {
            $iterator = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($source, RecursiveDirectoryIterator::SKIP_DOTS),
                RecursiveIteratorIterator::SELF_FIRST
            );

            foreach ($iterator as $item) {
                $src = $item->getPathname();
                $dst = $destination . '/' . $iterator->getSubPathName();

                if ($item->isDir()) {
                    self::mkdir($dst, $permission);
                } else {
                    copy($src, $dst);
                }
            }
        }
    }

    /**
     * Returns the extension of a file path.
     * @param string $path The file path to get the extension from.
     * @return string The extension of the file path.
     */
    public static function getExtension(string $path): string
    {
        return pathinfo($path, PATHINFO_EXTENSION);
    }

    /**
     * Retrieves the content type based on the file extension.
     * @param string $fileExtension The file extension.
     * @return false|string Returns the corresponding content type if the file extension is recognized,
     *                      false if the file extension is not recognized.
     */
    public static function getContentType(string $fileExtension): false|string
    {
        $images = ['gif', 'png', 'webp', 'bmp', 'avif'];
        $text = ['css', 'csv'];
        $video = ['mp4', 'webm'];
        $application = ['zip', 'xml', 'rtf', 'pdf', 'json'];
        $font = ['woff2', 'woff', 'ttf', 'otf'];
        $audio = ['wav', 'pus', 'aac'];

        if (in_array($fileExtension, $images)) {
            return 'image/' . $fileExtension;
        } elseif (in_array($fileExtension, $text)) {
            return 'text/' . $fileExtension;
        } elseif (in_array($fileExtension, $video)) {
            return 'video/' . $fileExtension;
        } elseif (in_array($fileExtension, $application)) {
            return 'application/' . $fileExtension;
        } elseif (in_array($fileExtension, $font)) {
            return 'font/' . $fileExtension;
        } elseif (in_array($fileExtension, $audio)) {
            return 'audio/' . $fileExtension;
        }

        return match ($fileExtension) {
            'jpeg', 'jpg' => 'image/jpeg',
            'svg' => 'image/svg+xml',
            'text' => 'text/plain',
            'doc' => 'application/msword',
            'js', 'mjs' => 'text/javascript',
            'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            '7z' => 'application/x-7z-compressed',
            'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'xul' => 'application/vnd.mozilla.xul+xml',
            '3gp' => 'video/3gpp',
            '3g2' => 'video/3gpp2',
            'xhtml' => 'application/xhtml+xml',
            'xls' => 'application/vnd.ms-excel',
            'vsd' => 'application/vnd.visio',
            'rar' => 'application/vnd.rar',
            'ts' => 'video/mp2t',
            'tif', 'tiff' => 'image/tiff',
            'tar' => 'application/x-tar',
            'sh' => 'application/x-sh',
            'pptx' => 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
            'ppt' => 'application/vnd.ms-powerpoint',
            'php' => 'application/x-httpd-php',
            'ogx' => 'application/ogg',
            'ogv' => 'video/ogg',
            'mp3' => 'audio/mpeg',
            'mid', 'midi' => 'audio/midi',
            'jsonld' => 'application/ld+json',
            'jar' => 'application/java-archive',
            'ics' => 'text/calendar',
            'ico' => 'image/vnd.microsoft.icon',
            'htm', 'html' => 'text/html',
            'gz' => 'application/gzip',
            'epub' => 'application/epub+zip',
            'eot' => 'application/vnd.ms-fontobject',
            'csh' => 'application/x-csh',
            'cda' => 'application/x-cdf',
            'bz2' => 'application/x-bzip2',
            'bz' => 'application/x-bzip',
            'bin' => 'application/octet-stream',
            'awz' => 'application/vnd.amazon.ebook',
            'avi' => 'video/x-msvideo',
            'arc' => 'application/x-freearc',
            'abw' => 'application/x-abiword',
            'x3d' => 'application/vnd.hzn-3d-crossword',
            'mseq' => 'application/vnd.mseq',
            'pwn' => 'application/vnd.3m.post-it-notes',
            'ace' => 'application/x-ace-compressed',
            'dir' => 'application/x-director',
            'apk' => 'application/vnd.android.package-archive',
            'aiff' => 'audio/x-aiff',
            'atom' => 'application/atom+xml',
            'torrent' => 'application/x-bittorrent',
            'c' => 'text/x-c',
            'deb' => 'application/x-debian-package',
            'dts' => 'audio/vnd.dts',
            'flv' => 'video/x-flv',
            'f4v' => 'video/x-f4v',
            'cer' => 'application/pkix-cert',
            'java' => 'text/x-java-source',
            'jsx' => 'text/jsx',
            'kml' => 'application/vnd.google-earth.kml+xml',
            'kmz' => 'application/vnd.google-earth.kmz',
            'm4a' => 'audio/x-m4a',
            'm4v' => 'video/x-m4v',
            'm4p' => 'application/mp4',
            'm4u' => 'video/vnd.mpegurl',
            'm3u8' => 'application/vnd.apple.mpegurl',
            'm3u' => 'audio/x-mpegurl',
            'latex' => 'application/x-latex',
            'kwd' => 'application/vnd.kde.kword',
            'kon' => 'application/vnd.kde.kontour',
            'ser' => 'application/java-serialized-object',
            'karbon' => 'application/vnd.kde.karbon',
            'kfo' => 'application/vnd.kde.kformula',
            'flw' => 'application/vnd.kde.kivio',
            'mkv' => 'video/x-matroska',
            'mpg', 'mpeg' => 'video/mpeg',
            'flac' => 'audio/flac',
            'txt' => 'text/plain',
            'md' => 'text/markdown',
            'vcf' => 'text/vcard',
            'odt' => 'application/vnd.oasis.opendocument.text',
            'ods' => 'application/vnd.oasis.opendocument.spreadsheet',
            'odp' => 'application/vnd.oasis.opendocument.presentation',
            'odg' => 'application/vnd.oasis.opendocument.graphics',
            'heic' => 'image/heic',
            'heif' => 'image/heif',
            default => false,
        };
    }

    /**
     * Get file mime type
     * @param string $path
     * @return string
     */
    public static function getMimeType(string $path): string
    {
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $path);
        finfo_close($finfo);

        return $mimeType;
    }

}