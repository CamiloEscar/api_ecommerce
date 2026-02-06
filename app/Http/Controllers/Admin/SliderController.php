<?php

namespace App\Http\Controllers\Admin;

use App\Helpers\ImageHelper;
use App\Http\Controllers\Controller;
use App\Services\ImageService;
use App\Models\Slider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class SliderController extends Controller
{
    protected $imageService;

    public function __construct(ImageService $imageService)
    {
        $this->imageService = $imageService;
    }

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
                    "type_slider" => $slider->type_slider,
                    "price_original" => $slider->price_original,
                    "price_campaing" => $slider->price_campaing,
                    "imagen" => $slider->imagen, // ✅ Ya es URL de Cloudinary
                ];
            })
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $data = $request->except('imagen');

        // ✅ Subir a Cloudinary
        if ($request->hasFile("imagen")) {
            $file = $request->file("imagen");

            // Validar que el archivo no tenga extensión .tmp
            if ($file->getClientOriginalExtension() === 'tmp') {
                return response()->json(["message" => "No se permiten archivos temporales"], 403);
            }

            $data['imagen'] = $this->imageService->upload($file, 'sliders');
        }

        $slider = Slider::create($data);

        return response()->json(["message" => 200]);
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
            "type_slider" => $slider->type_slider,
            "price_original" => $slider->price_original,
            "price_campaing" => $slider->price_campaing,
            "imagen" => $slider->imagen, // ✅ Ya es URL de Cloudinary
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

            // ✅ Eliminar imagen anterior de Cloudinary
            if ($slider->imagen) {
                $publicId = $this->imageService->getPublicIdFromUrl($slider->imagen);
                if ($publicId) {
                    $this->imageService->delete($publicId);
                }
            }

            // ✅ Subir nueva imagen a Cloudinary
            $data['imagen'] = $this->imageService->upload($file, 'sliders');
        }

        $slider->update($data);

        return response()->json(["message" => "Slider actualizado con éxito"], 200);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $slider = Slider::findOrFail($id);

        // ✅ Eliminar imagen de Cloudinary
        if ($slider->imagen) {
            $publicId = $this->imageService->getPublicIdFromUrl($slider->imagen);
            if ($publicId) {
                $this->imageService->delete($publicId);
            }
        }

        $slider->delete();

        return response()->json(["message" => 200]);
    }
}
