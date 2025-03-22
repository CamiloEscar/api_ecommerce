<?php

namespace App\Http\Controllers\Admin\Product;

use App\Http\Controllers\Controller;
use App\Http\Resources\Product\ProductCollection;
use App\Http\Resources\Product\ProductResource;
use App\Models\Product\Brand;
use App\Models\Product\Categorie;
use App\Models\Product\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ProductController extends Controller
{
    /**
     * Display a listing of the resource.
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
            "total" => $products->total,
            "products" => ProductCollection::make($products)
        ]);
    }

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


    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $isValid = Product::where("title", $request->title)->first();
        if ($isValid) {
            return response()->json([
                "message" => "El producto ya existe"
            ], 403);
        }
        if ($request->hasFile("portada")) {
            $path = Storage::putFile("products", $request->file("portada"));
            $request->request->add(["imagen" => $path]);
        }


        $request->request->add(["slug" => Str::slug($request->title)]);
        $request->request->add(["tags" =>$request->multiselect]);

        $product = Product::create($request->all());
        return response()->json([
            "message" => 200,
        ]);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $product = Product::findOrFail($id);

        return response()->json([
            "product" => ProductResource::make($product)
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $isValid = Product::where("id", "<>", $id)->where("title", $request->title)->first();
        if ($isValid) {
            return response()->json([
                "message" => "El producto ya existe"
            ], 403);
        }

        $product = Product::findOrFail($id);
        if ($request->hasFile("portada")) {
            if ($product->imagen) {
                Storage::delete($product->imagen);
            }
            $path = Storage::putFile("products", $request->file("portada"));
            $request->request->add(["imagen" => $path]);
        }


        $request->request->add(["slug" => Str::slug($request->title)]);
        $request->request->add(["tags" => $request->multiselect]);

        $product->update($request->all());
        return response()->json([
            "message" => 200,
            "Producto creado con éxito",
            "product" => $product
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $product = Product::findOrFail($id);
        $product->delete();

        return response()->json([
            "message" => 200,
            "Producto eliminado con éxito"
        ]);
    }
}
