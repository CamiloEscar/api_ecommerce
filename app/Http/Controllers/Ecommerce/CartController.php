<?php

namespace App\Http\Controllers\Ecommerce;

use App\Http\Controllers\Controller;
use App\Http\Resources\Ecommerce\Cart\CartEcommerceCollection;
use App\Http\Resources\Ecommerce\Cart\CartEcommerceResource;
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
