<?php

namespace App\Service;

use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\String\Slugger\SluggerInterface;

class SaleImageUploader
{
    private const MAX_IMAGES = 5;
    private const MAX_FILE_SIZE = 5242880; // 5MB
    private const ALLOWED_MIME_TYPES = [
        'image/jpeg',
        'image/png',
        'image/gif',
        'image/webp'
    ];

    public function __construct(
        private readonly string $targetDirectory,
        private readonly SluggerInterface $slugger
    ) {
    }

    /**
     * Upload une image et retourne le chemin
     * 
     * @throws FileException
     */
    public function upload(UploadedFile $file): string
    {
        // Validation
        $this->validateFile($file);

        // Générer un nom de fichier sécurisé
        $originalFilename = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        $safeFilename = $this->slugger->slug($originalFilename)->lower();
        $newFilename = $safeFilename . '-' . uniqid() . '.' . $file->guessExtension();

        try {
            $file->move($this->targetDirectory, $newFilename);
        } catch (FileException $e) {
            throw new FileException('Erreur lors de l\'upload de l\'image : ' . $e->getMessage());
        }

        return 'uploads/sales/' . $newFilename;
    }

    /**
     * Upload plusieurs images (max 5)
     * 
     * @param UploadedFile[] $files
     * @return string[]
     * @throws FileException
     */
    public function uploadMultiple(array $files): array
    {
        if (count($files) > self::MAX_IMAGES) {
            throw new FileException('Vous ne pouvez pas uploader plus de ' . self::MAX_IMAGES . ' images.');
        }

        $paths = [];
        foreach ($files as $file) {
            if ($file instanceof UploadedFile) {
                $paths[] = $this->upload($file);
            }
        }

        return $paths;
    }

    /**
     * Valide un fichier
     * 
     * @throws FileException
     */
    private function validateFile(UploadedFile $file): void
    {
        // Vérifier la taille
        if ($file->getSize() > self::MAX_FILE_SIZE) {
            throw new FileException('Le fichier est trop volumineux. Taille maximum : 5MB.');
        }

        // Vérifier le type MIME
        $mimeType = $file->getMimeType();
        if (!in_array($mimeType, self::ALLOWED_MIME_TYPES)) {
            throw new FileException('Type de fichier non autorisé. Types acceptés : JPEG, PNG, GIF, WebP.');
        }

        // Vérifier l'extension
        $extension = strtolower($file->guessExtension());
        $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        if (!in_array($extension, $allowedExtensions)) {
            throw new FileException('Extension de fichier non autorisée.');
        }
    }

    /**
     * Supprime un fichier
     */
    public function delete(string $path): bool
    {
        $fullPath = $this->targetDirectory . '/' . basename($path);
        if (file_exists($fullPath)) {
            return unlink($fullPath);
        }
        return false;
    }

    public function getTargetDirectory(): string
    {
        return $this->targetDirectory;
    }
}

