<?php

namespace App\Http\Controllers\Admin\Product;

use App\Http\Controllers\Controller;
use App\Http\Resources\Product\CategorieCollection;
use App\Models\Product\Categorie;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class CategorieController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $search = $request->search;

        $categories = Categorie::where("name", "line", "%" . $search . "%")->orderBy("id", "desc")->paginate(25);

        return response()->json([
            "total" => $categories->total(),
            "categories" => CategorieCollection::make($categories)
        ]);
    }

    public function config(){

        $categories_first = Categorie::where("categorie_second_id", NULL)->where("categorie_third_id", NULL)->get();
        $categories_seconds = Categorie::where("categorie_second_id", "<>", NULL)->where("categorie_third_id", NULL)->get();

        return response()->json([
            "categorie_first" => $categories_first,
            "categorie_seconds" => $categories_seconds
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $is_exists = Categorie::where("name", $request->name)->first();
        if ($is_exists) {
            return response()->json(["message" => "La categoría ya existe"], 403);
        }
        if ($request->hasFile("imagen")) {
            $path = Storage::putFile("categories", $request->file("imagen"));
            $request->request->add(["imagen" => $path]);
        }
        $categorie = Categorie::create($request->all());
        return response()->json(["message" => 200]);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $categorie = Categorie::findOrFail($id);

        return response()->json(["categorie" => CategorieCollection::make($categorie)]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {

        $is_exists = Categorie::where("id", "<>", $id)->where("name", $request->name)->first();
        if ($is_exists) {
            return response()->json(["message" => "La categoría ya existe"], 403);
        }
        $categorie = Categorie::findOrFail($id);
        if ($request->hasFile("imagen")) {
            if ($categorie->imagen) {
                Storage::delete($categorie->imagen);
            }
            $path = Storage::putFile("categories", $request->file("imagen"));
            $request->request->add(["imagen" => $path]);
        }
        $categorie->update($request->all());
        return response()->json(["message" => 200]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $categorie = Categorie::findOrFail($id);
        $categorie->delete();
        //validar que la categoria no este en ningun producto

        return response()->json(["message" => 200]);
    }
}
