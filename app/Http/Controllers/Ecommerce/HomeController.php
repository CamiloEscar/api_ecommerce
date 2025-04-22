<?php

namespace App\Http\Controllers\Ecommerce;

use App\Http\Controllers\Controller;
use App\Http\Resources\Ecommerce\Product\ProductEcommerceCollection;
use App\Http\Resources\Ecommerce\Product\ProductEcommerceResource;
use App\Models\Discount\Discount;
use App\Models\Product\Brand;
use App\Models\Product\Categorie;
use App\Models\Product\Product;
use App\Models\Product\Propertie;
use App\Models\Sale\Review;
use App\Models\Slider;
use Carbon\Carbon;
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



        $products_trending_new = Product::where("state", 2)->inRandomOrder()->limit(8)->get();
        $products_trending_featured = Product::where("state", 2)->inRandomOrder()->limit(8)->get();
        $products_trending_top_sellers = Product::where("state", 2)->inRandomOrder()->limit(8)->get();

        $sliders_secundario = Slider::where("state", 1)->where("type_slider", 2)->orderBy("id", "asc")->get();
        $sliders_productos = Slider::where("state", 1)->where("type_slider", 3)->orderBy("id", "asc")->get();

        // $products_comics = Product::where("state", 2)->where("categorie_first_id", 50)->inRandomOrder()->limit(6)->get();
        $products_comics = Product::where("state", 2)->where("categorie_first_id", 47)->where("categorie_second_id", 63)->inRandomOrder()->limit(6)->get();
        $products_carousel = Product::where("state", 2)->whereIn("categorie_first_id", $categories_randoms->pluck("id"))->inRandomOrder()->get();


        $products_last_discounts = Product::where("state", 2)->inRandomOrder()->limit(3)->get();
        $products_last_featured = Product::where("state", 2)->inRandomOrder()->limit(3)->get();
        $products_last_selling = Product::where("state", 2)->inRandomOrder()->limit(3)->get();


        date_default_timezone_set("America/Argentina/Buenos_Aires");
        //definimos variables para descuentos
        $DISCOUNT_FLASH = Discount::where("type_campaing", 2)->where("state", 1)
                                ->where("start_date", "<=", today())
                                ->where("end_date", ">=", today())
                                ->first();
        $DISCOUNT_FLASH_PRODUCTS = collect([]);

        if($DISCOUNT_FLASH){
            foreach ($DISCOUNT_FLASH->products as $key => $aux_product) {
                //accedemos a la relacion discount_product para asi poder acceder al producto
                $DISCOUNT_FLASH_PRODUCTS->push(ProductEcommerceResource::make($aux_product->product));
            }
            //campaña de descuento a nivel categoria
            foreach ($DISCOUNT_FLASH->categories as $key => $aux_categorie) {
                $products_of_categorie = Product::where("state", 2)->where("categorie_first_id", $aux_categorie->categorie_id)->get();

                foreach ($products_of_categorie as $key => $product) {
                    $DISCOUNT_FLASH_PRODUCTS->push(ProductEcommerceResource::make($product));
                }
            }
            foreach ($DISCOUNT_FLASH->brands as $key => $aux_brand) {
                $products_of_brands = Product::where("state", 2)->where("brand_id", $aux_brand->brand_id)->get();

                foreach ($products_of_brands as $key => $product) {
                    $DISCOUNT_FLASH_PRODUCTS->push(ProductEcommerceResource::make($product));
                }
            }
            $DISCOUNT_FLASH->end_date_format = Carbon::parse($DISCOUNT_FLASH->end_date)->format("M d Y H:i:s");
        }


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
            }),
            "products_trending_new" => ProductEcommerceCollection::make($products_trending_new),
            "products_trending_featured" => ProductEcommerceCollection::make($products_trending_featured),
            "products_trending_top_sellers" => ProductEcommerceCollection::make($products_trending_top_sellers),
            "sliders_secundario" => $sliders_secundario->map(function ($slider) {
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
            "products_comics" => ProductEcommerceCollection::make($products_comics),
            "products_carousel" => ProductEcommerceCollection::make($products_carousel),
            "sliders_productos" => $sliders_productos->map(function ($slider) {
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
            "products_last_discounts" => ProductEcommerceCollection::make($products_last_discounts),
            "products_last_featured" => ProductEcommerceCollection::make($products_last_featured),
            "products_last_selling" => ProductEcommerceCollection::make($products_last_selling),

            "discount_flash" => $DISCOUNT_FLASH,
            "discount_flash_product" => $DISCOUNT_FLASH_PRODUCTS,
        ]);
    }

    public function menus(Request $request) {
        $categories_menus = Categorie::where("categorie_second_id", NULL)
        ->where("categorie_third_id", NULL)
        ->orderBy("position", "desc")
        ->get();
        return response()->json([
            "categories_menus" => $categories_menus->map(function ($departament) {
                return [
                    "id" => $departament->id,
                    "name" => $departament->name,
                    "icon" => $departament->icon,
                    "categories" => $departament->categorie_seconds->map(function ($categorie) {
                        return [
                            "id" => $categorie->id,
                            "name" => $categorie->name,
                            "imagen" => $categorie->imagen ? env("APP_URL") . "storage/" . $categorie->imagen : NULL,
                            "subcategories" => $categorie->categorie_seconds->map(function ($subcategorie) {
                                return [
                                    "id" => $subcategorie->id,
                                    "name" => $subcategorie->name,
                                    "imagen" => $subcategorie->imagen ? env("APP_URL") . "storage/" . $subcategorie->imagen : NULL,
                                ];
                            })
                        ];
                    })
                ];
            }),
        ]);

    }

    public function show_product(Request $request, $slug){

        $campaing_discount = $request->get("campaing_discount");
        $discount = null;
        if($campaing_discount){
            $discount = Discount::where("code", $campaing_discount)->first();
        }

        $product = Product::where("slug",$slug)->where("state",2)->first();

        if(!$product){
            return response()->json([
                "message" => 403,
                "message_text" => "El producto no existe"
            ]);
        }

        $product_relateds = Product::where("categorie_first_id", $product->categorie_first_id)->where("state",2)->get();

        $reviews = Review::where("product_id", $product->id)->get();

        return response()->json([
            "message" => 200,
            "product" => ProductEcommerceResource::make($product),
            "product_relateds" => ProductEcommerceCollection::make($product_relateds),
            "discount_campaing" => $discount,
            "reviews" => $reviews->map(function ($review){
                return [
                    "id" => $review->id,
                    "user" => [
                        "full_name" => $review->user->name . " " . $review->user->surname,
                        'avatar' => $review->user->avatar ? env("APP_URL") . "storage/" . $review->user->avatar : 'https://cdn-icons-png.flaticon.com/512/12449/12449018.png',

                    ],
                    "message" => $review->message,
                    "rating" => $review->rating,
                    "created_at" => $review->created_at->format("Y-m-d H:i A")
                ];
            }),
        ]);
    }

    public function config_filter_advance(){
        $categories = Categorie::withCount(["product_categorie_firsts"])
                                ->where("categorie_second_id", NULL)
                                ->where("categorie_third_id", NULL)
                                ->get();

        $brands = Brand::withCount(["products"])->where("state", 1)->get();

        $colors = Propertie::where("code", "<>", NULL)->get();

        $product_relateds = Product::where("state", 2)->inRandomOrder()->limit(4)->get();

        return response()->json([
            "categories" => $categories->map(function ($categorie) {
                return [
                    "id" => $categorie->id,
                    "name" => $categorie->name,
                    "products_count" => $categorie->product_categorie_firsts_count,
                    "imagen" => $categorie->imagen ? env("APP_URL") . "storage/" . $categorie->imagen : NULL,
                ];
            }),
            "brands" => $brands->map(function ($brand) {
                return [
                    "id" => $brand->id,
                    "name" => $brand->name,
                    "products_count" => $brand->products_count,
                ];
            }),
            "colors" => $colors->map(function ($color) {
                $color->products_count = $color->variations->unique("product_id")->count();

                return $color;
            }),
            "product_relateds" => ProductEcommerceCollection::make($product_relateds),

        ]);


    }

    //usamos request ya que debe enviar y traer parametros
    public function filter_advance_product(Request $request){

        $categories_selected = $request->categories_selected;
        $colors_selected = $request->colors_selected;
        $colors_product_selected = [];
        $brands_selected = $request->brands_selected;

        if($colors_selected && sizeof($colors_selected) > 0){
            $properties = Propertie::whereIn("id", $colors_selected)->get();
            foreach ($properties as $propertie) {
                foreach ($propertie->variations as $variations) {
                    array_push($colors_product_selected, $variations->product_id);
                }
            }
        }

        $products = Product::filterAdvanceEcommerce($categories_selected, $colors_product_selected, $brands_selected)->orderBy("id", "desc")->get();

        return response()->json([
            "products" => ProductEcommerceCollection::make($products),
        ]);
    }
}
