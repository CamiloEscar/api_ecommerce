<?php

namespace App\Http\Controllers\Admin\Product;

use App\Http\Controllers\Controller;
use App\Models\Product\Product;
use App\Models\Product\ProductStockMovement;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ProductStockMovementController extends Controller
{
    /**
     * Display a listing of stock movements for a product.
     */
    public function index(Request $request)
    {
        $product_id = $request->product_id;

        $movements = ProductStockMovement::where('product_id', $product_id)
            ->orderBy("id", "desc")
            ->get();

        return response()->json([
            "movements" => $movements->map(function ($movement) {
                return [
                    'id' => $movement->id,
                    'product_id' => $movement->product_id,
                    'type' => $movement->type,
                    'quantity' => $movement->quantity,
                    'stock_before' => $movement->stock_before,
                    'stock_after' => $movement->stock_after,
                    'description' => $movement->description,
                    'reference' => $movement->reference,
                    'user_id' => $movement->user_id,
                    'user' => $movement->user ? [
                        'id' => $movement->user->id,
                        'name' => $movement->user->name,
                        'email' => $movement->user->email,
                    ] : null,
                    'created_at' => $movement->created_at->format("Y-m-d H:i:s"),
                ];
            })
        ]);
    }

    /**
     * Store a newly created stock movement.
     */
    public function store(Request $request)
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
            'type' => 'required|in:ingreso,egreso,ajuste',
            'quantity' => 'required|integer|min:1',
            'description' => 'nullable|string',
            'reference' => 'nullable|string',
        ]);

        DB::beginTransaction();
        try {
            $product = Product::findOrFail($request->product_id);

            $stock_before = $product->stock;
            $quantity = $request->quantity;

            // Calcular el nuevo stock según el tipo de movimiento
            switch ($request->type) {
                case 'ingreso':
                    $stock_after = $stock_before + $quantity;
                    break;
                case 'egreso':
                    if ($stock_before < $quantity) {
                        return response()->json([
                            "message" => 403,
                            "message_text" => "No hay suficiente stock disponible. Stock actual: $stock_before"
                        ], 403);
                    }
                    $stock_after = $stock_before - $quantity;
                    $quantity = -$quantity; // Guardamos como negativo
                    break;
                case 'ajuste':
                    // Para ajuste, la cantidad es el nuevo stock total
                    $stock_after = $quantity;
                    $quantity = $stock_after - $stock_before; // Diferencia
                    break;
            }

            // Actualizar el stock del producto
            $product->stock = $stock_after;
            $product->save();

            // Crear el registro del movimiento
            $movement = ProductStockMovement::create([
                'product_id' => $request->product_id,
                'type' => $request->type,
                'quantity' => $quantity,
                'stock_before' => $stock_before,
                'stock_after' => $stock_after,
                'description' => $request->description,
                'reference' => $request->reference,
                'user_id' => Auth::id(),
            ]);

            DB::commit();

            return response()->json([
                "message" => 200,
                "message_text" => "Movimiento de stock registrado correctamente",
                "movement" => [
                    'id' => $movement->id,
                    'product_id' => $movement->product_id,
                    'type' => $movement->type,
                    'quantity' => $movement->quantity,
                    'stock_before' => $movement->stock_before,
                    'stock_after' => $movement->stock_after,
                    'description' => $movement->description,
                    'reference' => $movement->reference,
                    'user_id' => $movement->user_id,
                    'user' => $movement->user ? [
                        'id' => $movement->user->id,
                        'name' => $movement->user->name,
                        'email' => $movement->user->email,
                    ] : null,
                    'created_at' => $movement->created_at->format("Y-m-d H:i:s"),
                ],
                "product_stock" => $stock_after
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                "message" => 500,
                "message_text" => "Error al registrar el movimiento: " . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified stock movement.
     */
    public function show(string $id)
    {
        $movement = ProductStockMovement::findOrFail($id);

        return response()->json([
            'id' => $movement->id,
            'product_id' => $movement->product_id,
            'type' => $movement->type,
            'quantity' => $movement->quantity,
            'stock_before' => $movement->stock_before,
            'stock_after' => $movement->stock_after,
            'description' => $movement->description,
            'reference' => $movement->reference,
            'user_id' => $movement->user_id,
            'user' => $movement->user ? [
                'id' => $movement->user->id,
                'name' => $movement->user->name,
                'email' => $movement->user->email,
            ] : null,
            'created_at' => $movement->created_at->format("Y-m-d H:i:s"),
        ]);
    }

    /**
     * Remove the specified stock movement.
     * IMPORTANTE: Eliminar un movimiento NO debería revertir el stock automáticamente
     * ya que podría causar inconsistencias. En su lugar, se debe crear un movimiento
     * de ajuste para corregir.
     */
    public function destroy(string $id)
    {
        $movement = ProductStockMovement::findOrFail($id);

        // Solo permitir eliminar si es el último movimiento
        $last_movement = ProductStockMovement::where('product_id', $movement->product_id)
            ->orderBy('id', 'desc')
            ->first();

        if ($last_movement->id !== $movement->id) {
            return response()->json([
                "message" => 403,
                "message_text" => "Solo se puede eliminar el último movimiento de stock"
            ], 403);
        }

        DB::beginTransaction();
        try {
            // Revertir el stock al estado anterior
            $product = Product::findOrFail($movement->product_id);
            $product->stock = $movement->stock_before;
            $product->save();

            $movement->delete();

            DB::commit();

            return response()->json([
                "message" => 200,
                "message_text" => "Movimiento eliminado y stock revertido correctamente"
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                "message" => 500,
                "message_text" => "Error al eliminar el movimiento: " . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get stock summary for a product
     */
    public function summary(Request $request)
    {
        $product_id = $request->product_id;
        $product = Product::findOrFail($product_id);

        $total_ingresos = ProductStockMovement::where('product_id', $product_id)
            ->where('type', 'ingreso')
            ->sum('quantity');

        $total_egresos = ProductStockMovement::where('product_id', $product_id)
            ->where('type', 'egreso')
            ->sum('quantity');

        $total_ajustes = ProductStockMovement::where('product_id', $product_id)
            ->where('type', 'ajuste')
            ->sum('quantity');

        return response()->json([
            'product_id' => $product_id,
            'current_stock' => $product->stock,
            'total_ingresos' => $total_ingresos,
            'total_egresos' => abs($total_egresos),
            'total_ajustes' => $total_ajustes,
            'total_movements' => ProductStockMovement::where('product_id', $product_id)->count(),
        ]);
    }
}
