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
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SaleController extends Controller
{
    public function index()
    {
        //
    }

    public function orders()
    {
        $user = auth("api")->user();
        $sales = Sale::where("user_id", $user->id)->orderBy("id", "desc")->get();

        return response()->json([
            "sales" => SaleCollection::make($sales)
        ]);
    }

    public function store(Request $request)
    {
        $request->request->add(["user_id" => auth("api")->user()->id]);
        $sale = Sale::create($request->all());

        $this->processCarts($sale);
        $this->createSaleAddress($sale, $request->sale_address);
        $this->sendConfirmationEmail($sale);

        return response()->json(["message" => 200]);
    }

    public function show(string $id)
    {
        $sale = Sale::where("n_transaccion", $id)->first();
        return response()->json(["sale" => SaleResource::make($sale)]);
    }

    // ==================== MERCADO PAGO ====================

    public function mercadopago(Request $request)
    {
        Log::info("=== MERCADOPAGO: Creando preferencia ===");

        try {
            $user = auth('api')->user();
            if (!$user) {
                return response()->json(['message' => 'No autenticado'], 401);
            }

            $mpKey = env("MERCADOPAGO_KEY");
            if (!$mpKey) {
                return response()->json(['message' => 'Mercado Pago no configurado'], 500);
            }

            $preference = $this->createMercadoPagoPreference(
                floatval($request->get("price_unit")),
                $user->id
            );

            if (!$preference['success']) {
                return response()->json($preference['error'], $preference['http_code']);
            }

            return response()->json(["preference" => $preference['data']]);

        } catch (\Exception $e) {
            Log::error("Error en mercadopago: " . $e->getMessage());
            return response()->json(['message' => 'Error al procesar el pago', 'error' => $e->getMessage()], 500);
        }
    }

public function mercadopagoCallbackSuccess(Request $request)
{
    Log::info("=== MERCADOPAGO: Callback Success ===", $request->all());

    $paymentId = $request->get('payment_id');
    $externalReference = $request->get('external_reference'); // user_id

    try {
        // Verificar el pago con Mercado Pago
        $paymentInfo = $this->verifyMercadoPagoPayment($paymentId);

        if ($paymentInfo && $paymentInfo['status'] === 'approved') {
            Log::info("Pago verificado y aprobado", ['payment' => $paymentInfo]);

            // 🆕 GUARDAR LA COMPRA EN LA BASE DE DATOS
            $this->createSaleFromMercadoPago($paymentInfo, $externalReference);
        }

    } catch (\Exception $e) {
        Log::error("Error procesando pago: " . $e->getMessage());
    }

    // Redirigir a Angular
    $frontendUrl = env("URL_TIENDA");
    $redirectUrl = $frontendUrl . "/mercado-pago-success?" . http_build_query([
        'payment_id' => $paymentId,
        'status' => $request->get('status'),
        'collection_status' => $request->get('collection_status'),
        'payment_type' => $request->get('payment_type'),
        'merchant_order_id' => $request->get('merchant_order_id'),
    ]);

    return redirect($redirectUrl);
}

// 🆕 NUEVO MÉTODO: Crear la venta desde Mercado Pago
private function createSaleFromMercadoPago($paymentInfo, $userId)
{
    // Obtener datos temporales guardados
    $saleTemp = SaleTemp::where("user_id", $userId)->first();

    if (!$saleTemp) {
        Log::error("No se encontró SaleTemp para user_id: " . $userId);
        return;
    }

    // Crear la venta
    $sale = Sale::create([
        "user_id" => $userId,
        "method_payment" => "MERCADOPAGO",
        "currency_total" => "ARS",
        "currency_payment" => "ARS",
        "total" => $paymentInfo['transaction_amount'],
        "subtotal" => $paymentInfo['transaction_amount'],
        "n_transaccion" => $paymentInfo['id'],
        "discount" => 0,
        "price_dolar" => 0,
        "description" => $saleTemp->description ?? "Compra con Mercado Pago",
    ]);

    Log::info("Sale creada", ['sale_id' => $sale->id]);

    // Procesar carrito y crear detalles
    $this->processCarts($sale);

    // Crear dirección de envío
    $saleAddress = json_decode($saleTemp->sale_address, true);
    if ($saleAddress) {
        $this->createSaleAddress($sale, $saleAddress);
    }

    // 🆕 ARREGLO: Enviar email de confirmación
    try {
        $user = \App\Models\User::find($userId);
        if ($user) {
            // Recargar la venta con todas sus relaciones
            $sale_complete = Sale::with(['sale_details', 'sale_address'])->findOrFail($sale->id);

            Log::info("Enviando email a: " . $user->email);
            Mail::to($user->email)->send(new SaleMail($user, $sale_complete));
            Log::info("Email enviado exitosamente");
        }
    } catch (\Exception $e) {
        Log::error("Error enviando email: " . $e->getMessage());
    }

    // Limpiar datos temporales
    $saleTemp->delete();

    Log::info("Compra completada exitosamente", ['sale_id' => $sale->id]);
}

    public function mercadopagoCallbackFailure(Request $request)
    {
        Log::info("=== MERCADOPAGO: Callback Failure ===", $request->all());
        return redirect(env("URL_TIENDA") . "/mercado-pago-failure");
    }

    public function mercadopagoCallbackPending(Request $request)
    {
        Log::info("=== MERCADOPAGO: Callback Pending ===", $request->all());
        return redirect(env("URL_TIENDA") . "/mercado-pago-pending");
    }

    public function mercadopagoWebhook(Request $request)
    {
        Log::info("=== MERCADOPAGO: Webhook (IPN) ===", $request->all());

        $type = $request->get('type');
        $dataId = $request->get('data.id') ?? $request->input('data')['id'] ?? null;

        if ($type === 'payment' && $dataId) {
            $paymentInfo = $this->verifyMercadoPagoPayment($dataId);

            if ($paymentInfo) {
                Log::info("Webhook - Pago actualizado", ['payment' => $paymentInfo]);
                // TODO: Actualizar estado del pago en base de datos
            }
        }

        return response()->json(['status' => 'ok'], 200);
    }

    public function checkout_temp(Request $request)
    {
        $sale_temp = SaleTemp::where("user_id", auth('api')->user()->id)->first();

        if ($sale_temp) {
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

    // ==================== MÉTODOS PRIVADOS ====================

    private function createMercadoPagoPreference($priceUnit, $userId)
    {
        $backendUrl = env("APP_URL");

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
                "success" => $backendUrl . "/api/ecommerce/mercadopago/callback/success",
                "failure" => $backendUrl . "/api/ecommerce/mercadopago/callback/failure",
                "pending" => $backendUrl . "/api/ecommerce/mercadopago/callback/pending"
            ],
            "auto_return" => "approved",
            "external_reference" => (string)$userId,
            "notification_url" => $backendUrl . "/api/ecommerce/mercadopago/webhook",
        ];

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'https://api.mercadopago.com/checkout/preferences');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Authorization: Bearer ' . env("MERCADOPAGO_KEY"),
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $responseData = json_decode($response, true);

        if ($httpCode >= 200 && $httpCode < 300) {
            return ['success' => true, 'data' => $responseData];
        }

        Log::error("Error creando preferencia MP", ['response' => $responseData]);
        return [
            'success' => false,
            'http_code' => $httpCode,
            'error' => [
                'message' => 'Error de Mercado Pago',
                'details' => $responseData
            ]
        ];
    }

    private function verifyMercadoPagoPayment($paymentId)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "https://api.mercadopago.com/v1/payments/{$paymentId}");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . env("MERCADOPAGO_KEY"),
        ]);

        $paymentInfo = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        return $httpCode === 200 ? json_decode($paymentInfo, true) : null;
    }

    private function processCarts($sale)
{
    $userId = $sale->user_id ?? auth("api")->user()->id;
    $carts = Cart::where("user_id", $userId)->get();

    foreach ($carts as $cart) {
        // Crear detalle de venta
        $new_detail = $cart->toArray();
        $new_detail["sale_id"] = $sale->id;
        SaleDetail::create($new_detail);

        // Actualizar stock
        if ($cart->product_variation_id) {
            $this->updateVariationStock($cart);
        } else {
            $this->updateProductStock($cart);
        }
    }

    // 🆕 Borrar carts AL FINAL (después de crear todos los detalles)
    Cart::where("user_id", $userId)->delete();
}

    private function updateVariationStock($cart)
    {
        $variation = ProductVariation::find($cart->product_variation_id);

        if ($variation->variation_father) {
            $variation->variation_father->update([
                "stock" => $variation->variation_father->stock - $cart->quantity
            ]);
        }

        $variation->update([
            "stock" => $variation->stock - $cart->quantity
        ]);
    }

    private function updateProductStock($cart)
    {
        $product = Product::find($cart->product_id);
        $product->update([
            "stock" => $product->stock - $cart->quantity
        ]);
    }

    private function createSaleAddress($sale, $addressData)
    {
        $addressData["sale_id"] = $sale->id;
        SaleAddres::create($addressData);
    }

    private function sendConfirmationEmail($sale)
    {
        $sale_new = Sale::findOrFail($sale->id);
        Mail::to(auth("api")->user()->email)->send(
            new SaleMail(auth("api")->user(), $sale_new)
        );
    }
}
