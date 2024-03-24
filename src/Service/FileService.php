<?php

namespace App\Service;

use Intervention\Image\Constraint;
use Intervention\Image\ImageManagerStatic as Image;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\String\Slugger\SluggerInterface;

class FileService
{
    private $targetDirectory;
    private $slugger;

    public function __construct(string $targetDirectory, SluggerInterface $slugger)
    {
        $this->targetDirectory = $targetDirectory;
        $this->slugger = $slugger;
    }

    public function upload(UploadedFile $file, string $subDirectory = ''): string
    {
        $targetDirectory = $this->getTargetDirectory();
        if (!empty($subDirectory)) {
            $targetDirectory .= '/' . trim($subDirectory, '/');
        }

        // Ensure the target directory exists
        if (!file_exists($targetDirectory)) {
            mkdir($targetDirectory, 0777, true);
        }

        $originalFilename = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        $safeFilename = $this->slugger->slug($originalFilename);
        $fileName = $safeFilename . '-' . uniqid() . '.' . $file->guessExtension();

        try {
            $file->move($targetDirectory, $fileName);
        } catch (FileException $e) {
            throw new \Exception($e->getMessage());
        }

        return $fileName;
    }

    public function deleteFile(string $filename, string $subDirectory = ''): void
    {
        $targetDirectory = $this->getTargetDirectory();
        if (!empty($subDirectory)) {
            $targetDirectory .= '/' . trim($subDirectory, '/');
        }

        $filePath = $targetDirectory . '/' . $filename;
        if (file_exists($filePath)) {
            unlink($filePath);
        }
    }

    public function getTargetDirectory(): string
    {
        return $this->targetDirectory;
    }

    public function resizeImage(string $path, int $width = 1024, int $height = 768): void
    {
        $filePath = $this->getTargetDirectory() . $path;

        if (!file_exists($filePath)) {
            return;
        }

        $image = Image::make($filePath);

        $image->resize($width, $height, function (Constraint $constraint) {
            $constraint->aspectRatio();
            $constraint->upsize();
        });

        $image->save();
    }
}
