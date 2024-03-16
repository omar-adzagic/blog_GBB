<?php

namespace App\Service;

use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\String\Slugger\SluggerInterface;

class FileUploader
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
}
