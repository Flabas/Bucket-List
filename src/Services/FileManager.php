<?php

namespace App\Services;

use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\String\Slugger\SluggerInterface;

class FileManager
{
    public function __construct(private SluggerInterface $slugger) {}


    public function upload(UploadedFile $file, string $dir, string $name, string $oldResourceToDelete = ''): string
    {
        $name = $this->slugger->slug($name) . '-' . uniqid() . '.' . $file->guessExtension();
        $file->move($dir, $name);
        if ($oldResourceToDelete) {
            $this->delete($dir, $oldResourceToDelete);
        }
        return $name;
    }

    public function delete(string $dir, string $name): bool
    {
        if (\file_exists($dir . '/' . $name)) {
            unlink($dir . '/' . $name);
            return true;
        }

        return false;
    }

}