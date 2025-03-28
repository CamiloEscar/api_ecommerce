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
                    'id' => $specification->id,
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
                            'id' => $product_specification->id,
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
    /**
 * Update the specified resource in storage.
 */
public function update(Request $request, string $id)
{
    // First, get the current specification to be updated
    $current_specification = ProductSpecification::findOrFail($id);

    $is_valid_specification = null;

    // For specifications with property_id
    if ($request->propertie_id) {
        // Check if another specification (not this one) has the same product, attribute and property
        $is_valid_specification = ProductSpecification::where("product_id", $request->product_id)
            ->where("id", "<>", $id) // Exclude the current specification
            ->where("attribute_id", $request->attribute_id)
            ->where("propertie_id", $request->propertie_id)
            ->first();
    }
    // For specifications with value_add
    else if ($request->value_add) {
        // For type 4 attributes (multi-select), we need special handling for JSON values
        if ($current_specification->attribute && $current_specification->attribute->type_attribute == 4) {
            // Get all other specifications for this product and attribute
            $other_specifications = ProductSpecification::where("product_id", $request->product_id)
                ->where("id", "<>", $id)
                ->where("attribute_id", $request->attribute_id)
                ->get();

            // Check each one manually for JSON equality
            foreach ($other_specifications as $spec) {
                if ($spec->value_add && $request->value_add) {
                    // Try to decode both values
                    try {
                        $requestValues = json_decode($request->value_add, true);
                        $specValues = json_decode($spec->value_add, true);

                        // If both are arrays, compare them regardless of order
                        if (is_array($requestValues) && is_array($specValues)) {
                            // Sort both arrays by ID if they contain objects with IDs
                            $sortById = function($items) {
                                usort($items, function($a, $b) {
                                    $idA = isset($a['id']) ? $a['id'] : $a;
                                    $idB = isset($b['id']) ? $b['id'] : $b;
                                    return $idA - $idB;
                                });
                                return $items;
                            };

                            $sortedRequest = $sortById($requestValues);
                            $sortedSpec = $sortById($specValues);

                            // Compare the sorted arrays
                            if (json_encode($sortedRequest) === json_encode($sortedSpec)) {
                                $is_valid_specification = $spec;
                                break;
                            }
                        }
                        // If they're not arrays, compare directly
                        else if ($request->value_add == $spec->value_add) {
                            $is_valid_specification = $spec;
                            break;
                        }
                    } catch (\Exception $e) {
                        // If JSON parsing fails, fall back to direct comparison
                        if ($request->value_add == $spec->value_add) {
                            $is_valid_specification = $spec;
                            break;
                        }
                    }
                }
            }
        }
        // For regular string values (type 1, 2, 3)
        else {
            $is_valid_specification = ProductSpecification::where("product_id", $request->product_id)
                ->where("id", "<>", $id)
                ->where("attribute_id", $request->attribute_id)
                ->where("value_add", $request->value_add)
                ->first();
        }
    }

    if ($is_valid_specification) {
        return response()->json(["message" => "Ya existe una especificacion con estas características"], 403);
    }

    // If we get here, no duplicate was found, so update the specification
    $product_specification = $current_specification;
    $product_specification->update($request->all());

    return response()->json(
        [
            "message" => 200,
            "specification" => [
                response(
                    ['id' => $product_specification->id,
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
