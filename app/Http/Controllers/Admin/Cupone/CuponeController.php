<?php

namespace App\Http\Controllers\Admin\Cupone;

use App\Http\Controllers\Controller;
use App\Models\Cupone\Cupone;
use App\Models\Cupone\CuponeBrand;
use App\Models\Cupone\CuponeCategorie;
use App\Models\Cupone\CuponeProduct;
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
            "cupones" => $cupones,
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
                "product_id" => $categorie_selec["id"],
            ]);
        }
        foreach ($request->brand_selected as $key => $brand_selec) {
            CuponeBrand::create([
                "cupone_id" => $CUPONE->id,
                "product_id" => $brand_selec["id"],
            ]);
        }

        return response()->json([
            "message" => 200, "message_text" => "Cupón creado correctamente"
        ]);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $CUPONE = Cupone::findOrFail($id);

        return response()->json([
            "cupone" => $CUPONE,
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

        $IS_EXIST = Cupone::where('code', $request->code)->where("id","<>", $id)->first(); //first para que se muestre al menos una coincidencia

        if ($IS_EXIST) {
            return response()->json([
                "message" => 403,
                "message_text" => "El cupon ya existe"
            ]);
        }

        $CUPONE = Cupone::findOrFail($id);
        $CUPONE->update($request->all());

        foreach ($CUPONE->categories() as $key => $categorie) {
            $categorie->delete();
        }
        foreach ($CUPONE->products() as $key => $product) {
            $product->delete();
        }
        foreach ($CUPONE->brands() as $key => $brand) {
            $brand->delete();
        }

        foreach ($request->product_selected as $key => $product_selec) {
            CuponeProduct::create([
                "cupone_id" => $CUPONE->id,
                "product_id" => $product_selec["id"],
            ]);
        }
        foreach ($request->categorie_selected as $key => $categorie_selec) {
            CuponeCategorie::create([
                "cupone_id" => $CUPONE->id,
                "product_id" => $categorie_selec["id"],
            ]);
        }
        foreach ($request->brand_selected as $key => $brand_selec) {
            CuponeBrand::create([
                "cupone_id" => $CUPONE->id,
                "product_id" => $brand_selec["id"],
            ]);
        }

        return response()->json([
            "message" => 200, "message_text" => "Cupón creado correctamente"
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
            "message" => 200, "message_text" => "Cupón eliminado correctamente"
        ]);
    }
}
