<?php

namespace App\Helpers;

class ImageHelper
{
    /**
     * Format image URL - handles both Cloudinary URLs and local storage paths
     *
     * @param string|null $imagePath The image path or URL from database
     * @return string|null The complete image URL
     */
    public static function getImageUrl(?string $imagePath): ?string
    {
        if (empty($imagePath)) {
            return null;
        }

        // If it's already a complete URL (Cloudinary, http, https), return as is
        if (strpos($imagePath, 'http://') === 0 || strpos($imagePath, 'https://') === 0) {
            return $imagePath;
        }

        // Otherwise, it's a local storage path, prepend the storage URL
        return env('APP_URL') . 'storage/' . $imagePath;
    }
}
