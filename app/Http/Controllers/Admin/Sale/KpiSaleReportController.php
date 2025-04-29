<?php

namespace App\Http\Controllers\Admin\Sale;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
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

    public function report_sales_week_categorias(){


        $start_week = Carbon::now()->startOfWeek();
        $end_week = Carbon::now()->endOfWeek();

        $start_week_last = Carbon::now()->subWeek()->startOfWeek();
        $end_week_last = Carbon::now()->subWeek()->endOfWeek();

        $sales_week = DB::table("sales")->where("sales.deleted_at", NULL)
                                        ->whereBetween("sales.created_at", [$start_week->format("Y-m-d")." 00:00:00",$end_week->format("Y-m-d")." 23:59:59"])
                                        ->sum("sales.total");

        $sales_week_last = DB::table("sales")->where("sales.deleted_at", NULL)
                                        ->whereBetween("sales.created_at", [$start_week_last->format("Y-m-d")." 00:00:00",$end_week_last->format("Y-m-d")." 23:59:59"])
                                        ->sum("sales.total");

        $porcentageV = 0;
        if($sales_week_last > 0){
            $porcentageV = (($sales_week-$sales_week_last)/$sales_week_last)*100;
        }

        $sales_week_categories = DB::table("sales")->where("sales.deleted_at", NULL)
                                        ->join("sale_details","sale_details.sale_id", "=", "sales.id")
                                        ->join("products", "sale_details.product_id", "=", "products.id")
                                        ->join("categories", "products.categorie_first_id", "=", "categories.id")
                                        ->whereBetween("sales.created_at", [$start_week->format("Y-m-d")." 00:00:00",$end_week->format("Y-m-d")." 23:59:59"])
                                        ->select("categories.name as categorie_name", DB::raw("ROUND(SUM(sales.total),2) as categorie_total"))
                                        ->groupBy("categorie_name")
                                        ->orderBy("categorie_total", "desc")
                                        ->take(3)
                                        ->get();


        return response()->json([
            "sales_week" => round($sales_week,2),
            "porcentageV" => round($porcentageV,2),
            "sales_week_categories" => $sales_week_categories,
        ]);


        // dd($start_week, $end_week);
        // dd($start_week_last, $end_week_last);
    }
}
