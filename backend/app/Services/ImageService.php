<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\Facades\Image;

class ImageService
{
    /**
     * Allowed image MIME types
     */
    protected array $allowedMimeTypes = [
        'image/jpeg',
        'image/png',
        'image/jpg',
        'image/webp',
    ];

    /**
     * Maximum file size in KB
     */
    protected int $maxFileSize = 5120; // 5MB

    /**
     * Upload and process an image
     */
    public function upload(
        UploadedFile $file,
        string $directory = 'images',
        ?int $maxWidth = 1920,
        ?int $maxHeight = 1080,
        int $quality = 85
    ): string {
        $this->validateImage($file);

        $filename = $this->generateFilename($file);
        $path = "{$directory}/{$filename}";

        // Process and optimize image
        $image = Image::make($file);

        // Resize if needed
        if ($maxWidth || $maxHeight) {
            $image->resize($maxWidth, $maxHeight, function ($constraint) {
                $constraint->aspectRatio();
                $constraint->upsize();
            });
        }

        // Encode with quality
        $encoded = $image->encode($file->getClientOriginalExtension(), $quality);

        // Store the image
        Storage::disk('public')->put($path, $encoded);

        Log::info('Image uploaded', [
            'original_name' => $file->getClientOriginalName(),
            'filename' => $filename,
            'path' => $path,
            'size' => $file->getSize(),
        ]);

        return $path;
    }

    /**
     * Upload and create multiple sizes (thumbnail, medium, large)
     */
    public function uploadMultipleSizes(
        UploadedFile $file,
        string $directory = 'images'
    ): array {
        $this->validateImage($file);

        $filename = $this->generateFilename($file);
        $image = Image::make($file);

        $sizes = [
            'thumbnail' => ['width' => 200, 'height' => 200, 'quality' => 80],
            'medium' => ['width' => 800, 'height' => 600, 'quality' => 85],
            'large' => ['width' => 1920, 'height' => 1080, 'quality' => 90],
        ];

        $paths = [];

        foreach ($sizes as $size => $config) {
            $sizeFilename = "{$size}_{$filename}";
            $path = "{$directory}/{$sizeFilename}";

            $resizedImage = clone $image;
            $resizedImage->resize(
                $config['width'],
                $config['height'],
                function ($constraint) {
                    $constraint->aspectRatio();
                    $constraint->upsize();
                }
            );

            $encoded = $resizedImage->encode(
                $file->getClientOriginalExtension(),
                $config['quality']
            );

            Storage::disk('public')->put($path, $encoded);

            $paths[$size] = $path;
        }

        Log::info('Multiple image sizes uploaded', [
            'original_name' => $file->getClientOriginalName(),
            'paths' => $paths,
        ]);

        return $paths;
    }

    /**
     * Delete an image
     */
    public function delete(string $path): bool
    {
        if (Storage::disk('public')->exists($path)) {
            Storage::disk('public')->delete($path);

            Log::info('Image deleted', ['path' => $path]);

            return true;
        }

        return false;
    }

    /**
     * Delete multiple image sizes
     */
    public function deleteMultipleSizes(array $paths): void
    {
        foreach ($paths as $path) {
            $this->delete($path);
        }
    }

    /**
     * Validate uploaded image
     */
    protected function validateImage(UploadedFile $file): void
    {
        if (!$file->isValid()) {
            throw new \InvalidArgumentException('Invalid file upload');
        }

        if (!in_array($file->getMimeType(), $this->allowedMimeTypes)) {
            throw new \InvalidArgumentException(
                'Invalid file type. Allowed types: ' . implode(', ', $this->allowedMimeTypes)
            );
        }

        if ($file->getSize() > $this->maxFileSize * 1024) {
            throw new \InvalidArgumentException(
                "File size exceeds maximum allowed size of {$this->maxFileSize}KB"
            );
        }
    }

    /**
     * Generate unique filename
     */
    protected function generateFilename(UploadedFile $file): string
    {
        $extension = $file->getClientOriginalExtension();
        $timestamp = now()->format('YmdHis');
        $random = Str::random(8);

        return "{$timestamp}_{$random}.{$extension}";
    }

    /**
     * Get image URL
     */
    public function getUrl(string $path): string
    {
        return Storage::disk('public')->url($path);
    }

    /**
     * Convert image to WebP format
     */
    public function convertToWebP(string $path, int $quality = 85): string
    {
        if (!Storage::disk('public')->exists($path)) {
            throw new \InvalidArgumentException('Image not found');
        }

        $image = Image::make(Storage::disk('public')->path($path));

        $webpPath = preg_replace('/\.(jpg|jpeg|png)$/i', '.webp', $path);

        $encoded = $image->encode('webp', $quality);

        Storage::disk('public')->put($webpPath, $encoded);

        Log::info('Image converted to WebP', [
            'original_path' => $path,
            'webp_path' => $webpPath,
        ]);

        return $webpPath;
    }

    /**
     * Create a square thumbnail with crop
     */
    public function createSquareThumbnail(
        UploadedFile $file,
        string $directory = 'thumbnails',
        int $size = 200,
        int $quality = 80
    ): string {
        $this->validateImage($file);

        $filename = $this->generateFilename($file);
        $path = "{$directory}/{$filename}";

        $image = Image::make($file);

        // Crop to square from center
        $image->fit($size, $size);

        $encoded = $image->encode($file->getClientOriginalExtension(), $quality);

        Storage::disk('public')->put($path, $encoded);

        return $path;
    }
}
