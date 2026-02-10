<?php

namespace App\Http\Controllers\Admin\Product;

use App\Http\Controllers\Controller;
use App\Models\Sale\Sale;
use App\Models\Sale\SaleDetail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProductSalesHistoryController extends Controller
{
    /**
     * Obtener historial de ventas de un producto específico
     */
    public function getSalesHistory($productId, Request $request)
    {
        $perPage = $request->get('per_page', 25);

        // Obtener detalles de venta que incluyen este producto
        $salesDetails = SaleDetail::with(['sale.user', 'sale.sale_addres', 'product'])
            ->where('product_id', $productId)
            ->whereHas('sale') // Solo ventas que existen
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);

        // Formatear los datos
        $salesHistory = $salesDetails->map(function($detail) {
            $sale = $detail->sale;

            if (!$sale) {
            return null;
            }

            return [
                'id' => $sale->id,
                'created_at' => $sale->created_at->format('Y-m-d h:i A'),
                'quantity' => $detail->quantity,
                'price_unit' => $detail->price_unit,
                'subtotal' => $detail->subtotal,
                'discount' => $detail->discount,
                'discount_amount' => ($detail->subtotal * $detail->discount) / 100,
                'total' => $detail->total,
                'currency' => $detail->currency,
                'method_payment' => $sale->method_payment,
                'n_transaccion' => $sale->n_transaccion,
                'user' => $sale->user ? [
                    'full_name' => $sale->user->name . ' ' . ($sale->user->surname ?? ''),
                    'avatar' => $sale->user->avatar,
                ] : null,
                'sale_address' => $sale->sale_addres ? [
                    'name' => $sale->sale_addres->name,
                    'surname' => $sale->sale_addres->surname,
                    'email' => $sale->sale_addres->email,
                    'phone' => $sale->sale_addres->phone,
                    'address' => $sale->sale_addres->address,
                    'city' => $sale->sale_addres->city,
                    'country_region' => $sale->sale_addres->country_region,
                ] : null,
            ];
        });

        return response()->json([
            'sales' => $salesHistory,
            'total' => $salesDetails->lastPage(),
            'current_page' => $salesDetails->currentPage(),
            'per_page' => $salesDetails->perPage(),
            'total_records' => $salesDetails->total(),
        ], 200);
    }

    /**
     * Obtener resumen de ventas del producto
     */
    public function getSalesSummary($productId)
    {
        // Total de ventas
        $totalSales = SaleDetail::where('product_id', $productId)
            ->whereHas('sale')
            ->count();

        // Total de unidades vendidas
        $totalQuantity = SaleDetail::where('product_id', $productId)
            ->whereHas('sale')
            ->sum('quantity');

        // Ingresos totales por moneda
        $revenueARS = SaleDetail::where('product_id', $productId)
            ->where('currency', 'ARS')
            ->whereHas('sale')
            ->sum('total');

        $revenueUSD = SaleDetail::where('product_id', $productId)
            ->where('currency', 'USD')
            ->whereHas('sale')
            ->sum('total');

        // Ventas por método de pago
        $salesByMethod = SaleDetail::select('sales.method_payment', DB::raw('count(*) as total'))
            ->join('sales', 'sale_details.sale_id', '=', 'sales.id')
            ->where('sale_details.product_id', $productId)
            ->groupBy('sales.method_payment')
            ->get();

        // Ventas por mes (últimos 6 meses)
        $salesByMonth = SaleDetail::select(
                DB::raw('DATE_FORMAT(sale_details.created_at, "%Y-%m") as month'),
                DB::raw('SUM(sale_details.quantity) as quantity'),
                DB::raw('SUM(sale_details.total) as revenue')
            )
            ->where('product_id', $productId)
            ->whereHas('sale')
            ->where('sale_details.created_at', '>=', now()->subMonths(6))
            ->groupBy('month')
            ->orderBy('month', 'desc')
            ->get();

        return response()->json([
            'total_sales' => $totalSales,
            'total_quantity' => $totalQuantity,
            'total_revenue_ars' => round($revenueARS, 2),
            'total_revenue_usd' => round($revenueUSD, 2),
            'sales_by_method' => $salesByMethod,
            'sales_by_month' => $salesByMonth,
        ], 200);
    }
    public function updateShippingStatus(Request $request, $id)
{
    $sale = Sale::findOrFail($id);

    $sale->shipping_status = $request->shipping_status;
    $sale->save();

    return response()->json([
        'message' => 200,
        'message_text' => 'Estado actualizado correctamente',
        'sale' => $sale
    ]);
}

}
