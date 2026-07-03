<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/** Upload/substituição de avatares no disco configurado (public/s3). */
class AvatarService
{
    private function disk(): string
    {
        return config('filesystems.uploads', 'public');
    }

    public function store(UploadedFile $file): string
    {
        $path = $file->store('avatars', $this->disk());

        return Storage::disk($this->disk())->url($path);
    }

    /** Substitui o avatar atual (removendo o anterior, se for um upload nosso). */
    public function replace(?string $currentUrl, UploadedFile $file): string
    {
        if ($currentUrl && str_contains($currentUrl, '/avatars/')) {
            Storage::disk($this->disk())->delete('avatars/'.Str::after($currentUrl, '/avatars/'));
        }

        return $this->store($file);
    }
}
