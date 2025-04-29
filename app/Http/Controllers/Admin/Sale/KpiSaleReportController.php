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
        $dolar = 1200;

        $query = DB::table("sales")->where("sales.deleted_at", NULL)
                    ->join("sale_addres", "sale_addres.sale_id", "=", "sales.id")
                    ->whereYear("sales.created_at", $year);

        if($month){
            $query->whereMonth("sales.created_at", $month);
        }

        $query->select("sale_addres.country_region as country_region",
                DB::raw("ROUND(SUM(IF(sales.currency_payment = 'USD',sales.total * $dolar, sales.total)),2) as total_sales"))
                ->groupBy("country_region")
                ->orderBy("total_sales", "desc");
        $query= $query->get();


        return response()->json([
            "sales_for_country" => $query
        ]);
    }

    public function report_sales_week_categorias(){

        $dolar = 1200;

        $start_week = Carbon::now()->startOfWeek();
        $end_week = Carbon::now()->endOfWeek();

        $start_week_last = Carbon::now()->subWeek()->startOfWeek();
        $end_week_last = Carbon::now()->subWeek()->endOfWeek();

        $sales_week = DB::table("sales")->where("sales.deleted_at", NULL)
                                        ->whereBetween("sales.created_at", [$start_week->format("Y-m-d")." 00:00:00",$end_week->format("Y-m-d")." 23:59:59"])
                                        ->select(DB::raw("ROUND(SUM(IF(sales.currency_payment = 'USD',sales.total * $dolar, sales.total)),2) as sales_total"))
                                        ->get()
                                        ->sum("sales_total");

        $sales_week_last = DB::table("sales")->where("sales.deleted_at", NULL)
                                        ->whereBetween("sales.created_at", [$start_week_last->format("Y-m-d")." 00:00:00",$end_week_last->format("Y-m-d")." 23:59:59"])
                                        ->select(DB::raw("ROUND(SUM(IF(sales.currency_payment = 'USD',sales.total * $dolar, sales.total)),2) as sales_total"))
                                        ->get()
                                        ->sum("sales_total");

        $porcentageV = 0;
        if($sales_week_last > 0){
            $porcentageV = (($sales_week-$sales_week_last)/$sales_week_last)*100;
        }

        $sales_week_categories = DB::table("sales")->where("sales.deleted_at", NULL)
                                        ->join("sale_details","sale_details.sale_id", "=", "sales.id")
                                        ->join("products", "sale_details.product_id", "=", "products.id")
                                        ->join("categories", "products.categorie_first_id", "=", "categories.id")
                                        ->where("sale_details.deleted_at", NULL)
                                        ->whereBetween("sales.created_at", [$start_week->format("Y-m-d")." 00:00:00",$end_week->format("Y-m-d")." 23:59:59"])
                                        ->select("categories.name as categorie_name", DB::raw("ROUND(SUM(IF(sales.currency_payment = 'USD',sales.total * 1200, sales.total)),2) as categorie_total"))
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

    public function report_sales_week_discounts(){
        $start_week = Carbon::now()->startOfWeek();
        $end_week = Carbon::now()->endOfWeek();

        $start_week_last = Carbon::now()->subWeek()->startOfWeek();
        $end_week_last = Carbon::now()->subWeek()->endOfWeek();

        $sales_week_discounts = DB::table("sales")->where("sales.deleted_at", NULL)
                                        ->join("sale_details", "sale_details.sale_id", "=", "sales.id")
                                        ->where("sale_details.deleted_at", NULL)
                                        ->whereBetween("sales.created_at", [$start_week->format("Y-m-d")." 00:00:00",$end_week->format("Y-m-d")." 23:59:59"])
                                        ->sum("sale_details.discount");

        $sales_week_discounts_last = DB::table("sales")->where("sales.deleted_at", NULL)
                                        ->join("sale_details", "sale_details.sale_id", "=", "sales.id")
                                        ->where("sale_details.deleted_at", NULL)
                                        ->whereBetween("sales.created_at", [$start_week_last->format("Y-m-d")." 00:00:00",$end_week_last->format("Y-m-d")." 23:59:59"])
                                        ->sum("sale_details.discount");

        $porcentageV = 0;
        if($sales_week_discounts_last > 0){
            $porcentageV = (($sales_week_discounts-$sales_week_discounts_last)/$sales_week_discounts_last)*100;
        }

        $sales_week_discounts_for_day = DB::table("sales")->where("sales.deleted_at", NULL)
                                        ->join("sale_details", "sale_details.sale_id", "=", "sales.id")
                                        ->where("sale_details.deleted_at", NULL)
                                        ->whereBetween("sales.created_at", [$start_week->format("Y-m-d")." 00:00:00",$end_week->format("Y-m-d")." 23:59:59"])
                                        ->select(DB::raw("DATE_FORMAT(sales.created_at,'%Y-%m-%d') as date_format"),
                                                          DB::raw("ROUND(SUM(sale_details.discount),2) as discount_total")
                                        )
                                        ->groupBy("date_format")
                                        ->get();

        $discount_for_days = collect([]);

        foreach ($sales_week_discounts_for_day as $key => $sales_week_discount) {
            $discount_for_days->push([
                "date" => $sales_week_discount->date_format,
                "percentage" => round((($sales_week_discount->discount_total) / $sales_week_discounts)*100,2)
            ]);
        }

        return response()->json([
            "discount_for_days" => $discount_for_days,
            "sales_week_discounts" => $sales_week_discounts,
            "porcentageV" => $porcentageV,
        ]);
    }

    public function report_sales_month_selected(Request $request){

        $year = $request->year;
        $month = $request->month;
        $dolar = 1200;

        $sales_for_day_of_month = DB::table("sales")->where("sales.deleted_at", NULL)
                                          ->whereYear("sales.created_at", $year)
                                          ->whereMonth("sales.created_at", $month)
                                          ->select(
                                            DB::raw("DATE_FORMAT(sales.created_at,'%Y-%m-%d') as date_format"),
                                                     DB::raw("DATE_FORMAT(sales.created_at, '%m-%d') as date_format_day"),
                                                     DB::raw("ROUND(SUM(IF(sales.currency_payment = 'USD',sales.total * $dolar, sales.total)),2) as sales_total")
                                          )
                                          ->groupBy("date_format","date_format_day")
                                          ->get();


        //metodo para obtener el mes anterior
        $month_last = Carbon::parse($year.'-'.$month.'-'.'01')->subMonth();

        // dd($month_last);

        $sales_for_month_last = DB::table("sales")->where("sales.deleted_at", NULL)
                                ->whereYear("sales.created_at", $month_last->format("Y"))
                                ->whereMonth("sales.created_at", $month_last->format("m"))
                                ->select(DB::raw("ROUND(SUM(IF(sales.currency_payment = 'USD',sales.total * $dolar, sales.total)),2) as sales_total"))
                                ->get()
                                ->sum("sales_total");

        $porcentageV = 0;
        if($sales_for_month_last > 0){
            $porcentageV = (($sales_for_day_of_month->sum("sales_total")-$sales_for_month_last)/$sales_for_month_last)*100;
        }

        return response()->json([
            "porcentageV" => round($porcentageV,2),
            // "sales_for_month_last" => $sales_for_month_last,
            "total_sales_for_month" => round($sales_for_day_of_month->sum("sales_total"),2),
            "sales_for_day_of_month" => ($sales_for_day_of_month),
        ]);

    }
}
