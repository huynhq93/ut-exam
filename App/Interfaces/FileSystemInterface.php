<?php

namespace App\Interfaces;

interface FileSystemInterface
{
    public function fopen(string $filename, string $mode);
    public function fputcsv($handle, array $fields): int|false;
    public function fclose($handle): bool;
} 