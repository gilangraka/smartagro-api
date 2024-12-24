<?php

namespace App\Helpers;

use Illuminate\Support\Str;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class UploadHelper
{
    public static function uploadFile(UploadedFile $file, string $path, string $disk = 'public')
    {
        if (!$file->isValid()) {
            throw new \Exception('Invalid file upload');
        }

        $filename = Str::uuid()->toString() . '.' . $file->getClientOriginalExtension();
        $file->storeAs($path, $filename, $disk);
        return $filename;
    }

    public static function deleteFile(string $filePath, string $disk = 'public')
    {
        return Storage::disk($disk)->delete($filePath);
    }
}
