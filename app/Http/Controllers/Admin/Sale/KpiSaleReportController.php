<?php

namespace App\Http\Controllers\Admin\Sale;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class KpiSaleReportController extends Controller
{
    public function report_sales_country_for_year(Request $request){

        $year = $request->year;
        $month = $request->month;

        $query = DB::table("sales")->where("sales.deleted_at", NULL)
                    ->join("sale_addres", "sale_addres.sale_id", "=", "sales.id")
                    ->whereYear("sales.created_at", $year);

        if($month){
            $query->whereMonth("sales.created_at", $month);
        }

        $query->select("sale_addres.country_region as country_region",
                DB::raw("ROUND(SUM(sales.total),2) as total_sales"))
                ->groupBy("country_region")
                ->orderBy("total_sales", "desc");
        $query= $query->get();


        return response()->json([
            "sales_for_country" => $query
        ]);
    }
}
