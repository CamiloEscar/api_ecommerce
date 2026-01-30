<?php

namespace App\Services;

use Cloudinary\Cloudinary;
use Illuminate\Http\UploadedFile;

class ImageService
{
    protected $cloudinary;

    public function __construct()
    {
        // ✅ Inicializar Cloudinary manualmente
        $this->cloudinary = new Cloudinary([
            'cloud' => [
                'cloud_name' => env('CLOUDINARY_CLOUD_NAME', 'devcam'),
                'api_key'    => env('CLOUDINARY_API_KEY', '735693454976424'),
                'api_secret' => env('CLOUDINARY_API_SECRET', 'osRfg5fCIE6_uhYw6wqHL8WWX9E'),
            ],
            'url' => [
                'secure' => true
            ]
        ]);
    }

    public function upload(UploadedFile $file, string $folder = 'products'): string
    {
        try {
            $result = $this->cloudinary->uploadApi()->upload($file->getRealPath(), [
                'folder' => $folder,
                'transformation' => [
                    'width' => 800,
                    'height' => 800,
                    'crop' => 'limit',
                    'quality' => 'auto',
                    'fetch_format' => 'auto'
                ]
            ]);

            return $result['secure_url'];
        } catch (\Exception $e) {
            \Log::error('Error uploading to Cloudinary: ' . $e->getMessage());
            throw new \Exception('Error al subir la imagen: ' . $e->getMessage());
        }
    }

    public function delete(string $publicId): bool
    {
        try {
            $result = $this->cloudinary->uploadApi()->destroy($publicId);
            return $result['result'] === 'ok';
        } catch (\Exception $e) {
            \Log::error('Error deleting from Cloudinary: ' . $e->getMessage());
            return false;
        }
    }

    public function getPublicIdFromUrl(string $url): ?string
    {
        if (empty($url)) {
            return null;
        }

        preg_match('/\/upload\/v\d+\/(.+)\.\w+$/', $url, $matches);
        return $matches[1] ?? null;
    }
}
