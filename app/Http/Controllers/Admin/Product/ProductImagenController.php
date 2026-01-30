<?php

namespace App\Http\Controllers\Admin\Product;

use App\Http\Controllers\Controller;
use App\Models\Product\ProductImage;
use App\Services\ImageService;
use Illuminate\Http\Request;

class ProductImagenController extends Controller
{
    protected $imageService;

    public function __construct(ImageService $imageService)
    {
        $this->imageService = $imageService;
    }

    public function store(Request $request)
    {
        $product_id = $request->product_id;

        if ($request->hasFile("imagen")) {
            $cloudinaryUrl = $this->imageService->upload($request->file("imagen"), 'products');

            $product_imagen = ProductImage::create([
                "imagen" => $cloudinaryUrl,
                "product_id" => $product_id,
            ]);

            return response()->json([
                "imagen" => [
                    "id" => $product_imagen->id,
                    "imagen" => $product_imagen->imagen
                ],
                "message" => 200,
            ]);
        }

        return response()->json(["message" => "No se envió ninguna imagen"], 400);
    }

    public function destroy(string $id)
    {
        $product_image = ProductImage::findOrFail($id);

        if ($product_image->imagen) {
            $publicId = $this->imageService->getPublicIdFromUrl($product_image->imagen);
            if ($publicId) {
                $this->imageService->delete($publicId);
            }
        }

        $product_image->delete();

        return response()->json(["message" => 200]);
    }
}
