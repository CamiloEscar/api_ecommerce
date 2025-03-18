<?php

namespace App\Http\Controllers\Admin\Product;

use App\Models\Product\Propertie;
use Illuminate\Http\Request;
use App\Models\Product\Attribute;
use App\Http\Controllers\Controller;

class AttributeProductController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $search = $request->search;

        $attributes = Attribute::where("name","like","%".$search."%")->orderBy("id","desc")->paginate(25);

        return response()->json([
            "total" => $attributes->total(),
            "attributes" => $attributes->map(function($attribute) {
                return [
                    "id" => $attribute->id,
                    "name" => $attribute->name,
                    "type_attribute" => $attribute->type_attribute,
                    "state" => $attribute->state,
                    "created_at" => $attribute->created_at->format("Y-m-d h:i:s"),
                    "properties" => $attribute->properties->map(function($propertie) {
                        return [
                            "id" => $propertie->id,
                            "name" => $propertie->name,
                            "code" => $propertie->code,
                        ];
                    })
                ];
            }),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $isValida = Attribute::where("name",$request->name)->first();
        if($isValida){
            return response()->json(["message" => 403]);
        }
        $attribute = Attribute::create($request->all());

        return response()->json([
            "message" => 200,
            "attribute" => [
                "id" => $attribute->id,
                "name" => $attribute->name,
                "type_attribute" => $attribute->type_attribute,
                "state" => $attribute->state,
                "created_at" => $attribute->created_at->format("Y-m-d h:i:s"),
                "properties" => $attribute->properties->map(function($propertie) {
                    return [
                        "id" => $propertie->id,
                        "name" => $propertie->name,
                        "code" => $propertie->code,
                    ];
                })
            ],
        ]);
    }

    public function store_propertie(Request $request) {
        $isValida = Propertie::where("name",$request->name)
                            ->where("attribute_id",$request->attribute_id)
                            ->first();
        // lista de colores fuertes rojo amarillo verde

        // lista de colores rojo marron anaranjado
        if($isValida){
            return response()->json(["message" => 403]);
        }
        $propertie = Propertie::create($request->all());

        return response()->json([
            "message" => 200,
            "propertie" => [
                "id" => $propertie->id,
                "name" => $propertie->name,
                "code" => $propertie->code,
                "created_at" => $propertie->created_at->format("Y-m-d h:i:s"),
            ],
        ]);
    }

    public function destroy_propertie($id) {
        $propertie = Propertie::findOrFail($id);
        $propertie->delete();

        return response()->json([
            "message" => 200,
        ]);
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
        $isValida = Attribute::where("id","<>",$id)->where("name",$request->name)->first();
        if($isValida){
            return response()->json(["message" => 403]);
        }
        $attribute = Attribute::findOrFail($id);
        $attribute->update($request->all());
        return response()->json([
            "message" => 200,
            "attribute" => [
                "id" => $attribute->id,
                "name" => $attribute->name,
                "type_attribute" => $attribute->type_attribute,
                "state" => $attribute->state,
                "created_at" => $attribute->created_at->format("Y-m-d h:i:s"),
                "properties" => $attribute->properties->map(function($propertie) {
                    return [
                        "id" => $propertie->id,
                        "name" => $propertie->name,
                        "code" => $propertie->code,
                    ];
                })
            ],
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $attribute = Attribute::findOrFail($id);
        $attribute->delete();//IMPORTANTE VALIDACION
        return response()->json([
            "message" => 200,
        ]);
    }
}
