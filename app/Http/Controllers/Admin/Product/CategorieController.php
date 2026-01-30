<?php

namespace App\Http\Controllers\Admin\Product;

use App\Http\Controllers\Controller;
use App\Http\Resources\Product\CategorieCollection;
use App\Models\Product\Categorie;
use App\Services\ImageService;
use Illuminate\Http\Request;

class CategorieController extends Controller
{
    protected $imageService;

    public function __construct(ImageService $imageService)
    {
        $this->imageService = $imageService;
    }

    /**
     * @OA\Get(
     *   path="/api/admin/categories",
     *   tags={"Admin - Categories"},
     *   summary="Listado de categorías",
     *   security={{"bearerAuth":{}}},
     *   @OA\Response(response=200, description="OK")
     * )
     */
    public function index(Request $request)
    {
        $search = $request->search;

        $categories = Categorie::where("name", "like", "%" . $search . "%")->orderBy("id", "desc")->paginate(25);

        return response()->json([
            "total" => $categories->total(),
            "categories" => CategorieCollection::make($categories)
        ]);
    }

    /**
     * @OA\Get(
     *   path="/api/admin/categories/config",
     *   tags={"Admin - Categories"},
     *   summary="Configuración de categorías",
     *   security={{"bearerAuth":{}}},
     *   @OA\Response(response=200, description="OK")
     * )
     */
    public function config()
    {
        $categories_first = Categorie::where("categorie_second_id", NULL)->where("categorie_third_id", NULL)->get();
        $categories_seconds = Categorie::where("categorie_second_id", "<>", NULL)->where("categorie_third_id", NULL)->get();

        return response()->json([
            "categorie_first" => $categories_first,
            "categorie_seconds" => $categories_seconds
        ]);
    }

    /**
     * @OA\Post(
     *   path="/api/admin/categories",
     *   tags={"Admin - Categories"},
     *   summary="Crear categoría",
     *   security={{"bearerAuth":{}}},
     *   @OA\RequestBody(
     *     required=true,
     *     @OA\JsonContent(
     *       required={"name"},
     *       @OA\Property(property="name", type="string", example="Electrónica"),
     *       @OA\Property(property="status", type="boolean", example=true)
     *     )
     *   ),
     *   @OA\Response(response=201, description="Creado")
     * )
     */
    public function store(Request $request)
    {
        $is_exists = Categorie::where("name", $request->name)->first();
        if ($is_exists) {
            return response()->json(["message" => "La categoría ya existe"], 403);
        }

        $data = $request->except('imagen');

        // ✅ Subir a Cloudinary
        if ($request->hasFile("imagen")) {
            $file = $request->file("imagen");

            if ($file->getClientOriginalExtension() === 'tmp') {
                return response()->json(["message" => "No se permiten archivos temporales"], 403);
            }

            $data['imagen'] = $this->imageService->upload($file, 'categories');
        }

        $categorie = Categorie::create($data);

        return response()->json([
            "message" => 200,
            "categorie" => $categorie
        ]);
    }

    /**
     * @OA\Get(
     *   path="/api/admin/categories/{id}",
     *   tags={"Admin - Categories"},
     *   summary="Detalle de categoría",
     *   security={{"bearerAuth":{}}},
     *   @OA\Parameter(
     *     name="id",
     *     in="path",
     *     required=true,
     *     @OA\Schema(type="integer")
     *   ),
     *   @OA\Response(response=200, description="OK")
     * )
     */
    public function show(string $id)
    {
        $categorie = Categorie::findOrFail($id);

        return response()->json(["categorie" => $categorie]);
    }

    /**
     * @OA\Put(
     *   path="/api/admin/categories/{id}",
     *   tags={"Admin - Categories"},
     *   summary="Actualizar categoría",
     *   security={{"bearerAuth":{}}},
     *   @OA\Parameter(
     *     name="id",
     *     in="path",
     *     required=true,
     *     @OA\Schema(type="integer")
     *   ),
     *   @OA\RequestBody(
     *     @OA\JsonContent(
     *       @OA\Property(property="name", type="string"),
     *       @OA\Property(property="status", type="boolean")
     *     )
     *   ),
     *   @OA\Response(response=200, description="Actualizado")
     * )
     */
    public function update(Request $request, string $id)
    {
        $is_exists = Categorie::where("id", "<>", $id)->where("name", $request->name)->first();
        if ($is_exists) {
            return response()->json(["message" => "La categoría ya existe"], 403);
        }

        $categorie = Categorie::findOrFail($id);
        $data = $request->except('imagen');

        // ✅ Actualizar imagen en Cloudinary
        if ($request->hasFile("imagen")) {
            $file = $request->file("imagen");

            if ($file->getClientOriginalExtension() === 'tmp') {
                return response()->json(["message" => "No se permiten archivos temporales"], 403);
            }

            // Eliminar imagen anterior de Cloudinary
            if ($categorie->imagen) {
                $publicId = $this->imageService->getPublicIdFromUrl($categorie->imagen);
                if ($publicId) {
                    $this->imageService->delete($publicId);
                }
            }

            $data['imagen'] = $this->imageService->upload($file, 'categories');
        }

        $categorie->update($data);

        return response()->json([
            "message" => 200,
            "categorie" => $categorie
        ]);
    }

    /**
     * @OA\Delete(
     *   path="/api/admin/categories/{id}",
     *   tags={"Admin - Categories"},
     *   summary="Eliminar categoría",
     *   security={{"bearerAuth":{}}},
     *   @OA\Parameter(
     *     name="id",
     *     in="path",
     *     required=true,
     *     @OA\Schema(type="integer")
     *   ),
     *   @OA\Response(response=204, description="Eliminado")
     * )
     */
    public function destroy(string $id)
    {
        $categorie = Categorie::findOrFail($id);

        // Validar que la categoría no esté en ningún producto
        if ($categorie->product_categorie_firsts()->count() > 0 ||
            $categorie->product_categorie_seconds()->count() > 0 ||
            $categorie->product_categorie_thirds()->count() > 0) {
            return response()->json([
                "message" => 403,
                "message_text" => "No se puede eliminar la categoría porque está en uso en productos"
            ]);
        }

        // ✅ Eliminar imagen de Cloudinary
        if ($categorie->imagen) {
            $publicId = $this->imageService->getPublicIdFromUrl($categorie->imagen);
            if ($publicId) {
                $this->imageService->delete($publicId);
            }
        }

        $categorie->delete();

        return response()->json(["message" => 200]);
    }
}
