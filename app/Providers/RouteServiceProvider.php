<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Route;

class RouteServiceProvider extends ServiceProvider
{
    /**
     * The path to the "home" route for your application.
     *
     * @var string
     */
    public const HOME = '/home';

    /**
     * Define your route model bindings, pattern filters, etc.
     *
     * @return void
     */
    public function boot()
    {
        $this->routes(function () {
            Route::prefix('api') // Prefijo para rutas de API
                ->middleware('api') // Middleware de API
                ->group(base_path('routes/api.php')); // Cargar las rutas de api.php

            Route::middleware('web') // Middleware de rutas web
                ->group(base_path('routes/web.php')); // Cargar las rutas de web.php
        });
    }
}
