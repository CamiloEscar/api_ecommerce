<?php

namespace App\Http\Controllers\Admin\Costo;

use App\Helpers\ImageHelper;

use App\Http\Controllers\Controller;
use App\Http\Resources\Costo\CostoCollection;
use App\Http\Resources\Costo\CostoResource;
use App\Models\Costo\Costo;
use App\Models\Costo\CostoBrand;
use App\Models\Costo\CostoCategorie;
use App\Models\Costo\CostoProduct;
use App\Models\Product\Brand;
use App\Models\Product\Categorie;
use App\Models\Product\Product;
use Illuminate\Http\Request;

class CostoController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    /**
     * @OA\Get(
     *   path="/api/admin/costoenvio",
     *   tags={"Admin - Costos de Envío"},
     *   summary="Listado de costos de envío",
     *   security={{"bearerAuth":{}}},
     *   @OA\Response(
     *     response=200,
     *     description="Listado obtenido correctamente"
     *   )
     * )
     */
    public function index(Request $request)
    {
        $costos = Costo::where('code', 'like', '%' . $request->search . '%')->orderby('id', 'desc')->paginate(25);

        return response()->json([
            "total" => $costos->total(),
            "costos" => CostoCollection::make($costos),  //pasamos la colecion, ya que pasamos la lista de registro
        ]);
    }

    /**
 * @OA\Get(
 *   path="/api/admin/costoenvio/config",
 *   tags={"Admin - Costos de Envío"},
 *   summary="Configuración de costos",
 *   security={{"bearerAuth":{}}},
 *   @OA\Response(response=200, description="OK")
 * )
 */
    public function config()
    { //extraemos los datos que necesitamos para el seleccionable de la lista de registros de cupones
        $products = Product::where("state", 2)->orderBy("id", "desc")->get();

        $categories = Categorie::where("state", 1)->where("categorie_second_id", NULL)
            ->where("categorie_third_id", NULL)
            ->orderBy("id", "desc")
            ->get();

        $brands = Brand::where("state", 1)->orderBy("id", "desc")->get();

        return response()->json([
            "products" => $products->map(function ($product) {
                return [
                    "id" => $product->id,
                    "title" => $product->title,
                    "slug" => $product->slug,
                    "imagen" => ImageHelper::getImageUrl($product->imagen)
                ];
            }),
            "categories" => $categories->map(function ($categorie) {
                return [
                    "id" => $categorie->id,
                    "name" => $categorie->name,
                    "imagen" => ImageHelper::getImageUrl($categorie->imagen)
                ];
            }),
            "brands" => $brands->map(function ($brand) {
                return [
                    "id" => $brand->id,
                    "name" => $brand->name,
                ];
            }),
        ]);
    }
    /**
     * Store a newly created resource in storage.
     */

    /**
     * @OA\Post(
     *   path="/api/admin/costoenvio",
     *   tags={"Admin - Costos de Envío"},
     *   summary="Crear costo de envío",
     *   security={{"bearerAuth":{}}},
     *   @OA\RequestBody(
     *     required=true,
     *     @OA\JsonContent(
     *       required={"code","type_discount","discount","type_count","type_costo","state"},
     *       @OA\Property(property="code", type="string", example="BUENOS AIRES"),
     *       @OA\Property(
     *         property="type_discount",
     *         type="integer",
     *         description="1 porcentaje, 2 monto fijo",
     *         example=1
     *       ),
     *       @OA\Property(property="discount", type="number", example=15),
     *       @OA\Property(
     *         property="type_count",
     *         type="integer",
     *         description="1 ilimitado, 2 limitado",
     *         example=1
     *       ),
     *       @OA\Property(property="num_use", type="integer", example=10),
     *       @OA\Property(
     *         property="type_costo",
     *         type="integer",
     *         description="1 producto, 2 categoría, 3 marca",
     *         example=2
     *       ),
     *       @OA\Property(
     *         property="state",
     *         type="integer",
     *         description="1 activo, 2 inactivo",
     *         example=1
     *       )
     *     )
     *   ),
     *   @OA\Response(response=201, description="Costo de envío creado")
     * )
     */
    public function store(Request $request)
    {
        $IS_EXIST = Costo::where('code', $request->code)->first(); //first para que se muestre al menos una coincidencia

        if ($IS_EXIST) {
            return response()->json([
                "message" => 403,
                "message_text" => "El costo ya existe"
            ]);
        }

        $COSTO = Costo::create($request->all());

        foreach ($request->product_selected as $key => $product_selec) {
            CostoProduct::create([
                "costo_id" => $COSTO->id,
                "product_id" => $product_selec["id"],
            ]);
        }
        foreach ($request->categorie_selected as $key => $categorie_selec) {
            CostoCategorie::create([
                "costo_id" => $COSTO->id,
                "categorie_id" => $categorie_selec["id"],
            ]);
        }
        foreach ($request->brand_selected as $key => $brand_selec) {
            CostoBrand::create([
                "costo_id" => $COSTO->id,
                "brand_id" => $brand_selec["id"],
            ]);
        }

        return response()->json([
            "message" => 200,
            "message_text" => "Costo de envio creado correctamente"
        ]);
    }

    /**
     * Display the specified resource.
     */
        /**
     * @OA\Get(
     *   path="/api/admin/costoenvio/{id}",
     *   tags={"Admin - Costos de Envío"},
     *   summary="Detalle de un costo de envío",
     *   security={{"bearerAuth":{}}},
     *   @OA\Parameter(
     *     name="id",
     *     in="path",
     *     required=true,
     *     example=1
     *   ),
     *   @OA\Response(response=200, description="Detalle obtenido")
     * )
     */
    public function show(string $id)
    {
        $COSTO = Costo::findOrFail($id);

        return response()->json([
            "costo" => CostoResource::make($COSTO)
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
        /**
     * @OA\Put(
     *   path="/api/admin/costoenvio/{id}",
     *   tags={"Admin - Costos de Envío"},
     *   summary="Actualizar costo de envío",
     *   security={{"bearerAuth":{}}},
     *   @OA\Parameter(
     *     name="id",
     *     in="path",
     *     required=true,
     *     example=1
     *   ),
     *   @OA\RequestBody(
     *     required=true,
     *     @OA\JsonContent(
     *       @OA\Property(property="discount", type="number", example=20),
     *       @OA\Property(property="state", type="integer", example=2)
     *     )
     *   ),
     *   @OA\Response(response=200, description="Costo de envío actualizado")
     * )
     */
    public function update(Request $request, string $id)
    {
        $IS_EXIST = Costo::where('code', $request->code)->where("id", "<>", $id)->first();

        if ($IS_EXIST) {
            return response()->json([
                "message" => 403,
                "message_text" => "El cupon ya existe"
            ]);
        }

        $COSTO = Costo::findOrFail($id);
        $COSTO->update($request->all());

        // Delete existing relationships using the correct approach
        CostoCategorie::where('costo_id', $id)->delete();
        CostoCategorie::where('costo_id', $id)->delete();
        CostoBrand::where('costo_id', $id)->delete();

        foreach ($request->product_selected as $key => $product_selec) {
            CostoCategorie::create([
                "costo_id" => $COSTO->id,
                "product_id" => $product_selec["id"],
            ]);
        }

        foreach ($request->categorie_selected as $key => $categorie_selec) {
            CostoCategorie::create([
                "costo_id" => $COSTO->id,
                "categorie_id" => $categorie_selec["id"],
            ]);
        }

        foreach ($request->brand_selected as $key => $brand_selec) {
            CostoBrand::create([
                "costo_id" => $COSTO->id,
                "brand_id" => $brand_selec["id"],
            ]);
        }

        return response()->json([
            "message" => 200,
            "message_text" => "Costo de envio actualizado correctamente" // Changed from "creado" to "actualizado"
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
        /**
     * @OA\Delete(
     *   path="/api/admin/costoenvio/{id}",
     *   tags={"Admin - Costos de Envío"},
     *   summary="Eliminar costo de envío",
     *   security={{"bearerAuth":{}}},
     *   @OA\Parameter(
     *     name="id",
     *     in="path",
     *     required=true,
     *     example=1
     *   ),
     *   @OA\Response(response=200, description="Costo eliminado")
     * )
     */
    public function destroy(string $id)
    {
        $COSTO = Costo::findOrFail($id);
        $COSTO->delete();

        //TODO: cuando hay una compra relacionada con el cupon ya no se puede eliminar
        return response()->json([
            "message" => 200,
            "message_text" => "Costo de envio eliminado correctamente"
        ]);
    }
}
