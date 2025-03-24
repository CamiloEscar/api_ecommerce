<?php

namespace App\Http\Controllers\Admin\Product;

use App\Http\Controllers\Controller;
use App\Models\Product\ProductVariation;
use Illuminate\Http\Request;

class ProductVariationsController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $is_valid_variation = null;
        if ($request->propertie_id) {
            $is_valid_variation = ProductVariation::where("product_id", $request->product_id)
                ->where("attribute_id", $request->attribute_id)

                ->where("propertie_id", $request->propertie_id)
                ->first();
        } else {


            $is_valid_variation = ProductVariation::where("product_id", $request->product_id)
                ->where("attribute_id", $request->attribute_id)
                ->where("value_add", $request->value_add)
                ->first();
        }
        if ($is_valid_variation) {
            return response()->json(["message" => "Ya existe una variación con estas características"], 403);
        }

        $product_variation = ProductVariation::create($request->all());
        return response()->json($product_variation, 200);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $is_valid_variation = null;
        if ($request->propertie_id) {
            $is_valid_variation = ProductVariation::where("product_id", $request->product_id)
                ->where("id", "<>", $id)
                ->where("attribute_id", $request->attribute_id)

                ->where("propertie_id", $request->propertie_id)
                ->first();
        } else {


            $is_valid_variation = ProductVariation::where("product_id", $request->product_id)
                ->where("id", "<>", $id)
                ->where("attribute_id", $request->attribute_id)
                ->where("value_add", $request->value_add)
                ->first();
        }
        if ($is_valid_variation) {
            return response()->json(["message" => "Ya existe una variación con estas características"], 403);
        }

        $product_variation = ProductVariation::findOrFail($id);
        $product_variation->update($request->all());
        return response()->json($product_variation, 200);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $product_variation = ProductVariation::findOrFail($id);
        $product_variation->delete();
        //TODO: cololcar una validacion si el producto esta en carrito de compra o en el pedido
        return response()->json($product_variation, 200);

    }
}
