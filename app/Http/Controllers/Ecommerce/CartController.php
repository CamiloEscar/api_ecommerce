<?php

namespace App\Http\Controllers\Ecommerce;

use App\Http\Controllers\Controller;
use App\Http\Resources\Ecommerce\Cart\CartEcommerceCollection;
use App\Http\Resources\Ecommerce\Cart\CartEcommerceResource;
use App\Models\Cupone\Cupone;
use App\Models\Product\Product;
use App\Models\Product\ProductVariation;
use App\Models\Sale\Cart;
use Illuminate\Http\Request;

class CartController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //tenemos que autenticarnos
        $user = auth('api')->user();

        //obtiene los datos de la tabla cart
        $carts = Cart::where("user_id", $user->id)->get();

        //envia el json al front
        return response()->json([
            "carts" => CartEcommerceCollection::make($carts),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $user = auth('api')->user();

        //validamos si existe el producto con variacion
        if ($request->product_variation_id) {
            $is_exists_cart_variations = Cart::where("product_variation_id", $request->product_variation_id)
                ->where("product_id", $request->product_id)
                ->where("user_id", $user->id)
                ->first();

            if ($is_exists_cart_variations) {
                return response()->json([
                    "message" => 403,
                    "message_text" => "El producto junto con la variacion ya ha sido agregado, aumente la cantidad en el carrito "
                ]);
            } else {
                $variation = ProductVariation::find($request->product_variation_id);

                if ($variation && $variation->stock < $request->quantity) {
                    return response()->json([
                        "message" => 403,
                        "message_text" => "De esa variacion no se puede agregar mas productos en el carrito por falta de stock"
                    ]);
                }
            }
        } else {
            //sin variacion
            $is_exists_cart_simple = Cart::where("product_variation_id", NULL)
                ->where("product_id", $request->product_id)
                ->where("user_id", $user->id)
                ->first();

            if ($is_exists_cart_simple) {
                return response()->json([
                    "message" => 403,
                    "message_text" => "El producto ya ha sido agregado, aumente la cantidad en el carrito "
                ]);
            } else {
                $product = Product::find($request->product_id);
                if ($product->stock < $request->quantity) {
                    return response()->json([
                        "message" => 403,
                        "message_text" => "No se puede agregar mas productos en el carrito por falta de stock"
                    ]);
                }
            }
        }

        $request->request->add(["user_id" => $user->id]);
        $cart = Cart::create($request->all());

        return response()->json([
            "cart" => CartEcommerceResource::make($cart)
        ]);
    }

    public function apply_cupon(Request $request){
        $cupon = Cupone::where("code", $request->code_cupon)->where("state", 1)->first();

        if(!$cupon){
            return response()->json(["message" => 403, "message_text" => "El cupon ingresado no existe o ya caduco"]);
        }

        $user = auth('api')->user();
        $carts = Cart::where("user_id", $user->id)->get();

        foreach ($carts as $key => $cart) {
            if($cupon->type_cupone == 1){  //1 es a nivel de producto
                $is_exists_product_cupon = false;       //existe?
                foreach ($cupon->products as $cupon_product) { //products es una relacion al modelo auxiliar
                    if($cupon_product->product_id == $cart->product_id){
                        $is_exists_product_cupon = true;
                        break;
                    };
                }
                if($is_exists_product_cupon){
                    $subtotal = 0;
                    if($cupon->type_discount == 1){ //porcentaje
                        $subtotal = $cart->price_unit - $cart->price_unit * ($cupon->discount*0.01);
                    };
                    if($cupon->type_discount == 2){ //monto fijo
                        $subtotal = $cart->price_unit - $cupon->discount;
                    };

                    // if(!$cart->code_discount){  //si ya tiene codigo de descuento hecho no hace falta
                        $cart->update([
                            "type_discount" => $cupon->type_discount,
                            "discount" => $cupon->discount,
                            "code_cupon" => $cupon->code,
                            "subtotal" => $subtotal,
                            "total" => $subtotal*$cart->quantity,
                            "type_campaing" => NULL,
                            "code_discount" => NULL,
                        ]);
                    // };
                }

            };
            if($cupon->type_cupone == 2){  //1 es a nivel de categoria
                $is_exists_categorie_cupon = false;       //existe?
                foreach ($cupon->categories as $cupon_product) { //products es una relacion al modelo auxiliar
                    if($cupon_product->categorie_id == $cart->product->categorie_first_id){
                        $is_exists_categorie_cupon = true;
                        break;
                    };
                }
                if($is_exists_categorie_cupon){
                    $subtotal = 0;
                    if($cupon->type_discount == 1){ //porcentaje
                        $subtotal = $cart->price_unit - $cart->price_unit * ($cupon->discount*0.01);
                    };
                    if($cupon->type_discount == 2){ //monto fijo
                        $subtotal = $cart->price_unit - $cupon->discount;
                    };

                    // if(!$cart->code_discount){  //si ya tiene codigo de descuento hecho no hace falta
                        $cart->update([
                            "type_discount" => $cupon->type_discount,
                            "discount" => $cupon->discount,
                            "code_cupon" => $cupon->code,
                            "subtotal" => $subtotal,
                            "total" => $subtotal*$cart->quantity,
                            "type_campaing" => NULL,
                            "code_discount" => NULL,
                        ]);
                    // };
                }
            };
            if($cupon->type_cupone == 3){  //1 es a nivel de marca
                $is_exists_brand_cupon = false;       //existe?
                foreach ($cupon->brands as $cupon_product) { //products es una relacion al modelo auxiliar
                    if($cupon_product->brand_id == $cart->product->brand_id){
                        $is_exists_brand_cupon = true;
                        break;
                    };
                }
                if($is_exists_brand_cupon){
                    $subtotal = 0;
                    if($cupon->type_discount == 1){ //porcentaje
                        $subtotal = $cart->price_unit - $cart->price_unit * ($cupon->discount*0.01);
                    };
                    if($cupon->type_discount == 2){ //monto fijo
                        $subtotal = $cart->price_unit - $cupon->discount;
                    };

                    // if(!$cart->code_discount){  //si ya tiene codigo de descuento hecho no hace falta
                        $cart->update([
                            "type_discount" => $cupon->type_discount,
                            "discount" => $cupon->discount,
                            "code_cupon" => $cupon->code,
                            "subtotal" => $subtotal,
                            "total" => $subtotal*$cart->quantity,
                            "type_campaing" => NULL,
                            "code_discount" => NULL,
                        ]);
                    // };
                }
            };
        }

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
        $user = auth('api')->user();

        //validamos si existe el producto con variacion
        if ($request->product_variation_id) {
            $is_exists_cart_variations = Cart::where("product_variation_id", $request->product_variation_id)
                ->where("product_id", $request->product_id)
                ->where("id", '<>', $id)
                ->where("user_id", $user->id)
                ->first();

            if ($is_exists_cart_variations) {
                return response()->json([
                    "message" => 403,
                    "message_text" => "El producto junto con la variacion ya ha sido agregado, aumente la cantidad en el carrito "
                ]);
            } else {
                $variation = ProductVariation::find($request->product_variation_id);

                if ($variation && $variation->stock < $request->quantity) {
                    return response()->json([
                        "message" => 403,
                        "message_text" => "De esa variacion no se puede agregar mas productos en el carrito por falta de stock"
                    ]);
                }
            }
        } else {
            //sin variacion
            $is_exists_cart_simple = Cart::where("product_variation_id", NULL)
                ->where("product_id", $request->product_id)
                ->where("id", '<>', $id)
                ->where("user_id", $user->id)
                ->first();

            if ($is_exists_cart_simple) {
                return response()->json([
                    "message" => 403,
                    "message_text" => "El producto ya ha sido agregado, aumente la cantidad en el carrito "
                ]);
            } else {
                $product = Product::find($request->product_id);
                if ($product->stock < $request->quantity) {
                    return response()->json([
                        "message" => 403,
                        "message_text" => "No se puede agregar mas productos en el carrito por falta de stock"
                    ]);
                }
            }
        }

        $cart = Cart::findOrFail($id);
        $cart->update($request->all());

        return response()->json([
            "cart" => CartEcommerceResource::make($cart)
        ]);
    }

    public function delete_all(){
        $user = auth("api")->user();
        $carts = Cart::where("user_id", $user->id)->get();
        foreach ($carts as $key => $cart) {
            $cart->delete();
        }
        return response()->json([
            "message" => 200,
        ]);
    }
    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $cart = Cart::findOrFail($id);
        $cart->delete();

        return response()->json([
            "message" => 200,
        ]);
    }
}
