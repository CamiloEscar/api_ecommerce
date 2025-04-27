<?php

namespace App\Http\Controllers\Admin\Sale;

use App\Http\Controllers\Controller;
use App\Http\Resources\Ecommerce\Sale\SaleCollection;
use App\Models\Sale\Sale;
use Illuminate\Http\Request;

class SalesController extends Controller
{
   public function list(Request $request) {

    $search = $request->search;

    $start_date = $request->start_date;
    $end_date = $request->end_date;
    $brand_id = $request->brand_id;
    $categorie_first_id = $request->categorie_first_id;
    $categorie_second_id = $request->categorie_second_id;
    $categorie_third_id = $request->categorie_third_id;
    $method_payment = $request->method_payment;


    $sales = Sale::filterAdvanceAdmin(  $search,
                                        $start_date,
                                        $end_date,
                                        $brand_id,
                                        $categorie_first_id,
                                        $categorie_second_id,
                                        $categorie_third_id,
                                        $method_payment,)
                                        ->orderBy('id', 'desc')
                                        ->paginate(25);

    return response()->json([
        'total' => $sales->total(),
        'sale' => SaleCollection::make($sales),
    ]);
   }
}
