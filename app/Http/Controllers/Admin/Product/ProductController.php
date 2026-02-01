<?php

namespace App\Http\Controllers\Admin\Product;

use App\Http\Controllers\Controller;
use App\Http\Resources\Product\ProductCollection;
use App\Http\Resources\Product\ProductResource;
use App\Models\Product\Brand;
use App\Models\Product\Categorie;
use App\Models\Product\Product;
use App\Models\Product\ProductImage;
use App\Services\ImageService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ProductController extends Controller
{
    protected $imageService;

    public function __construct(ImageService $imageService)
    {
        $this->imageService = $imageService;
    }

    /**
     * @OA\Post(
     *   path="/api/admin/products/index",
     *   tags={"Admin - Products"},
     *   summary="Listado de productos con filtros",
     *   security={{"bearerAuth":{}}},
     *   @OA\RequestBody(
     *     @OA\JsonContent(
     *       @OA\Property(property="search", type="string"),
     *       @OA\Property(property="status", type="boolean")
     *     )
     *   ),
     *   @OA\Response(response=200, description="OK")
     * )
     */
    public function index(Request $request)
    {
        $search = $request->search;
        $categorie_first_id = $request->categorie_first_id;
        $categorie_second_id = $request->categorie_second_id;
        $categorie_third_id = $request->categorie_third_id;
        $brand_id = $request->brand_id;

        $products = Product::filterAdvanceProduct($search, $categorie_first_id, $categorie_second_id, $categorie_third_id, $brand_id)
            ->orderBy("id")->paginate(25);

        return response()->json([
            "total" => $products->total(),
            "products" => ProductCollection::make($products)
        ]);
    }

    /**
     * @OA\Get(
     *   path="/api/admin/products/config",
     *   tags={"Admin - Products"},
     *   summary="Configuración de productos",
     *   security={{"bearerAuth":{}}},
     *   @OA\Response(response=200, description="OK")
     * )
     */
    public function config()
    {
        $categories_first = Categorie::where("state", 1)->where("categorie_second_id", NULL)->where("categorie_third_id", NULL)->get();
        $categories_seconds = Categorie::where("state", 1)->where("categorie_second_id", "<>", NULL)->where("categorie_third_id", NULL)->get();
        $categories_thirds = Categorie::where("state", 1)->where("categorie_second_id", "<>", NULL)->where("categorie_third_id", "<>", NULL)->get();

        $brands = Brand::where("state", 1)->get();

        return response()->json([
            "categorie_first" => $categories_first,
            "categorie_seconds" => $categories_seconds,
            "categorie_thirds" => $categories_thirds,
            "brands" => $brands
        ]);
    }

    public function store(Request $request)
    {
        $isValid = Product::where("title", $request->title)->first();
        if ($isValid) {
            return response()->json([
                "message" => "El producto ya existe"
            ], 403);
        }

        // ✅ Subir portada a Cloudinary
        if ($request->hasFile("portada")) {
            $cloudinaryUrl = $this->imageService->upload($request->file("portada"), 'products');
            $request->request->add(["imagen" => $cloudinaryUrl]);
        }

        $request->request->add(["slug" => Str::slug($request->title)]);
        $request->request->add(["tags" => $request->multiselect]);

        $product = Product::create($request->all());

        return response()->json([
            "message" => 200,
            "product" => $product
        ]);
    }

    /**
     * @OA\Post(
     *   path="/api/admin/products/imagens",
     *   tags={"Admin - Products"},
     *   summary="Subir imagen de producto",
     *   security={{"bearerAuth":{}}},
     *   @OA\RequestBody(
     *     required=true,
     *     @OA\MediaType(
     *       mediaType="multipart/form-data",
     *       @OA\Schema(
     *         required={"image"},
     *         @OA\Property(
     *           property="image",
     *           type="string",
     *           format="binary"
     *         )
     *       )
     *     )
     *   ),
     *   @OA\Response(response=200, description="Imagen subida")
     * )
     */
    public function imagens(Request $request)
    {
        $product_id = $request->product_id;

        // ✅ Subir a Cloudinary
        if ($request->hasFile("imagen_add")) {
            $cloudinaryUrl = $this->imageService->upload($request->file("imagen_add"), 'products');

            $product_imagen = ProductImage::create([
                "imagen" => $cloudinaryUrl,
                "product_id" => $product_id,
            ]);

            return response()->json([
                "imagen" => [
                    "id" => $product_imagen->id,
                    "imagen" => $product_imagen->imagen // ✅ URL completa de Cloudinary
                ],
                "message" => 200,
            ]);
        }

        return response()->json([
            "message" => "No se envió ninguna imagen"
        ], 400);
    }

    public function show(string $id)
    {
        $product = Product::findOrFail($id);

        return response()->json([
            "product" => ProductResource::make($product)
        ]);
    }

    public function update(Request $request, string $id)
    {
        $isValid = Product::where("id", "<>", $id)->where("title", $request->title)->first();
        if ($isValid) {
            return response()->json([
                "message" => "El producto ya existe"
            ], 403);
        }

        $product = Product::findOrFail($id);

        // ✅ Actualizar portada en Cloudinary
        if ($request->hasFile("portada")) {
            // Eliminar imagen anterior
            if ($product->imagen) {
                $publicId = $this->imageService->getPublicIdFromUrl($product->imagen);
                if ($publicId) {
                    $this->imageService->delete($publicId);
                }
            }

            $cloudinaryUrl = $this->imageService->upload($request->file("portada"), 'products');
            $request->request->add(["imagen" => $cloudinaryUrl]);
        }

        $request->request->add(["slug" => Str::slug($request->title)]);
        $request->request->add(["tags" => $request->multiselect]);

        // $product->update($request->all());
        $data = $request->all();

$data['cost']  = (int) $request->cost;
$data['state'] = (int) $request->state;

$product->update($data);


        return response()->json([
            "message" => 200,
            "product" => $product
        ]);
    }

    public function destroy(string $id)
    {
        $product = Product::findOrFail($id);

        // ✅ Eliminar imagen principal de Cloudinary
        if ($product->imagen) {
            $publicId = $this->imageService->getPublicIdFromUrl($product->imagen);
            if ($publicId) {
                $this->imageService->delete($publicId);
            }
        }

        // ✅ Eliminar todas las imágenes adicionales
        foreach ($product->images as $image) {
            $publicId = $this->imageService->getPublicIdFromUrl($image->imagen);
            if ($publicId) {
                $this->imageService->delete($publicId);
            }
        }

        $product->delete();

        return response()->json([
            "message" => 200,
            "text" => "Producto eliminado con éxito"
        ]);
    }

    /**
     * @OA\Delete(
     *   path="/api/admin/products/imagens/{id}",
     *   tags={"Admin - Products"},
     *   summary="Eliminar imagen",
     *   security={{"bearerAuth":{}}},
     *   @OA\Parameter(
     *     name="id",
     *     in="path",
     *     required=true,
     *     @OA\Schema(type="integer")
     *   ),
     *   @OA\Response(response=204, description="Eliminada")
     * )
     */
    public function delete_imagens(string $id)
    {
        $product_image = ProductImage::findOrFail($id);

        // ✅ Eliminar de Cloudinary
        if ($product_image->imagen) {
            $publicId = $this->imageService->getPublicIdFromUrl($product_image->imagen);
            if ($publicId) {
                $this->imageService->delete($publicId);
            }
        }

        $product_image->delete();

        return response()->json([
            "message" => 200,
            "text" => "Imagen eliminada con éxito"
        ]);
    }
}
