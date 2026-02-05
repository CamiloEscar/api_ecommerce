<?php

namespace App\Http\Controllers\Ecommerce;

use App\Helpers\ImageHelper;

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

/**
* @OA\Info(
*             title="API Home",
*             version="1.0",
*             description="Descripcion de los endpoints del home"
* )
*
* @OA\Server(url="http://127.0.0.1:8000")
*/


class HomeController extends Controller
{
    //

   /**
 * Listado de todos los registros de los productos del home
 *
 * @OA\Get(
 *     path="/api/ecommerce/menus",
 *     tags={"Menu de las categorias"},
 *     summary="Obtiene el listado de menús con categorías y subcategorías",
 *     @OA\Response(
 *         response=200,
 *         description="OK",
 *         @OA\JsonContent(
 *             @OA\Property(
 *                 property="categories_menus",
 *                 type="array",
 *                 @OA\Items(
 *                     type="object",
 *                     @OA\Property(
 *                         property="id",
 *                         type="integer",
 *                         example=1
 *                     ),
 *                     @OA\Property(
 *                         property="name",
 *                         type="string",
 *                         example="Funkos"
 *                     ),
 *                     @OA\Property(
 *                         property="icon",
 *                         type="string",
 *                         example="<svg>...</svg>"
 *                     ),
 *                     @OA\Property(
 *                         property="categories",
 *                         type="array",
 *                         @OA\Items(
 *                             type="object",
 *                             @OA\Property(
 *                                 property="id",
 *                                 type="integer",
 *                                 example=3
 *                             ),
 *                             @OA\Property(
 *                                 property="name",
 *                                 type="string",
 *                                 example="Series de TV"
 *                             ),
 *                             @OA\Property(
 *                                 property="imagen",
 *                                 type="string",
 *                                 format="url",
 *                                 example="http://127.0.0.1:8000/storage/categories/POMe1ibHLQXUApyuS9DGaUFMNyvtVtY62jhoAPpV.jpg"
 *                             ),
 *                             @OA\Property(
 *                                 property="subcategories",
 *                                 type="array",
 *                                 @OA\Items(
 *                                     type="object",
 *                                     @OA\Property(
 *                                         property="id",
 *                                         type="integer",
 *                                         example=7
 *                                     ),
 *                                     @OA\Property(
 *                                         property="name",
 *                                         type="string",
 *                                         example="Disney"
 *                                     ),
 *                                     @OA\Property(
 *                                         property="imagen",
 *                                         type="string",
 *                                         nullable=true,
 *                                         example=null
 *                                     )
 *                                 )
 *                             )
 *                         )
 *                     )
 *                 )
 *             )
 *         )
 *     )
 * )
 */



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
        $products_comics = Product::where("state", 2)->where("categorie_first_id", 38)->where("categorie_second_id", 10)->inRandomOrder()->limit(6)->get();
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
                    "imagen" => ImageHelper::getImageUrl($slider->imagen),
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
                    "imagen" => ImageHelper::getImageUrl($categorie->imagen),
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
                    "imagen" => ImageHelper::getImageUrl($slider->imagen),
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
                    "imagen" => ImageHelper::getImageUrl($slider->imagen),
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

    public function menus(Request $request)
{
    $categories_menus = Categorie::where("categorie_second_id", NULL)
        ->where("categorie_third_id", NULL)
        ->orderBy("position", "desc")
        ->get();

    $categories_menus = Categorie::with([
        'categorie_seconds.categorie_thirds'
    ])
    ->where("categorie_second_id", NULL)
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
                        "imagen" => ImageHelper::getImageUrl($categorie->imagen),
                        "subcategories" => $categorie->categorie_thirds->map(function ($subcategorie) {
                            return [
                                "id" => $subcategorie->id,
                                "name" => $subcategorie->name,
                            ];
                        })->values(),
                    ];
                })->values(),
            ];
        })->values(),
    ]);
}


