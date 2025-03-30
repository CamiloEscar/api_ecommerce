<?php

namespace App\Http\Controllers\Admin\Cupone;

use App\Http\Controllers\Controller;
use App\Http\Resources\Cupone\CuponeCollection;
use App\Http\Resources\Cupone\CuponeResource;
use App\Models\Cupone\Cupone;
use App\Models\Cupone\CuponeBrand;
use App\Models\Cupone\CuponeCategorie;
use App\Models\Cupone\CuponeProduct;
use App\Models\Product\Brand;
use App\Models\Product\Categorie;
use App\Models\Product\Product;
use Illuminate\Http\Request;

class CuponeController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $cupones = Cupone::where('code', 'like', '%' . $request->search . '%')->orderby('id', 'desc')->paginate(25);

        return response()->json([
            "total" => $cupones->total(),
            "cupones" => CuponeCollection::make($cupones),  //pasamos la colecion, ya que pasamos la lista de registro
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
        //product_selected va a contener los productos seleccionados para el cupon, se envia desde el frontend
        //categorie_selected
        //brand_selected

        $IS_EXIST = Cupone::where('code', $request->code)->first(); //first para que se muestre al menos una coincidencia

        if ($IS_EXIST) {
            return response()->json([
                "message" => 403,
                "message_text" => "El cupon ya existe"
            ]);
        }

        $CUPONE = Cupone::create($request->all());

        foreach ($request->product_selected as $key => $product_selec) {
            CuponeProduct::create([
                "cupone_id" => $CUPONE->id,
                "product_id" => $product_selec["id"],
            ]);
        }
        foreach ($request->categorie_selected as $key => $categorie_selec) {
            CuponeCategorie::create([
                "cupone_id" => $CUPONE->id,
                "categorie_id" => $categorie_selec["id"],
            ]);
        }
        foreach ($request->brand_selected as $key => $brand_selec) {
            CuponeBrand::create([
                "cupone_id" => $CUPONE->id,
                "brand_id" => $brand_selec["id"],
            ]);
        }

        return response()->json([
            "message" => 200,
            "message_text" => "Cupón creado correctamente"
        ]);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $CUPONE = Cupone::findOrFail($id);

        return response()->json([
            "cupone" => CuponeResource::make($CUPONE)
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //product_selected va a contener los productos seleccionados para el cupon, se envia desde el frontend
        //categorie_selected
        //brand_selected
        $IS_EXIST = Cupone::where('code', $request->code)->where("id", "<>", $id)->first();

        if ($IS_EXIST) {
            return response()->json([
                "message" => 403,
                "message_text" => "El cupon ya existe"
            ]);
        }

        $CUPONE = Cupone::findOrFail($id);
        $CUPONE->update($request->all());

        // Delete existing relationships using the correct approach
        CuponeCategorie::where('cupone_id', $id)->delete();
        CuponeProduct::where('cupone_id', $id)->delete();
        CuponeBrand::where('cupone_id', $id)->delete();

        foreach ($request->product_selected as $key => $product_selec) {
            CuponeProduct::create([
                "cupone_id" => $CUPONE->id,
                "product_id" => $product_selec["id"],
            ]);
        }

        foreach ($request->categorie_selected as $key => $categorie_selec) {
            CuponeCategorie::create([
                "cupone_id" => $CUPONE->id,
                "categorie_id" => $categorie_selec["id"],
            ]);
        }

        foreach ($request->brand_selected as $key => $brand_selec) {
            CuponeBrand::create([
                "cupone_id" => $CUPONE->id,
                "brand_id" => $brand_selec["id"],
            ]);
        }

        return response()->json([
            "message" => 200,
            "message_text" => "Cupón actualizado correctamente" // Changed from "creado" to "actualizado"
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $CUPONE = Cupone::findOrFail($id);
        $CUPONE->delete();

        //TODO: cuando hay una compra relacionada con el cupon ya no se puede eliminar
        return response()->json([
            "message" => 200,
            "message_text" => "Cupón eliminado correctamente"
        ]);
    }
}
