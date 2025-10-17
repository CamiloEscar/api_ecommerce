<?php

namespace App\Http\Controllers\Admin\Costo;

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
    public function index(Request $request)
    {
        $costos = Costo::where('code', 'like', '%' . $request->search . '%')->orderby('id', 'desc')->paginate(25);

        return response()->json([
            "total" => $costos->total(),
            "costos" => CostoCollection::make($costos),  //pasamos la colecion, ya que pasamos la lista de registro
        ]);
    }

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
                    "imagen" => env("APP_URL") . "storage/" . $product->imagen
                ];
            }),
            "categories" => $categories->map(function ($categorie) {
                return [
                    "id" => $categorie->id,
                    "name" => $categorie->name,
                    "imagen" => env("APP_URL") . "storage/" . $categorie->imagen
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
