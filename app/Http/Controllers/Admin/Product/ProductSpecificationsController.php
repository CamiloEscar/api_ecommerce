<?php

namespace App\Http\Controllers\Admin\Product;

use App\Http\Controllers\Controller;
use App\Models\Product\ProductSpecification;
use Illuminate\Http\Request;

class ProductSpecificationsController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $product_id = $request->product_id;

        $specifications = ProductSpecification::where('product_id', $product_id)->orderBy("id", "desc")->get();

        return response()->json([
            "$specifications" => $specifications->map(function ($specification) {
                return [
                    'product_id' => $specification->product_id,
                    'attribute_id' => $specification->attribute_id,
                    //relaciones
                    "attribute" => $specification->attribute ? [
                        "name" => $specification->attribute->name,
                        "type_attribute" => $specification->attribute->type_attribute,
                    ] : NULL,
                    'propertie_id' => $specification->propertie_id,
                    //relaciones
                    "propertie" => $specification->propertie ? [
                        "name" => $specification->propertie->name,
                        "code" => $specification->propertie->code,
                    ] : NULL,

                    'value_add' => $specification->value_add,
                ];
            })

        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $is_valid_specification = null;
        if ($request->propertie_id) {
            $is_valid_specification = ProductSpecification::where("product_id", $request->product_id)
                ->where("attribute_id", $request->attribute_id)

                ->where("propertie_id", $request->propertie_id)
                ->first();
        } else {


            $is_valid_specification = ProductSpecification::where("product_id", $request->product_id)
                ->where("attribute_id", $request->attribute_id)
                ->where("value_add", $request->value_add)
                ->first();
        }
        if ($is_valid_specification) {
            return response()->json(["message" => "Ya existe una especificacion con estas características"], 403);
        }

        $product_specification = ProductSpecification::create($request->all());
        return response()->json(
            [
                "message" => 200,
                "specification" => [
                    response(
                        [
                            'product_id' => $product_specification->product_id,
                            'attribute_id' => $product_specification->attribute_id,
                            //relaciones
                            "attribute" => $product_specification->attribute ? [
                                "name" => $product_specification->attribute->name,
                                "type_attribute" => $product_specification->attribute->type_attribute,
                            ] : NULL,
                            'propertie_id' => $product_specification->propertie_id,
                            //relaciones
                            "propertie" => $product_specification->propertie ? [
                                "name" => $product_specification->propertie->name,
                                "code" => $product_specification->propertie->code,
                            ] : NULL,

                            'value_add' => $product_specification->value_add,
                        ]
                    )
                ]
            ]
        );
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
        $is_valid_specification = null;
        if ($request->propertie_id) {
            $is_valid_specification = ProductSpecification::where("product_id", $request->product_id)
                ->where("id", "<>", $id)
                ->where("attribute_id", $request->attribute_id)

                ->where("propertie_id", $request->propertie_id)
                ->first();
        } else {


            $is_valid_specification = ProductSpecification::where("product_id", $request->product_id)
                ->where("id", "<>", $id)
                ->where("attribute_id", $request->attribute_id)
                ->where("value_add", $request->value_add)
                ->first();
        }
        if ($is_valid_specification) {
            return response()->json(["message" => "Ya existe una especificacion con estas características"], 403);
        }

        $product_specification = ProductSpecification::findOrFail($id);
        $product_specification->update($request->all());
        return response()->json(
            [
                "message" => 200,
                "specification" => [
                    response(
                        [
                            'product_id' => $product_specification->product_id,
                            'attribute_id' => $product_specification->attribute_id,
                            //relaciones
                            "attribute" => $product_specification->attribute ? [
                                "name" => $product_specification->attribute->name,
                                "type_attribute" => $product_specification->attribute->type_attribute,
                            ] : NULL,
                            'propertie_id' => $product_specification->propertie_id,
                            //relaciones
                            "propertie" => $product_specification->propertie ? [
                                "name" => $product_specification->propertie->name,
                                "code" => $product_specification->propertie->code,
                            ] : NULL,

                            'value_add' => $product_specification->value_add,
                            'add_price' => $product_specification->add_price,
                            'stock' => $product_specification->stock,
                        ]
                    )
                ]
            ]
        );
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $product_specification = ProductSpecification::findOrFail($id);
        $product_specification->delete();
        //TODO: cololcar una validacion si el producto esta en carrito de compra o en el pedido
        return response()->json($product_specification, 200);
    }
}
