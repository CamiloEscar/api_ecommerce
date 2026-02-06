<?php

namespace App\Http\Controllers\Ecommerce;

use App\Http\Controllers\Controller;
use App\Http\Resources\Ecommerce\Cart\CartEcommerceCollection;
use App\Http\Resources\Ecommerce\Cart\CartEcommerceResource;
use App\Models\Costo\Costo;
use App\Models\Cupone\Cupone;
use App\Models\Cupone\CuponeUserUsage;
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

        // ✅ Validar stock para cada item del carrito
        foreach ($carts as $cart) {
            $stockDisponible = 0;
            $stockSuficiente = true;

            if ($cart->product_variation_id) {
                // Producto con variación
                $variation = ProductVariation::find($cart->product_variation_id);
                if ($variation) {
                    $stockDisponible = $variation->stock;
                    $stockSuficiente = $variation->stock >= $cart->quantity;
                } else {
                    $stockSuficiente = false;
                    $stockDisponible = 0;
                }
            } else {
                // Producto simple
                $product = Product::find($cart->product_id);
                if ($product) {
                    $stockDisponible = $product->stock;
                    $stockSuficiente = $product->stock >= $cart->quantity;
                } else {
                    $stockSuficiente = false;
                    $stockDisponible = 0;
                }
            }

            // Agregar información de stock al objeto cart
            $cart->stock_disponible = $stockDisponible;
            $cart->stock_suficiente = $stockSuficiente;
        }


        //envia el json al front
        return response()->json([
            "carts" => CartEcommerceCollection::make($carts),
        ]);
    }

    /**
     * Validar stock antes del checkout
     */
    public function validate_stock()
    {
        $user = auth('api')->user();
        $carts = Cart::where("user_id", $user->id)->get();

        $items_sin_stock = [];
        $items_stock_insuficiente = [];

        foreach ($carts as $cart) {
            if ($cart->product_variation_id) {
                // Producto con variación
                $variation = ProductVariation::find($cart->product_variation_id);

                if (!$variation) {
                    $items_sin_stock[] = [
                        'cart_id' => $cart->id,
                        'product_name' => $cart->product->title ?? 'Producto eliminado',
                        'variation' => 'Variación no disponible'
                    ];
                } elseif ($variation->stock < $cart->quantity) {
                    $items_stock_insuficiente[] = [
                        'cart_id' => $cart->id,
                        'product_name' => $cart->product->title,
                        'variation' => $cart->product_variation->propertie->name ?? '',
                        'cantidad_solicitada' => $cart->quantity,
                        'stock_disponible' => $variation->stock
                    ];
                }
            } else {
                // Producto simple
                $product = Product::find($cart->product_id);

                if (!$product) {
                    $items_sin_stock[] = [
                        'cart_id' => $cart->id,
                        'product_name' => 'Producto no disponible'
                    ];
                } elseif ($product->stock < $cart->quantity) {
                    $items_stock_insuficiente[] = [
                        'cart_id' => $cart->id,
                        'product_name' => $product->title,
                        'cantidad_solicitada' => $cart->quantity,
                        'stock_disponible' => $product->stock
                    ];
                }
            }
        }

        if (count($items_sin_stock) > 0 || count($items_stock_insuficiente) > 0) {
            return response()->json([
                "message" => 403,
                "message_text" => "Algunos productos en tu carrito no están disponibles",
                "items_sin_stock" => $items_sin_stock,
                "items_stock_insuficiente" => $items_stock_insuficiente
            ]);
        }

        return response()->json([
            "message" => 200,
            "message_text" => "Todos los productos están disponibles"
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

        //  VALIDAR SI EL USUARIO YA USÓ ESTE CUPÓN
    if ($cupon->hasBeenUsedByUser($user->id)) {
        return response()->json([
            "message" => 403,
            "message_text" => "Ya has utilizado este cupón anteriormente"
        ]);
    }

    $carts = Cart::where("user_id", $user->id)->get();

    if ($carts->isEmpty()) {
        return response()->json([
            "message" => 403,
            "message_text" => "No tienes productos en el carrito"
        ]);
    }

        $appliedToAnyProduct = false;

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
            $appliedToAnyProduct = true;
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

    if (!$appliedToAnyProduct) {
            return response()->json([
                "message" => 403,
                "message_text" => "Este cupón no aplica a ninguno de los productos en tu carrito"
            ]);
        }

        // ✅ REGISTRAR EL USO DEL CUPÓN
        CuponeUserUsage::create([
            'cupone_id' => $cupon->id,
            'user_id' => $user->id,
            'used_at' => now()
        ]);

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

    $costo_total = 0;

    foreach ($carts as $cart) {
        // Saltar si ya tiene este costo aplicado
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
            // Calcular el monto de envio SIN tocar subtotal ni total del item
            $costoEnvioItem = 0;
            if ($costo->type_discount == 1) { // porcentaje
                $costoEnvioItem = ($cart->total) * ($costo->discount * 0.01);
            }
            if ($costo->type_discount == 2) { // monto fijo
                $costoEnvioItem = $costo->discount;
            }

            $costo_total += $costoEnvioItem;

            // SOLO guardar la referencia al costo, NO modificar subtotal/total/type_discount/discount
            $cart->update([
                "code_costo" => $costo->code,
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

        foreach ($carts as $cart) {
            if ($cart->code_costo) {
                // SOLO limpiar la referencia al costo, NO tocar subtotal/total/discount
                $cart->update([
                    'code_costo' => NULL,
                ]);
            }
        }

        return response()->json([
            'message' => 200,
            'message_text' => 'Costo de envío removido correctamente',
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
    // public function update(Request $request, string $id)
    // {
    //     $user = auth('api')->user();

    //     //validamos si existe el producto con variacion
    //     if ($request->product_variation_id) {
    //         $is_exists_cart_variations = Cart::where("product_variation_id", $request->product_variation_id)
    //             ->where("product_id", $request->product_id)
    //             ->where("id", '<>', $id)
    //             ->where("user_id", $user->id)
    //             ->first();

    //         if ($is_exists_cart_variations) {
    //             return response()->json([
    //                 "message" => 403,
    //                 "message_text" => "El producto junto con la variacion ya ha sido agregado, aumente la cantidad en el carrito "
    //             ]);
    //         } else {
    //             $variation = ProductVariation::find($request->product_variation_id);

    //             if ($variation && $variation->stock < $request->quantity) {
    //                 return response()->json([
    //                     "message" => 403,
    //                     "message_text" => "De esa variacion no se puede agregar mas productos en el carrito por falta de stock"
    //                 ]);
    //             }
    //         }
    //     } else {
    //         //sin variacion
    //         $is_exists_cart_simple = Cart::where("product_variation_id", NULL)
    //             ->where("product_id", $request->product_id)
    //             ->where("id", '<>', $id)
    //             ->where("user_id", $user->id)
    //             ->first();

    //         if ($is_exists_cart_simple) {
    //             return response()->json([
    //                 "message" => 403,
    //                 "message_text" => "El producto ya ha sido agregado, aumente la cantidad en el carrito "
    //             ]);
    //         } else {
    //             $product = Product::find($request->product_id);
    //             if ($product->stock < $request->quantity) {
    //                 return response()->json([
    //                     "message" => 403,
    //                     "message_text" => "No se puede agregar mas productos en el carrito por falta de stock"
    //                 ]);
    //             }
    //         }
    //     }

    //     $cart = Cart::findOrFail($id);
    //     $cart->update($request->all());

    //     return response()->json([
    //         "cart" => CartEcommerceResource::make($cart)
    //     ]);
    // }

public function update(Request $request, string $id)
{
    $user = auth('api')->user();
    $cart = Cart::where("id", $id)->where("user_id", $user->id)->firstOrFail();
    $quantity = (int) $request->quantity;

    if ($quantity < 1) {
        return response()->json(["message" => 403, "message_text" => "La cantidad mínima es 1"]);
    }

    // Validar stock...
    if ($cart->product_variation_id) {
        $variation = ProductVariation::find($cart->product_variation_id);
        if (!$variation || $variation->stock < $quantity) {
            return response()->json(["message" => 403, "message_text" => "Stock insuficiente"]);
        }
    } else {
        $product = Product::find($cart->product_id);
        if (!$product || $product->stock < $quantity) {
            return response()->json(["message" => 403, "message_text" => "Stock insuficiente"]);
        }
    }

    // Recalcular: subtotal es el precio unitario con descuento, total es subtotal * quantity
    // NO incluir costo de envio en el total del item
    $subtotal = $cart->subtotal ?: $cart->price_unit;
    $total = $subtotal * $quantity;

    $cart->update([
        "quantity" => $quantity,
        "total" => $total
    ]);

    return response()->json(["cart" => CartEcommerceResource::make($cart)]);
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
