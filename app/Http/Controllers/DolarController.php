<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Http;

class DolarController extends Controller
{
    public function obtenerDolar()
    {
        try {
            $response = Http::get('https://api.bluelytics.com.ar/v2/latest');
            $data = $response->json();

            return response()->json([
                'buy' => $data['blue']['value_buy'],
                'sell' => $data['blue']['value_sell'],
                'source' => 'Bluelytics'
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => 'No se pudo obtener el precio del dólar'], 500);
        }
    }
}
