<?php

namespace Krzysztofzylka\File;

class Validation
{

    /**
     * Check file size
     * @param string|int $fileSize file size in bytes or file path
     * @param int $maxSize Allowed max size in MB
     * @return bool
     */
    public static function size(int|string $fileSize, int $maxSize): bool
    {
        if (is_string($fileSize)) {
            $fileSize = filesize($fileSize);
        }

        return $fileSize <= ($maxSize * 1024 * 1024);
    }

    /**
     * Check file mime type
     * @param string $name
     * @param array $allowedTypes
     * @return bool
     */
    public static function mimeType(string $name, array $allowedTypes): bool
    {
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $name);
        finfo_close($finfo);

        return in_array($mimeType, $allowedTypes);
    }

}