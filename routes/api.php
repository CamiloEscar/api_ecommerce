<?php

use App\Http\Controllers\Admin\Cupone\CuponeController;
use App\Http\Controllers\Admin\Discount\DiscountController;
use App\Http\Controllers\Admin\Product\ProductController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\Admin\Product\CategorieController;
use App\Http\Controllers\Admin\Product\AttributeProductController;
use App\Http\Controllers\Admin\Product\BrandController;
use App\Http\Controllers\Admin\Product\ProductSpecificationsController;
use App\Http\Controllers\Admin\Product\ProductVariationsAnidadoController;
use App\Http\Controllers\Admin\Product\ProductVariationsController;
use App\Http\Controllers\Admin\Sale\KpiSaleReportController;
use App\Http\Controllers\Admin\Sale\SalesController;
use App\Http\Controllers\Admin\SliderController;
use App\Http\Controllers\DolarController;
use App\Http\Controllers\Ecommerce\CartController;
use App\Http\Controllers\Ecommerce\HomeController;
use App\Http\Controllers\Ecommerce\ReviewController;
use App\Http\Controllers\Ecommerce\SaleController;
use App\Http\Controllers\Ecommerce\UserAdressController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::group([

    // 'middleware' => 'auth:api',
    'prefix' => 'auth'

], function ($router) {
    Route::post('/register', [AuthController::class, 'register'])->name('register');
    Route::post('/login', [AuthController::class, 'login'])->name('login');
    Route::post('/login_ecommerce', [AuthController::class, 'login_ecommerce'])->name('login_ecommerce');
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
    Route::post('/refresh', [AuthController::class, 'refresh'])->name('refresh');
    Route::post('/me', [AuthController::class, 'me'])->name('me');
    Route::post('/verified_auth', [AuthController::class, 'verified_auth'])->name('verified_auth');
    //
    Route::post('/verified_email', [AuthController::class, 'verified_email'])->name('verified_email');
    Route::post('/verified_code', [AuthController::class, 'verified_code'])->name('verified_code');
    Route::post('/new_password', [AuthController::class, 'new_password'])->name('new_password');
});

//grupo para el adminisitrador, rutas necesarias para gestionar las rutas de categorias
Route::group([

    "middleware" => "auth:api",
    "prefix" => "admin"
], function ($router) {
    Route::get("categories/config", [CategorieController::class, "config"]);
    Route::resource("categories", CategorieController::class);
    Route::post("categories/{id}", [CategorieController::class, "update"]);

    Route::post("properties", [AttributeProductController::class, "store_propertie"]);
    Route::delete("properties/{id}", [AttributeProductController::class, "destroy_propertie"]);
    Route::resource("attributes", AttributeProductController::class);
    // //url para editar las properties TODO: hacer
    // Route::put("properties/{id}", [AttributeProductController::class, "update_propertie"]);

    Route::resource("sliders", SliderController::class);
    Route::post("sliders/{id}", [SliderController::class, "update"]);


    Route::get("products/config", [ProductController::class, "config"]);
    Route::post("products/imagens", [ProductController::class, "imagens"]);
    Route::delete("products/imagens/{id}", [ProductController::class, "delete_imagens"]);
    Route::post("products/index", [ProductController::class, "index"]);
    Route::resource("products", ProductController::class);
    Route::post("products/{id}", [ProductController::class, "update"]);

    Route::resource("brands", BrandController::class);

    Route::get("variations/config", [ProductVariationsController::class, "config"]);
    Route::resource("variations", ProductVariationsController::class);
    Route::resource("anidado_variations", ProductVariationsAnidadoController::class);

    Route::resource("specifications", ProductSpecificationsController::class);

    Route::get("cupones/config", [CuponeController::class, "config"]);

    Route::resource("cupones", CuponeController::class);

    Route::resource("discounts", DiscountController::class);

    Route::post("sales/list", [SalesController::class, "list"]);

    Route::group([

        "prefix" => "kpi"
    ], function ($router) {
        Route::post("report_sales_country_for_year", [KpiSaleReportController::class, "report_sales_country_for_year"]);
        Route::post("report_sales_week_categorias", [KpiSaleReportController::class, "report_sales_week_categorias"]);
        Route::post("report_sales_week_discounts", [KpiSaleReportController::class, "report_sales_week_discounts"]);
        Route::post("report_sales_month_selected", [KpiSaleReportController::class, "report_sales_month_selected"]);
        Route::post("report_sales_for_month_year_selected", [KpiSaleReportController::class, "report_sales_for_month_year_selected"]);
        Route::post("report_discount_cupone_year", [KpiSaleReportController::class, "report_discount_cupone_year"]);
    });
});

Route::get("sales/list-excel", [SalesController::class, "list_excel"]);
Route::get("sales/report-pdf/{id}", [SalesController::class, "report_pdf"]);



Route::group([

    "prefix" => "ecommerce"
], function ($router) {
    Route::get("home",  [HomeController::class, "home"]);
    Route::get("menus",  [HomeController::class, "menus"]);
    Route::get("producto/{slug}",  [HomeController::class, "show_product"]);

    //middleware que va a obligar a que se use el carrito de compra si estas autentificado
    Route::group([
        "middleware" => 'auth:api'
    ], function($router){
        Route::delete('carts/delete_all', [CartController::class, "delete_all"]);
        Route::post('carts/apply_cupon', [CartController::class, "apply_cupon"]);
        Route::resource('carts', CartController::class);


        Route::resource('user_address', UserAdressController::class);

        Route::get('mercadopago', [SaleController::class, "mercadopago"]);
        Route::get('sale/{id}', [SaleController::class, "show"]);
        Route::post('checkout', [SaleController::class, "store"]);
        Route::post('checkout-temp', [SaleController::class, "checkout_temp"]);
        Route::post('checkout-mercadopago', [SaleController::class, "checkout_mercadopago"]);

        Route::get("profile_client/me", [AuthController::class, "me"]);
        Route::get('profile_client/orders', [SaleController::class, "orders"]);
        Route::post("profile_client", [AuthController::class, "update"]);

        Route::resource('reviews', ReviewController::class);


    });

    Route::get("config-filter-advance",  [HomeController::class, "config_filter_advance"]);
    Route::post("filter-advance-product",  [HomeController::class, "filter_advance_product"]);
    Route::post("campaing-discount-link",  [HomeController::class, "campaing_discount_link"]);

    //api dolar
    // Route::get('mercadopago', [SaleController::class, "mercadopago"]);
    // Route::get('/dolar', [DolarController::class, 'obtenerDolar']);
});
