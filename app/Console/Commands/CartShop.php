<?php

namespace App\Console\Commands;

use App\Mail\CartAbandoned;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class CartShop extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'abandoned:cart-shop';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Voy a enviar un curreo de recordatorio cada 6 horas durante 2 dias';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        // date_default_timezone_get("America/Argentina/Buenos_aires");
        echo date_default_timezone_get();

        $users = User::whereHas("carts", function($query) {
            $query->whereRaw("DATEDIFF(updated_at,created_at)<3");
        })->get();

        foreach ($users as $key => $user) {
            foreach ($user->carts as $cart) {
                $cart->update([
                    "updated_at" => now()
                ]);
            }

            Mail::to($user->email)->send(new CartAbandoned($user, $user->carts));
        }

        dd($users);
    }
}
