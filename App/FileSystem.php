<?php

namespace App;

use App\Interfaces\FileSystemInterface;

class FileSystem implements FileSystemInterface
{
    public function fopen(string $filename, string $mode)
    {
        return fopen($filename, $mode);
    }

    public function fputcsv($handle, array $fields): int|false
    {
        return fputcsv($handle, $fields);
    }

    public function fclose($handle): bool
    {
        return fclose($handle);
    }
}
