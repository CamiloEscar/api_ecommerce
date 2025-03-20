<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Slider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class SliderController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $search = $request->search;

        $sliders = Slider::where("title", "like", "%" . $search . "%")->orderBy("id", "desc")->paginate(25);

        return response()->json([
            "total" => $sliders->total(),
            "sliders" => $sliders->map(function ($slider) {
                return [
                    "id" => $slider->id,
                    "title" => $slider->title,
                    "subtitle" => $slider->subtitle,
                    "label" => $slider->label,
                    "link" => $slider->link,
                    "state" => $slider->state,
                    "color" => $slider->color,
                    "imagen" => env("APP_URL") . "storage/" . $slider->imagen,
                ];
            })
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $data = $request->except('imagen'); // Excluye la imagen de los datos

        if ($request->hasFile("imagen")) {
            $file = $request->file("imagen");

            // Validar que el archivo no tenga extensión .tmp
            if ($file->getClientOriginalExtension() === 'tmp') {
                return response()->json(["message" => "No se permiten archivos temporales"], 403);
            }

            // Guardar la imagen en el disco 'public' bajo la carpeta 'categories'
            $fileName = $file->hashName();
            $path = $file->storeAs("slider", $fileName, "public");

            // Guardar como 'categories/nombrearchivo' en la base de datos
            $data['imagen'] = "slider/" . $fileName;
        }

        $slider = Slider::create($data);
        return response()->json(["message" => 200]);

        //probar
        // if ($request->hasFile("image")) {
        //     $path = Storage::putFile("sliders", $request->file("image"));
        //     $request->request->add(["imagen" => $path]);
        // }
        // $slider = Slider::create($request->all());
        // return response()->json(["message" => "Slider creado correctamente", "slider" => $slider]);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $slider = Slider::findOrFail($id);

        return response()->json(["slider" => [
            "id" => $slider->id,
            "title" => $slider->title,
            "subtitle" => $slider->subtitle,
            "label" => $slider->label,
            "link" => $slider->link,
            "state" => $slider->state,
            "color" => $slider->color,
            "imagen" => env("APP_URL") . "storage/" . $slider->imagen,
        ]]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
            $slider = Slider::findOrFail($id);
        $data = $request->except('imagen');

        if ($request->hasFile("imagen")) {
            $file = $request->file("imagen");

            // Validar que el archivo no tenga extensión .tmp
            if ($file->getClientOriginalExtension() === 'tmp') {
                return response()->json(["message" => "No se permiten archivos temporales"], 403);
            }

            // Eliminar la imagen anterior del disco 'public'
            if ($slider->imagen) {
                Storage::disk("public")->delete($slider->imagen);
            }

            // Guardar la imagen en el disco 'public' bajo la carpeta 'categories'
            $fileName = $file->hashName();
            $path = $file->storeAs("sliders", $fileName, "public");

            // Guardar como 'categories/nombrearchivo' en la base de datos
            $data['imagen'] = "sliders/" . $fileName;
        }

        $slider->update($data);
        return response()->json(["message" => "Categoría actualizada con éxito"], 200);
        // $slider = Slider::findOrFail($id);
        // if ($request->hasFile("image")) {
        //     if ($slider->imagen) {
        //         Storage::delete($slider->imagen);
        //     }
        //     $path = Storage::putFile("slider", $request->file("image"));
        //     $request->request->add(["imagen" => $path]);
        // }
        // $slider->update($request->all());
        // return response()->json(["message" => "Slider actualizado correctamente", "slider" => $slider]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $slider = Slider::findOrFail($id);
        $slider->delete();

        return response()->json(["message" => 200]);
    }
}
