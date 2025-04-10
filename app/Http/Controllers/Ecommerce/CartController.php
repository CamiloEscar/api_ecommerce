<?php

namespace App\Http\Controllers\Ecommerce;

use App\Http\Controllers\Controller;
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
        $carts = Cart::where("user_id",$user->id)->get();

        //envia el json al front
        return response()->json([
            "carts" => $carts,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $user = auth('api')->user();

        //validamos si existe el producto con variacion
        if($request->product_variation_id){
            $is_exists_cart_variations = Cart::where("product_variation_id", $request->product_variation_id)
            ->where("product_id", $request->product_id)
            ->where("user_id", $user->id)
            ->first();

            if($is_exists_cart_variations){
                return response()->json([
                    "message" => 403,
                    "message_text" => "El producto junto con la variacion ya ha sido agregado, aumente la cantidad en el carrito "
                ]);
            }
        } else {
            //sin variacion
            $is_exists_cart_simple = Cart::where("product_variation_id", NULL)
                                            ->where("product_id", $request->product_id)
                                            ->where("user_id", $user->id)
                                            ->first();

            if($is_exists_cart_simple){
                return response()->json([
                    "message" => 403,
                    "message_text" => "El producto ya ha sido agregado, aumente la cantidad en el carrito "
                ]);
            }
        }

        $request->request->add(["user_id", $user->id]);
        $cart = Cart::create($request->all());

        return response()->json([
            "cart" => $cart
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
        if($request->product_variation_id){
            $is_exists_cart_variations = Cart::where("product_variation_id", $request->product_variation_id)
                                            ->where("product_id", $request->product_id)
                                            ->where("id", '<>', $id)
                                            ->where("user_id", $user->id)
                                            ->first();

            if($is_exists_cart_variations){
                return response()->json([
                    "message" => 403,
                    "message_text" => "El producto junto con la variacion ya ha sido agregado, aumente la cantidad en el carrito "
                ]);
            }
        } else {
            //sin variacion
            $is_exists_cart_simple = Cart::where("product_variation_id", NULL)
                                            ->where("product_id", $request->product_id)
                                            ->where("id", '<>', $id)
                                            ->where("user_id", $user->id)
                                            ->first();

            if($is_exists_cart_simple){
                return response()->json([
                    "message" => 403,
                    "message_text" => "El producto ya ha sido agregado, aumente la cantidad en el carrito "
                ]);
            }
        }

        $cart = Cart::findOrFail($id);
        $cart->update($request->all());

        return response()->json([
            "cart" => $cart
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
