<?php

namespace App\Http\Controllers\Ecommerce;

use App\Http\Controllers\Controller;
use App\Models\Product\Categorie;
use App\Models\Slider;
use Illuminate\Http\Request;

class HomeController extends Controller
{
    //

    public function home(Request $request)
    {
        $sliders_principal = Slider::where("state", 1)->where("type_slider", 1)->orderBy("id", "desc")->get();

        // ->orderBy("id", "desc")
        $categories_randoms = Categorie::withCount(["product_categorie_firsts"])
        ->where("categorie_second_id", NULL)
        ->where("categorie_third_id", NULL)
        ->inRandomOrder()
        ->limit(5)
        ->get();

        return response()->json([
            "sliders_principal" => $sliders_principal->map(function ($slider) {
                return [
                    "id" => $slider->id,
                    "title" => $slider->title,
                    "subtitle" => $slider->subtitle,
                    "label" => $slider->label,
                    "imagen" => $slider->imagen ? env("APP_URL") . "storage/" . $slider->imagen : NULL,
                    "link" => $slider->link,
                    "color" => $slider->color,
                    "state" => $slider->state,
                    "type_slider" => $slider->type_slider,
                    "price_original" => $slider->price_original,
                    "price_campaing" => $slider->price_campaing
                ];
            }),
            "categories_randoms" => $categories_randoms->map(function ($categorie) {
                return [
                    "id" => $categorie->id,
                    "name" => $categorie->name,
                    "products_count" => $categorie->product_categorie_firsts_count,
                    "imagen" => $categorie->imagen ? env("APP_URL") . "storage/" . $categorie->imagen : NULL,
                ];
            })
        ]);
    }
}
