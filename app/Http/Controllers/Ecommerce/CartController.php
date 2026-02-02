<?php

namespace App\Http\Controllers\Ecommerce;

use App\Http\Controllers\Controller;
use App\Http\Resources\Ecommerce\Cart\CartEcommerceCollection;
use App\Http\Resources\Ecommerce\Cart\CartEcommerceResource;
use App\Models\Costo\Costo;
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

    public function apply_cupon(Request $request)
{
    $cupon = Cupone::where("code", $request->code_cupon)->where("state", 1)->first();

    if (!$cupon) {
        return response()->json([
            "message" => 403,
            "message_text" => "El cupón ingresado no existe o ya caducó"
        ]);
    }

    $user = auth('api')->user();
    $carts = Cart::where("user_id", $user->id)->get();

    $costo_total = 0;

    foreach ($carts as $cart) {
        // 🔒 Evitar aplicar varias veces el mismo cupón
        if ($cart->code_cupon === $cupon->code) {
            continue;
        }

        $applyDiscount = false;

        // --- Nivel producto ---
        if ($cupon->type_cupone == 1) {
            foreach ($cupon->products as $cupon_product) {
                if ($cupon_product->product_id == $cart->product_id) {
                    $applyDiscount = true;
                    break;
                }
            }
        }

        // --- Nivel categoría ---
        if ($cupon->type_cupone == 2) {
            foreach ($cupon->categories as $cupon_category) {
                if ($cupon_category->categorie_id == $cart->product->categorie_first_id) {
                    $applyDiscount = true;
                    break;
                }
            }
        }

        // --- Nivel marca ---
        if ($cupon->type_cupone == 3) {
            foreach ($cupon->brands as $cupon_brand) {
                if ($cupon_brand->brand_id == $cart->product->brand_id) {
                    $applyDiscount = true;
                    break;
                }
            }
        }

        if ($applyDiscount) {
            $precioBase = $cart->price_unit;
            $subtotal = $precioBase;

            if ($cupon->type_discount == 1) { // porcentaje
                $subtotal = $precioBase - ($precioBase * ($cupon->discount * 0.01));
            }
            if ($cupon->type_discount == 2) { // monto fijo
                $subtotal = max(0, $precioBase - $cupon->discount);
            }

            $total = $subtotal * $cart->quantity;

            // Si ya había costo de envío, se lo volvemos a sumar
            if ($cart->code_costo && $cart->discount && $cart->type_discount == 2) {
                $total += $cart->discount;
            }

            $cart->update([
                "type_discount" => $cupon->type_discount,
                "discount" => $cupon->discount,
                "code_cupon" => $cupon->code,
                "subtotal" => $subtotal,
                "total" => $total,
                "type_campaing" => NULL,
                "code_discount" => NULL,
            ]);
        }
    }

    return response()->json([
        "message" => 200,
        "message_text" => "Cupón aplicado correctamente"
    ]);
}

    public function apply_costo(Request $request)
{
    $costo = Costo::where("code", $request->code_costo)->where("state", 1)->first();

    if (!$costo) {
        return response()->json([
            "message" => 403,
            "message_text" => "El costo ingresado no existe o ya caducó"
        ]);
    }

    $user = auth('api')->user();
    $carts = Cart::where("user_id", $user->id)->get();

    // inicializar acumulador de costo total añadido
    $costo_total = 0;

    foreach ($carts as $cart) {
        // 🔒 PRIORIDAD MÁXIMA: Si el producto tiene cost = 1, NO aplica ningún costo de envío
        if ($cart->product && $cart->product->cost == 1) {
            continue;
        }

        // 🔒 Solo aplica costos si el producto tiene cost = 2 (producto con envío pago)
        if (!$cart->product || $cart->product->cost != 2) {
            continue;
        }

        // 🔒 Evitar aplicar varias veces el mismo costo
        if ($cart->code_costo === $costo->code) {
            continue;
        }

        $applyCosto = false;

        // --- Nivel producto ---
        if ($costo->type_costo == 1) {
            foreach ($costo->products as $costo_product) {
                if ($costo_product->product_id == $cart->product_id) {
                    $applyCosto = true;
                    break;
                }
            }
        }

        // --- Nivel categoría ---
        if ($costo->type_costo == 2) {
            foreach ($costo->categories as $costo_category) {
                if ($costo_category->categorie_id == $cart->product->categorie_first_id) {
                    $applyCosto = true;
                    break;
                }
            }
        }

        // --- Nivel marca ---
        if ($costo->type_costo == 3) {
            foreach ($costo->brands as $costo_brand) {
                if ($costo_brand->brand_id == $cart->product->brand_id) {
                    $applyCosto = true;
                    break;
                }
            }
        }

        if ($applyCosto) {
            $subtotal = $cart->subtotal ?: $cart->price_unit;
            $originalTotal = $subtotal * $cart->quantity;
            $total = $originalTotal;

            if ($costo->type_discount == 1) { // porcentaje
                $total += ($total * ($costo->discount * 0.01));
            }
            if ($costo->type_discount == 2) { // monto fijo
                $total += $costo->discount;
            }

            // compute added amount for reporting
            $added = $total - $originalTotal;
            $costo_total += $added;

            $cart->update([
                "type_discount" => $costo->type_discount,
                "discount" => $costo->discount,
                "code_costo" => $costo->code,
                "subtotal" => $subtotal,
                "total" => $total,
                "type_campaing" => NULL,
                "code_discount" => NULL,
            ]);
        }
    }

    return response()->json([
        "message" => 200,
        "message_text" => "Costo de envío aplicado correctamente",
        "costo" => $costo_total
    ]);
}

    public function remove_costo(Request $request)
    {
        $user = auth('api')->user();
        $carts = Cart::where("user_id", $user->id)->get();

        $removed_amount = 0;

        foreach ($carts as $cart) {
            // 🔒 Productos con cost = 1 no deben tener costo de envío
            if ($cart->product && $cart->product->cost == 1) {
                continue;
            }

            if ($cart->code_costo) {
                // revert to original subtotal/total based on price_unit and quantity
                $originalSubtotal = $cart->price_unit;
                $originalTotal = $originalSubtotal * $cart->quantity;

                // calculate difference to report removed amount
                $removed_amount += ($cart->total - $originalTotal);

                $cart->update([
                    'type_discount' => NULL,
                    'discount' => 0,
                    'code_costo' => NULL,
                    'subtotal' => $originalSubtotal,
                    'total' => $originalTotal,
                ]);
            }
        }

        return response()->json([
            'message' => 200,
            'message_text' => 'Costo de envío removido correctamente',
            'removed' => $removed_amount
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
