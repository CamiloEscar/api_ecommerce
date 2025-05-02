<?php

namespace Database\Factories\Sale;

use App\Models\User;
use App\Models\Sale\Sale;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Sale>
 */
class SaleFactory extends Factory
{
    protected $model = Sale::class;
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        // More realistic date distribution with higher frequency in recent months
        $dateWeights = [
            '2024-01' => 5,
            '2024-02' => 5,
            '2024-03' => 7,
            '2024-04' => 8,
            '2024-05' => 10,
            '2024-06' => 12,
            '2024-07' => 12,
            '2024-08' => 15,
            '2024-09' => 18,
            '2024-10' => 20,
            '2024-11' => 25,
            '2024-12' => 35,
        ];

        $month = $this->weightedRandomMonth($dateWeights);
        $day = rand(1, 28); // Avoid invalid dates in February
        $hour = rand(8, 22); // More realistic shopping hours
        $minute = rand(0, 59);
        $second = rand(0, 59);

        $date_sales = \DateTime::createFromFormat(
            'Y-m-d H:i:s',
            "2024-{$month}-{$day} {$hour}:{$minute}:{$second}"
        );

        // Payment methods with realistic weights
        $paymentMethods = [
            'MERCADOPAGO' => 75,
            'PAYPAL' => 20,
            'TRANSFER' => 5,
        ];
        $method_payment = $this->weightedRandom($paymentMethods);

        // Currency distribution
        // If in Argentina (higher probability), more likely to use ARS
        $isArgentinaBuyer = (rand(1, 100) <= 70); // 70% Argentina buyers

        if ($isArgentinaBuyer) {
            $currency_payment = (rand(1, 100) <= 85) ? 'ARS' : 'USD'; // 85% ARS for Argentinian buyers
        } else {
            $currency_payment = (rand(1, 100) <= 80) ? 'USD' : 'ARS'; // 80% USD for non-Argentinian buyers
        }

        // Product currency might differ from payment currency
        $currency_total = $currency_payment;
        if ($currency_payment == 'USD') {
            // Sometimes people view in ARS but pay in USD
            $currency_total = (rand(1, 100) <= 30) ? 'ARS' : 'USD';
        }

        // Dollar exchange rate variations
        $price_dolar = 0;
        if ($currency_payment != $currency_total) {
            // Realistic exchange rate for 2024 in Argentina (with variations)
            $base_exchange_rate = 1050; // Base rate
            $variation = rand(-50, 50); // Some variation
            $price_dolar = $base_exchange_rate + $variation;
        }

        // Transaction ID formats by payment provider
        $n_transaccion = match($method_payment) {
            'MERCADOPAGO' => 'MP' . $this->faker->numerify('########'),
            'PAYPAL' => $this->faker->bothify('PP-????-####-####-####'),
            'TRANSFER' => 'TR' . $this->faker->numerify('#########'),
            default => Str::random(10),
        };

        // Preference ID formats by payment provider
        $preference_id = null;
        if ($method_payment == 'MERCADOPAGO') {
            $preference_id = $this->faker->numerify('1234567890##########');
        } elseif ($method_payment == 'PAYPAL') {
            $preference_id = $this->faker->bothify('PAY-????####????####????####');
        }

        return [
            "user_id" => User::where("type_user", 2)->inRandomOrder()->first()->id,
            "method_payment" => $method_payment,
            "currency_total" => $currency_total,
            "currency_payment" => $currency_payment,
            "discount" => 0, // Will be calculated in seeder
            "subtotal" => 0, // Will be calculated in seeder
            "total" => 0, // Will be calculated in seeder
            "price_dolar" => $price_dolar,
            "description" => $this->generateOrderDescription($method_payment),
            "n_transaccion" => $n_transaccion,
            "preference_id" => $preference_id,
            "created_at" => $date_sales,
            "updated_at" => $date_sales,
        ];
    }

    /**
     * Generate a realistic order description based on payment method
     */
    protected function generateOrderDescription($method_payment): string
    {
        $descriptions = [
            'MERCADOPAGO' => [
                'Compra en línea procesada por MercadoPago',
                'Orden web con pago mediante MercadoPago',
                'Pago de productos mediante MercadoPago',
                'Transacción en línea - MercadoPago',
                'Compra web con MercadoPago'
            ],
            'PAYPAL' => [
                'Compra internacional con PayPal',
                'Orden procesada mediante PayPal',
                'Pago de productos vía PayPal',
                'Transacción internacional - PayPal',
                'Compra web con PayPal'
            ],
            'TRANSFER' => [
                'Compra mediante transferencia bancaria',
                'Pago por transferencia directa',
                'Orden procesada por transferencia',
                'Compra pagada por transferencia bancaria',
                'Transferencia por productos en línea'
            ]
        ];

        $options = $descriptions[$method_payment] ?? ['Compra en línea'];
        return $options[array_rand($options)];
    }

    /**
     * Select a random month with weighted probabilities
     */
    protected function weightedRandomMonth(array $weights): string
    {
        $total = array_sum($weights);
        $rand = rand(1, $total);

        $current = 0;
        foreach ($weights as $month => $weight) {
            $current += $weight;
            if ($rand <= $current) {
                return substr($month, -2); // Extract just the month part (e.g., "01" from "2024-01")
            }
        }

        return '12'; // Default to December
    }

    /**
     * Pick a random element based on weighted probabilities
     */
    protected function weightedRandom(array $weights): string
    {
        $total = array_sum($weights);
        $rand = rand(1, $total);

        $current = 0;
        foreach ($weights as $item => $weight) {
            $current += $weight;
            if ($rand <= $current) {
                return $item;
            }
        }

        // Return first key as fallback
        return array_key_first($weights);
    }
}
