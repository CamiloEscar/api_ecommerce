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

    public function report_sales_week_categorias() {
        $dolar = 1200;

        $start_week = Carbon::now()->startOfWeek();
        $end_week = Carbon::now()->endOfWeek();

        $start_week_last = Carbon::now()->subWeek()->startOfWeek();
        $end_week_last = Carbon::now()->subWeek()->endOfWeek();

        // Ventas totales esta semana
        $sales_week = DB::table("sales")
            ->whereNull("sales.deleted_at")
            ->whereBetween("sales.created_at", [$start_week->format("Y-m-d")." 00:00:00", $end_week->format("Y-m-d")." 23:59:59"])
            ->select(DB::raw("SUM(IF(sales.currency_payment = 'USD', sales.total * $dolar, sales.total)) as total"))
            ->value("total");

        // Ventas totales semana pasada
        $sales_week_last = DB::table("sales")
            ->whereNull("sales.deleted_at")
            ->whereBetween("sales.created_at", [$start_week_last->format("Y-m-d")." 00:00:00", $end_week_last->format("Y-m-d")." 23:59:59"])
            ->select(DB::raw("SUM(IF(sales.currency_payment = 'USD', sales.total * $dolar, sales.total)) as total"))
            ->value("total");

        $porcentageV = 0;
        if ($sales_week_last > 0) {
            $porcentageV = (($sales_week - $sales_week_last) / $sales_week_last) * 100;
        }

        // Ventas por categoría (top 3)
        $sales_week_categories = DB::table("sales")
            ->join("sale_details", "sale_details.sale_id", "=", "sales.id")
            ->join("products", "sale_details.product_id", "=", "products.id")
            ->join("categories", "products.categorie_first_id", "=", "categories.id")
            ->whereNull("sales.deleted_at")
            ->whereNull("sale_details.deleted_at")
            ->whereBetween("sales.created_at", [$start_week->format("Y-m-d") . " 00:00:00", $end_week->format("Y-m-d") . " 23:59:59"])
            ->select(
                "categories.name as categorie_name",
                DB::raw("ROUND(SUM(IF(sale_details.currency = 'USD', sale_details.total * $dolar, sale_details.total)), 2) as categorie_total")
            )
            ->groupBy("categorie_name")
            ->orderByDesc("categorie_total")
            ->take(3)
            ->get();

        return response()->json([
            "sales_week" => round($sales_week, 2),
            "porcentageV" => round($porcentageV, 2),
            "sales_week_categories" => $sales_week_categories,
        ]);
    }


    public function report_sales_week_discounts(){
        $dolar = 1200;

        $start_week = Carbon::now()->startOfWeek();
        $end_week = Carbon::now()->endOfWeek();

        $start_week_last = Carbon::now()->subWeek()->startOfWeek();
        $end_week_last = Carbon::now()->subWeek()->endOfWeek();

        // Total descuentos esta semana
        $sales_week_discounts = DB::table("sales")->whereNull("sales.deleted_at")
            ->join("sale_details", "sale_details.sale_id", "=", "sales.id")
            ->whereNull("sale_details.deleted_at")
            ->whereBetween("sales.created_at", [$start_week->format("Y-m-d") . " 00:00:00", $end_week->format("Y-m-d") . " 23:59:59"])
            ->select(DB::raw("SUM(IF(sale_details.currency = 'USD', sale_details.discount * $dolar, sale_details.discount)) as total"))
            ->value("total");

        // Total descuentos semana pasada
        $sales_week_discounts_last = DB::table("sales")->whereNull("sales.deleted_at")
            ->join("sale_details", "sale_details.sale_id", "=", "sales.id")
            ->whereNull("sale_details.deleted_at")
            ->whereBetween("sales.created_at", [$start_week_last->format("Y-m-d") . " 00:00:00", $end_week_last->format("Y-m-d") . " 23:59:59"])
            ->select(DB::raw("SUM(IF(sale_details.currency = 'USD', sale_details.discount * $dolar, sale_details.discount)) as total"))
            ->value("total");

        $porcentageV = 0;
        if ($sales_week_discounts_last > 0) {
            $porcentageV = (($sales_week_discounts - $sales_week_discounts_last) / $sales_week_discounts_last) * 100;
        }

        // Descuentos por día de esta semana
        $sales_week_discounts_for_day = DB::table("sales")->whereNull("sales.deleted_at")
            ->join("sale_details", "sale_details.sale_id", "=", "sales.id")
            ->whereNull("sale_details.deleted_at")
            ->whereBetween("sales.created_at", [$start_week->format("Y-m-d") . " 00:00:00", $end_week->format("Y-m-d") . " 23:59:59"])
            ->select(
                DB::raw("DATE_FORMAT(sales.created_at,'%Y-%m-%d') as date_format"),
                DB::raw("ROUND(SUM(IF(sale_details.currency = 'USD', sale_details.discount * $dolar, sale_details.discount)),2) as discount_total")
            )
            ->groupBy("date_format")
            ->get();

        // Porcentaje diario
        $discount_for_days = collect();
        foreach ($sales_week_discounts_for_day as $day) {
            $percentage = $sales_week_discounts > 0
                ? round(($day->discount_total / $sales_week_discounts) * 100, 2)
                : 0;
            $discount_for_days->push([
                "date" => $day->date_format,
                "percentage" => $percentage,
            ]);
        }

        return response()->json([
            "discount_for_days" => $discount_for_days,
            "sales_week_discounts" => round($sales_week_discounts, 2),
            "porcentageV" => round($porcentageV, 2),
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

    public function report_sales_for_month_year_selected(Request $request){
        $year = $request->year;
        $dolar = 1200;

        // Ventas por mes del año seleccionado
        $query = DB::table("sales")->whereNull("sales.deleted_at")
            ->whereYear("sales.created_at", $year)
            ->select(
                DB::raw("DATE_FORMAT(sales.created_at, '%Y-%m') as date_format_month"),
                DB::raw("ROUND(SUM(IF(sales.currency_payment = 'USD', sales.total * $dolar, sales.total)),2) as sale_total")
            )
            ->groupBy("date_format_month")
            ->get();

        // Ventas por mes del año anterior
        $query_last = DB::table("sales")->whereNull("sales.deleted_at")
            ->whereYear("sales.created_at", $year - 1)
            ->select(
                DB::raw("DATE_FORMAT(sales.created_at, '%Y-%m') as date_format_month"),
                DB::raw("ROUND(SUM(IF(sales.currency_payment = 'USD', sales.total * $dolar, sales.total)),2) as sale_total")
            )
            ->groupBy("date_format_month")
            ->get();

        // Descuentos por código de descuento
        $query_discount = DB::table("sales")->whereNull("sales.deleted_at")
            ->join("sale_details", "sale_details.sale_id", "=", "sales.id")
            ->whereNull("sale_details.deleted_at")
            ->whereYear("sales.created_at", $year)
            ->whereNotNull("sale_details.code_discount")
            ->select(
                DB::raw("ROUND(SUM(IF(sale_details.currency = 'USD', sale_details.discount * $dolar, sale_details.discount)),2) as discount_total"),
                DB::raw("COUNT(*) as count_total")
            )
            ->get();

        // Descuentos por cupones
        $query_cupon = DB::table("sales")->whereNull("sales.deleted_at")
            ->join("sale_details", "sale_details.sale_id", "=", "sales.id")
            ->whereNull("sale_details.deleted_at")
            ->whereYear("sales.created_at", $year)
            ->whereNotNull("sale_details.code_cupon")
            ->select(
                DB::raw("ROUND(SUM(IF(sale_details.currency = 'USD', sale_details.discount * $dolar, sale_details.discount)),2) as discount_total"),
                DB::raw("COUNT(*) as count_total")
            )
            ->get();

        return response()->json([
            "query_cupon" => $query_cupon,
            "query_discount" => $query_discount,
            "sales_for_month_year_last" => $query_last,
            "sales_form_month_year_total" => $query->sum("sale_total"),
            "sales_for_month_year" => $query,
        ]);
    }


    public function report_discount_cupone_year(Request $request) {

        $year = $request->year;

        $query_cupon = DB::table("sales")->whereNull("sales.deleted_at")
                                                ->join("sale_details", "sale_details.sale_id", "=", "sales.id")
                                                ->whereNull("sale_details.deleted_at")
                                                ->whereYear("sales.created_at", $year)
                                                ->whereNotNull("sale_details.code_cupon") // ✅ Cambio importante
                                                ->select(
                                                    "sale_details.code_cupon as cupone",
                                                    DB::raw("COUNT(*) as count_total")
                                                )
                                                ->groupBy("sale_details.code_cupon")
                                                ->get();

        $query_discount = DB::table("sales")->whereNull("sales.deleted_at")
                                                ->join("sale_details", "sale_details.sale_id", "=", "sales.id")
                                                ->whereNull("sale_details.deleted_at")
                                                ->whereYear("sales.created_at", $year)
                                                ->whereNotNull("sale_details.code_discount") // ✅ Cambio importante
                                                ->select(
                                                    "sale_details.code_discount as code_discount",
                                                    DB::raw("COUNT(*) as count_total")
                                                )
                                                ->groupBy("sale_details.code_discount")
                                                ->get();

        return response()->json([
            "uso_discount_year" => $query_discount,
            "canje_cupone_year" => $query_cupon
        ]);
    }


    public function report_sales_for_categories(Request $request) {

        $year = $request->year;
        $month = $request->month;
        $dolar = 1200;

        $sales_for_month = DB::table("sales")->where("sales.deleted_at", NULL)
                                ->whereYear("sales.created_at", $year)
                                ->whereMonth("sales.created_at", $month)
                                ->select(DB::raw("ROUND(SUM(IF(sales.currency_payment = 'USD',sales.total * $dolar, sales.total)),2) as sales_total"))
                                ->get()
                                ->sum("sales_total");

        $month_last = Carbon::parse($year.'-'.$month.'-'.'01')->subMonth();

        $sales_for_month_last = DB::table("sales")->where("sales.deleted_at", NULL)
                                ->whereYear("sales.created_at", $month_last->format("Y"))
                                ->whereMonth("sales.created_at", $month_last->format("m"))
                                ->select(DB::raw("ROUND(SUM(IF(sales.currency_payment = 'USD',sales.total * $dolar, sales.total)),2) as sales_total"))
                                ->get()
                                ->sum("sales_total");

        $porcentageV = 0;
        if($sales_for_month_last > 0){
            $porcentageV = (($sales_for_month - $sales_for_month_last)/$sales_for_month_last)*100;
        }



        $query = DB::table("sales")->whereNull("sales.deleted_at")
                                            ->join("sale_details", "sale_details.sale_id", "=", "sales.id")
                                            ->whereNull("sale_details.deleted_at")
                                            ->whereYear("sales.created_at", $year)
                                            ->whereMonth("sales.created_at", $month)
                                            ->join("products", "products.id", "=", "sale_details.product_id")
                                            ->join("categories", "categories.id", "=", "products.categorie_first_id")
                                            // Agregar filtro para excluir devoluciones o registros erróneos
                                            ->where("sale_details.total", ">", 0)
                                            ->select(
                                                "categories.name as categorie_name",
                                                DB::raw("ROUND(SUM(IF(sale_details.currency = 'USD', sale_details.total * $dolar, sale_details.total)), 2) as categories_total"),
                                                DB::raw("ROUND(SUM(sale_details.quantity), 2) as categories_quantity"),
                                                // Promedio por unidad vendida
                                                DB::raw("ROUND(SUM(IF(sale_details.currency = 'USD', sale_details.total * $dolar, sale_details.total)) / SUM(sale_details.quantity), 2) as categories_avg")
                                            )
                                            ->groupBy("categorie_name")
                                            ->get();


        return response()->json([
            "sale_form_month" => $sales_for_month,
            "sale_form_month_categorie" => $query->sum("categories_total"),
            "porcentageV" => $porcentageV,
            "sale_for_categories" => $query,
        ]);
    }

    public function report_sales_for_categories_details(Request $request) {


        $year = $request->year;
        $month = $request->month;
        $dolar = 1200;

        $sales_month_categories = DB::table("sales")
            ->join("sale_details", "sale_details.sale_id", "=", "sales.id")
            ->join("products", "sale_details.product_id", "=", "products.id")
            ->join("categories", "products.categorie_first_id", "=", "categories.id")
            ->whereNull("sales.deleted_at")
            ->whereNull("sale_details.deleted_at")
            ->whereYear("sales.created_at", $year)
            ->whereMonth("sales.created_at", $month)
            ->select(
                "categories.name as categorie_name",
                         "categories.id as categorie_id",
                DB::raw("ROUND(SUM(IF(sale_details.currency = 'USD', sale_details.total * $dolar, sale_details.total)), 2) as categorie_total")
            )
            ->groupBy("categorie_name", "categorie_id")
            ->orderByDesc("categorie_total")
            ->take(4)
            ->get();

        $product_most_sales = collect([]);
        foreach ($sales_month_categories as $key => $sales_month_categ) {


            $query_product_most_sales = DB::table("sales")
            ->join("sale_details", "sale_details.sale_id", "=", "sales.id")
            ->join("products", "sale_details.product_id", "=", "products.id")
            ->join("categories", "products.categorie_first_id", "=", "categories.id")
            ->whereNull("sales.deleted_at")
            ->whereNull("sale_details.deleted_at")
            ->whereYear("sales.created_at", $year)
            ->whereMonth("sales.created_at", $month)
            ->where("products.categorie_first_id", $sales_month_categ->categorie_id)
            ->select(
                "products.title as product_title","products.sku as product_sku","products.price_ars as product_price",
                DB::raw("ROUND(SUM(IF(sale_details.currency = 'USD', sale_details.total * $dolar, sale_details.total)), 2) as product_total"),
                DB::raw("ROUND(SUM(sale_details.quantity), 2) as product_quantity_total"),
            )
            ->groupBy("product_title","product_sku","product_price")
            ->orderByDesc("product_total")
            ->take(3)
            ->get();


            $product_most_sales->push([
                "categorie_id" => $sales_month_categ->categorie_id,
                "products" => $query_product_most_sales,

            ]);
        }
        return response()->json([
            "product_most_sales" => $product_most_sales,
            "sale_month_categories" => $sales_month_categories,
        ]);
    }
}