/**
 * Obtener detalles de un producto por slug
 *
 * @OA\Get(
 *     path="/api/ecommerce/producto/{slug}",
 *     summary="Obtener detalles de un producto",
 *     tags={"Producto"},
 *     @OA\Parameter(
 *         name="slug",
 *         in="path",
 *         required=true,
 *         description="Slug del producto",
 *         @OA\Schema(type="string", example="funko-pop-marvel-spider-man-simbionte-especial")
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Detalles del producto",
 *         @OA\JsonContent(
 *             @OA\Property(property="product", type="object",
 *                 @OA\Property(property="id", type="integer", example=2),
 *                 @OA\Property(property="title", type="string", example="Funko Pop! Marvel: Spider-Man Simbionte (Especial)"),
 *                 @OA\Property(property="slug", type="string", example="funko-pop-marvel-spider-man-simbionte-especial"),
 *                 @OA\Property(property="sku", type="string", example="Funko-Spiderman-Simbionte"),
 *                 @OA\Property(property="price_ars", type="number", format="float", example=150),
 *                 @OA\Property(property="price_usd", type="number", format="float", example=40),
 *                 @OA\Property(property="resumen", type="string", example="Figura Funko Pop! de Spider-Man con traje simbionte..."),
 *                 @OA\Property(property="imagen", type="string", format="url", example="http://127.0.0.1:8000/storage/products/DhdVBVqKH5PTZzBHLe2b1lREKP9UHk6einNdjX4e.jpg"),
 *                 @OA\Property(property="state", type="integer", example=2),
 *                 @OA\Property(property="description", type="string", format="html", example="<p>Figura coleccionable Funko Pop...</p>"),
 *
 *                 @OA\Property(property="tags", type="array",
 *                     @OA\Items(type="object",
 *                         @OA\Property(property="item_id", type="integer", example=1714412401001),
 *                         @OA\Property(property="item_text", type="string", example="funko")
 *                     )
 *                 ),
 *                 @OA\Property(property="tags_parse", type="array",
 *                     @OA\Items(type="string", example="funko")
 *                 ),
 *
 *                 @OA\Property(property="brand_id", type="integer", example=2),
 *                 @OA\Property(property="brand", type="object",
 *                     @OA\Property(property="id", type="integer", example=2),
 *                     @OA\Property(property="name", type="string", example="Banpresto")
 *                 ),
 *
 *                 @OA\Property(property="categorie_first_id", type="integer", example=1),
 *                 @OA\Property(property="categorie_first", type="object",
 *                     @OA\Property(property="id", type="integer", example=1),
 *                     @OA\Property(property="name", type="string", example="Funkos")
 *                 ),
 *                 @OA\Property(property="categorie_second_id", type="integer", example=3),
 *                 @OA\Property(property="categorie_second", type="object",
 *                     @OA\Property(property="id", type="integer", example=3),
 *                     @OA\Property(property="name", type="string", example="Series de TV")
 *                 ),
 *                 @OA\Property(property="categorie_third_id", type="integer", example=8),
 *                 @OA\Property(property="categorie_third", type="object",
 *                     @OA\Property(property="id", type="integer", example=8),
 *                     @OA\Property(property="name", type="string", example="Marvel")
 *                 ),

 *                 @OA\Property(property="stock", type="integer", example=20),
 *                 @OA\Property(property="created_at", type="string", format="date-time", example="2024-01-13 05:42:59"),
 *                 @OA\Property(property="images", type="array", @OA\Items(type="string", format="url")),
 *                 @OA\Property(property="discount_g", type="string", nullable=true, example=null),
 *                 @OA\Property(property="variations", type="array", @OA\Items(type="object")),
 *                 @OA\Property(property="avg_reviews", type="number", example=0),
 *                 @OA\Property(property="count_reviews", type="integer", example=0),

 *                 @OA\Property(property="specifications", type="array",
 *                     @OA\Items(type="object",
 *                         @OA\Property(property="id", type="integer", example=1),
 *                         @OA\Property(property="product_id", type="integer", example=2),
 *                         @OA\Property(property="attribute_id", type="integer", example=3),
 *                         @OA\Property(property="attribute", type="object",
 *                             @OA\Property(property="name", type="string", example="Tamaño"),
 *                             @OA\Property(property="type_attribute", type="integer", example=1)
 *                         ),
 *                         @OA\Property(property="propertie_id", type="integer", nullable=true, example=null),
 *                         @OA\Property(property="propertie", type="string", nullable=true, example=null),
 *                         @OA\Property(property="value_add", type="string", example="Funko Pop Spiderman 2 Marvel")
 *                     )
 *                 )
 *             )
 *         )
 *     ),
 *     @OA\Response(
 *         response=404,
 *         description="Producto no encontrado",
 *         @OA\JsonContent(
 *             @OA\Property(property="message", type="string", example="Producto no encontrado")
 *         )
 *     )
 * )
 */

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
                        'avatar' => ImageHelper::getImageUrl($review->user->avatar) ?? 'https://cdn-icons-png.flaticon.com/512/12449/12449018.png',

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
                    "imagen" => ImageHelper::getImageUrl($categorie->imagen),
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
        $brands_selected = $request->brands_selected;

        $min_price = $request->min_price;
        $max_price = $request->max_price;

        $currency = $request->currency;

        $options_aditional = $request->options_aditional;

        $search = $request->search;

        $colors_product_selected = [];
        if($colors_selected && sizeof($colors_selected) > 0){
            $properties = Propertie::whereIn("id", $colors_selected)->get();
            foreach ($properties as $propertie) {
                foreach ($propertie->variations as $variations) {
                    array_push($colors_product_selected, $variations->product_id);
                }
            }
        }

        $product_general_ids_array = [];
        if($options_aditional && sizeof($options_aditional) > 0 && in_array("campaing", $options_aditional)) {

            date_default_timezone_set("America/Argentina/Buenos_Aires");
            $discount = Discount::where("type_campaing", 1)->where("state", 1)
                                ->where("start_date", "<=", today())
                                ->where("end_date", ">=", today())
                                ->first();
            if($discount){
                foreach ($discount->products as $product_aux) {
                    //accedemos a la relacion discount_product para asi poder acceder al producto
                    array_push($product_general_ids_array, $product_aux->product_id);
                }
                foreach ($discount->categories as $categorie_aux) {
                    //accedemos a la relacion discount_product para asi poder acceder al producto
                    array_push($categories_selected, $categorie_aux->categorie_id);
                }
                foreach ($discount->brands as $brand_aux) {
                    array_push($brands_selected, $brand_aux->brand_id);
                }
            }
        };

        $products = Product::filterAdvanceEcommerce($categories_selected, $colors_product_selected, $brands_selected,
                                                    $min_price, $max_price, $currency, $product_general_ids_array, $options_aditional, $search )
                                                    ->orderBy("id", "desc")->get();

        return response()->json([
            "products" => ProductEcommerceCollection::make($products),
        ]);
    }

    public function campaing_discount_link(Request $request){
        $code_discount = $request->code_discount;

        $is_exist_discount = Discount::where("code", $code_discount)->where("type_campaing", 3)->where("state", 1)->first();
        if(!$is_exist_discount){
            return response()->json([
                "message" => 403,
                "message_text" => "El codigo de descuento no existe"
            ]);
        }

        date_default_timezone_set("America/Argentina/Buenos_Aires");
        $DISCOUNT_LINK = Discount::where("code", $code_discount)
                            ->where("state", 1)
                            ->where("type_campaing", 3)
                            ->where("start_date", "<=", today())
                            ->where("end_date", ">=", today())
                            ->first();

        if(!$DISCOUNT_LINK){
            return response()->json([
                "message" => 403,
                "message_text" => "El codigo de descuento no esta activo o ya vencio"
            ]);
        }

        $DISCOUNT_LINK_PRODUCTS = collect([]);
        if($DISCOUNT_LINK){
            foreach ($DISCOUNT_LINK->products as $key => $aux_product) {
                //accedemos a la relacion discount_product para asi poder acceder al producto
                $DISCOUNT_LINK_PRODUCTS->push(ProductEcommerceResource::make($aux_product->product));
            }
            //campaña de descuento a nivel categoria
            foreach ($DISCOUNT_LINK->categories as $key => $aux_categorie) {
                $products_of_categorie = Product::where("state", 2)->where("categorie_first_id", $aux_categorie->categorie_id)->get();

                foreach ($products_of_categorie as $key => $product) {
                    $DISCOUNT_LINK_PRODUCTS->push(ProductEcommerceResource::make($product));
                }
            }
            foreach ($DISCOUNT_LINK->brands as $key => $aux_brand) {
                $products_of_brands = Product::where("state", 2)->where("brand_id", $aux_brand->brand_id)->get();

                foreach ($products_of_brands as $key => $product) {
                    $DISCOUNT_LINK_PRODUCTS->push(ProductEcommerceResource::make($product));
                }
            }
            $DISCOUNT_LINK->start_date_format = Carbon::parse($DISCOUNT_LINK->start_date)->format("Y/m/d");
            $DISCOUNT_LINK->end_date_format = Carbon::parse($DISCOUNT_LINK->end_date)->format("Y/m/d");
        }

        return response()->json([
            "message" => 200,
            "discount" => $DISCOUNT_LINK,
            "products" => $DISCOUNT_LINK_PRODUCTS
        ]);
    }
}
