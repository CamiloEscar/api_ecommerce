<?php

namespace App\Http\Controllers\Ecommerce;

use App\Http\Controllers\Controller;
use App\Http\Resources\Ecommerce\Sale\SaleCollection;
use App\Http\Resources\Ecommerce\Sale\SaleResource;
use App\Mail\SaleMail;
use App\Models\Product\Product;
use App\Models\Product\ProductVariation;
use App\Models\Sale\Cart;
use App\Models\Sale\Sale;
use App\Models\Sale\SaleAddres;
use App\Models\Sale\SaleDetail;
use App\Models\Sale\SaleTemp;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use MercadoPago\Client\Preference\PreferenceClient;
use MercadoPago\MercadoPagoConfig;

class SaleController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    public function orders(){
        $user = auth("api")->user();

        $sales = Sale::where("user_id", $user->id)->orderBy("id", "desc")->get();

        return response()->json([
            "sales" => SaleCollection::make($sales)
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->request->add(["user_id" => auth("api")->user()->id]);
        $sale = Sale::create($request->all());

        $carts = Cart::where("user_id", auth("api")->user()->id)->get();

        foreach ($carts as $key => $cart) {

            $nCart = $cart;
            $new_detail = [];
            $new_detail = $nCart->toArray();
            $new_detail["sale_id"] = $sale->id;
            SaleDetail::create($new_detail);

            //descuento del stock del producto
            if($cart->product_variation_id){
                $variation = ProductVariation::find($cart->product_variation_id);
                if($variation->variation_father){
                    $variation->variation_father->update([
                        "stock" => $variation->variation_father->stock - $cart->quantity
                    ]);
                    $variation->update([
                        "stock" => $variation->stock - $cart->quantity
                    ]);
                } else{
                    $variation->update([
                        "stock" => $variation->stock - $cart->quantity
                    ]);
                }
            } else {
                $product = Product::find($cart->product_id);
                $product->update([
                    "stock" => $product->stock - $cart->quantity
                ]);
            }
            //TODO: la eliminacion del carrito de compra
            $cart->delete();
        }

        $sale_addres = $request->sale_address;
        $sale_addres["sale_id"] = $sale->id;
        $sale_address = SaleAddres::create($sale_addres);

        //el correo que le debe llegar al cliente con la compra realizada
        $sale_new = Sale::findOrFail($sale->id);
        Mail::to(auth("api")->user()->email)->send(new SaleMail(auth("api")->user(),$sale_new));
        return response()->json([
            "message" => 200
        ]);
    }

    public function checkout_mercadopago(Request $request){

        $response = Http::withHeaders([
            "Content-Type" => "application/json",
            "Authorization" => "Bearer ".env("MERCADOPAGO_KEY")
        ])->get("https://api.mercadopago.com/v1/payments/".$request->n_transaccion);
            // dd(json_decode($response->getBody()->getContents(),true));
            //array que obtenemos con el total y el subtotal
        $format_response = json_decode($response->getBody()->getContents(),true);

        $sale_temp = SaleTemp::where("user_id", auth('api')->user()->id)->first();

        $request->request->add([
            "user_id" => auth("api")->user()->id,
            "total" => $format_response["transaction_amount"],
            "subtotal" => $format_response["transaction_amount"],
            "description" => $sale_temp->description ? $sale_temp->description : NULL,
    ]);
        $sale = Sale::create($request->all());

        $carts = Cart::where("user_id", auth("api")->user()->id)->get();

        foreach ($carts as $key => $cart) {

            $nCart = $cart;
            $new_detail = [];
            $new_detail = $nCart->toArray();
            $new_detail["sale_id"] = $sale->id;
            SaleDetail::create($new_detail);
        }


        $sale_addres = json_decode($sale_temp->sale_address, true);
        $sale_addres["sale_id"] = $sale->id;
        $sale_address = SaleAddres::create($sale_addres);

        //el correo que le debe llegar al cliente con la compra realizada
        $sale_new = Sale::findOrFail($sale->id);
        Mail::to(auth("api")->user()->email)->send(new SaleMail(auth("api")->user(),$sale_new));
        return response()->json([
            "message" => 200
        ]);
    }

    //TODO: COLOCAR ESTO CUANDO HAGA LA INTEGRACION CON MERCADO PAGO
public function mercadopago(Request $request) {
    error_log("=== MERCADOPAGO ENDPOINT LLAMADO ===");
    error_log("Request: " . json_encode($request->all()));

    try {
        // Validar usuario
        $user = auth('api')->user();
        if (!$user) {
            return response()->json(['message' => 'No autenticado'], 401);
        }

        error_log("Usuario autenticado: " . $user->id);

        // Obtener Access Token
        $mpKey = env("MERCADOPAGO_KEY");
        if (!$mpKey) {
            return response()->json(['message' => 'Mercado Pago no configurado'], 500);
        }

        // Preparar datos
        $priceUnit = floatval($request->get("price_unit"));

        // Generar URLs completas
        $baseUrl = env("URL_TIENDA");

        $data = [
            "items" => [
                [
                    "title" => "Compra en Ecommerce Funkos",
                    "quantity" => 1,
                    "currency_id" => "ARS",
                    "unit_price" => $priceUnit,
                ]
            ],
            "back_urls" => [
                "success" => $baseUrl . "/mercado-pago-success",
                "failure" => $baseUrl . "/mercado-pago-failure",
                "pending" => $baseUrl . "/mercado-pago-pending"
            ],
            "auto_return" => "approved",
        ];

        error_log("Datos a enviar: " . json_encode($data));

        // Hacer petición HTTP con cURL
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'https://api.mercadopago.com/checkout/preferences');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $mpKey,
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($curlError) {
            error_log("cURL Error: " . $curlError);
            return response()->json(['message' => 'Error de conexión', 'error' => $curlError], 500);
        }

        error_log("HTTP Code: " . $httpCode);
        error_log("Response: " . $response);

        $responseData = json_decode($response, true);

        if ($httpCode >= 200 && $httpCode < 300) {
            // Éxito
            return response()->json([
                "preference" => $responseData,
            ]);
        } else {
            // Error
            error_log("=== ERROR DE MERCADO PAGO ===");
            error_log("Response completa: " . json_encode($responseData));

            return response()->json([
                'message' => 'Error de Mercado Pago',
                'http_code' => $httpCode,
                'error' => $responseData['message'] ?? 'Error desconocido',
                'details' => $responseData,
            ], 500);
        }

    } catch (\Exception $e) {
        error_log("=== ERROR GENERAL ===");
        error_log("Error: " . $e->getMessage());

        return response()->json([
            'message' => 'Error al procesar el pago',
            'error' => $e->getMessage()
        ], 500);
    }
}
    //funcion para guardar la informacion del checkout de mercado pago, ya que se pierde la informacion cuando se hace una compra por mp
    public function checkout_temp(Request $request) {

        $sale_temp = SaleTemp::where("user_id",auth('api')->user()->id)->first();
        if($sale_temp){
            $sale_temp->update([
                "description" => $request->description,
                "sale_address" => json_encode($request->sale_address),
            ]);
        } else {
            SaleTemp::create([
                "user_id" => auth('api')->user()->id,
                "description" => $request->description,
                "sale_address" => json_encode($request->sale_address),
            ]);
        }


        return response()->json(true);
    }
    //public function checkout_mercadopago

    // $nCart = $cart;


    //descuento del stock del producto
    // if($cart->product_variation_id){
    //     $variation = ProductVariation::find($cart->product_variation_id);
    //     if($variation->variation_father){
    //         $variation->variation_father->update([
    //             "stock" => $variation->variation_father->stock - $cart->quantity
    //         ]);
    //         $variation->update([
    //             "stock" => $variation->stock - $cart->quantity
    //         ]);
    //     } else{
    //         $variation->update([
    //             "stock" => $variation->stock - $cart->quantity
    //         ]);
    //     }
    // } else {
    //     $product = Product::find($cart->product_id);
    //     $product->update([
    //         "stock" => $product->stock - $cart->quantity
    //     ]);
    // }
    // //TODO: la eliminacion del carrito de compra
    // $cart->delete();

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $sale = Sale::where("n_transaccion", $id)->first();

        return response()->json([
            "sale" => SaleResource::make($sale)
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
