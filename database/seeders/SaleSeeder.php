<?php

namespace Database\Seeders;

use App\Models\Sale\Sale;
use Illuminate\Support\Str;
use App\Models\Cupone\Cupone;
use App\Models\Product\Product;
use App\Models\Sale\SaleAddres;
use App\Models\Sale\SaleDetail;
use Illuminate\Database\Seeder;
use App\Models\Discount\Discount;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class SaleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Sale::factory()->count(300)->create()->each(function($sale) {
            $faker = \Faker\Factory::create('es_AR'); // Using Spanish (Argentina) locale for more realistic data

            // Create a sale address with more realistic data for the region
            $this->createSaleAddress($sale, $faker);

            // Determine number of items - weighted towards smaller orders
            $itemWeights = [
                1 => 35,  // 35% chance of single item
                2 => 30,  // 30% chance of 2 items
                3 => 20,  // 20% chance of 3 items
                4 => 10,  // 10% chance of 4 items
                5 => 5    // 5% chance of 5 items
            ];

            $num_items = $this->weightedRandom($itemWeights);

            $sum_total_sale = 0;

            // Create details for each item in the sale
            for ($i = 0; $i < $num_items; $i++) {
                // Quantity weighted more heavily toward smaller quantities
                $quantityWeights = [
                    1 => 60,   // 60% chance of quantity 1
                    2 => 25,   // 25% chance of quantity 2
                    3 => 8,    // 8% chance of quantity 3
                    4 => 3,    // 3% chance of quantity 4
                    5 => 2,    // 2% chance of quantity 5
                    6 => 1,    // 1% chance of quantity 6
                    7 => 0.5,  // 0.5% chance of quantity 7
                    8 => 0.3,  // 0.3% chance of quantity 8
                    9 => 0.1,  // 0.1% chance of quantity 9
                    10 => 0.1, // 0.1% chance of quantity 10
                ];
                $quantity = $this->weightedRandom($quantityWeights);

                // Get a random product
                $product = Product::inRandomOrder()->first();

                // Determine if discount/coupon applies (with realistic distribution)
                // 1 = coupon, 2 = discount, 3 = no discount
                $discountTypeWeights = [
                    1 => 15,  // 15% chance of coupon
                    2 => 25,  // 25% chance of discount
                    3 => 60,  // 60% chance of no discount
                ];
                $is_cupon_discount = $this->weightedRandom($discountTypeWeights);
                $discount_cupone = $this->getDiscountCupone($is_cupon_discount);

                // Calculate price based on currency
                $price_unit = $sale->currency_total == 'ARS' ? $product->price_ars : $product->price_usd;
                $unit_subtotal = $this->getTotalProduct($discount_cupone, $product, $sale->currency_total);
                $line_total = $unit_subtotal * $quantity;

                // Create sale detail
                $sale_detail = SaleDetail::create([
                    "sale_id" => $sale->id,
                    "product_id" => $product->id,
                    "type_discount" => $discount_cupone ? $discount_cupone->type_discount : NULL,
                    "discount" => $discount_cupone ? $discount_cupone->discount : NULL,
                    "type_campaing" => $is_cupon_discount == 2 ? $discount_cupone->type_campaing : NULL,
                    "code_cupon" => $is_cupon_discount == 1 ? $discount_cupone->code : NULL,
                    "code_discount" => $is_cupon_discount == 2 ? $discount_cupone->code : NULL,
                    "product_variation_id" => NULL,
                    "quantity" => $quantity,
                    "price_unit" => $price_unit,
                    "subtotal" => $unit_subtotal,
                    "total" => $line_total,
                    "currency" => $sale->currency_total,
                    "created_at" => $sale->created_at,
                    "updated_at" => $sale->updated_at,
                ]);

                $sum_total_sale += $line_total;
            }

            // Update the sale with the calculated totals
            if ($sale->currency_total != $sale->currency_payment) {
                // Use the real exchange rate from the sale to convert
                $exchange_rate = $sale->price_dolar ?: 1050; // Default if not set
                $sum_total_sale_converted = round($sum_total_sale / $exchange_rate, 2);

                $sale->update([
                    "subtotal" => $sum_total_sale_converted,
                    "total" => $sum_total_sale_converted,
                ]);
            } else {
                $sale->update([
                    "subtotal" => $sum_total_sale,
                    "total" => $sum_total_sale,
                ]);
            }
        });
        // php artisan db:seed --class=SaleSeeder
    }

    /**
     * Create a realistic sale address based on the region
     */
    protected function createSaleAddress($sale, $faker)
    {
        // Determine country - weighted towards Argentina
        $countryWeights = [
            'Argentina' => 70,
            'Uruguay' => 10,
            'Chile' => 8,
            'Brasil' => 5,
            'Peru' => 5,
            'Bolivia' => 2,
        ];
        $country = $this->weightedRandom($countryWeights);

        // Cities based on country for more realism
        $cities = [
            'Argentina' => [
                'Buenos Aires' => 40,
                'Córdoba' => 15,
                'Rosario' => 12,
                'Mendoza' => 10,
                'La Plata' => 8,
                'Mar del Plata' => 8,
                'San Miguel de Tucumán' => 7,
            ],
            'Uruguay' => [
                'Montevideo' => 60,
                'Salto' => 15,
                'Punta del Este' => 15,
                'Ciudad de la Costa' => 10,
            ],
            'Chile' => [
                'Santiago' => 50,
                'Valparaíso' => 20,
                'Concepción' => 15,
                'La Serena' => 15,
            ],
            'Brasil' => [
                'São Paulo' => 40,
                'Rio de Janeiro' => 30,
                'Porto Alegre' => 20,
                'Curitiba' => 10,
            ],
            'Peru' => [
                'Lima' => 60,
                'Arequipa' => 20,
                'Cusco' => 15,
                'Trujillo' => 5,
            ],
            'Bolivia' => [
                'Santa Cruz de la Sierra' => 50,
                'La Paz' => 35,
                'Cochabamba' => 15,
            ],
        ];

        // Select a city based on the country
        $city = $this->weightedRandom($cities[$country]);

        // Phone format by country
        $phoneFormats = [
            'Argentina' => '+54 9 ## #### ####',
            'Uruguay' => '+598 9 ### ####',
            'Chile' => '+56 9 #### ####',
            'Brasil' => '+55 ## 9#### ####',
            'Peru' => '+51 9## ### ###',
            'Bolivia' => '+591 7### ####',
        ];

        // Postcode format by country
        $postcodeFormat = match($country) {
            'Argentina' => $faker->regexify('[A-Z][0-9]{4}[A-Z]{3}'),
            'Uruguay' => $faker->numberBetween(10000, 99999),
            'Chile' => $faker->numberBetween(1000000, 9999999),
            'Brasil' => $faker->regexify('[0-9]{5}-[0-9]{3}'),
            'Peru' => $faker->numberBetween(10000, 99999),
            'Bolivia' => $faker->numberBetween(1000, 9999),
        };

        // Create the address
        return SaleAddres::create([
            "sale_id" => $sale->id,
            "name" => $faker->firstName(),
            "surname" => $faker->lastName(),
            "company" => rand(1, 100) <= 25 ? $faker->company() : null, // 25% chance of company
            "country_region" => $country,
            "address" => $faker->streetAddress(),
            "street" => $faker->streetName(),
            "city" => $city,
            "postcode_zip" => $postcodeFormat,
            "phone" => $faker->numerify($phoneFormats[$country]),
            "email" => $faker->unique()->safeEmail(),
        ]);
    }

    /**
     * Retrieve a discount or coupon based on the type
     */
    public function getDiscountCupone($is_cupon_discount)
    {
        if ($is_cupon_discount != 3) {
            if ($is_cupon_discount == 1) {
                // Get a coupon (assuming all coupons in DB are valid)
                $cupone = Cupone::inRandomOrder()->first();
                return $cupone;
            } else {
                // Get a discount (assuming all discounts in DB are valid)
                $discount = Discount::inRandomOrder()->first();
                return $discount;
            }
        }
        return null;
    }

    /**
     * Calculate the price of a product after applying discounts
     */
    public function getTotalProduct($discount_cupone, $product, $currency)
    {
        // Get base price in the appropriate currency
        $price = ($currency == "ARS") ? $product->price_ars : $product->price_usd;

        // Apply discount if one exists
        if ($discount_cupone) {
            if ($discount_cupone->type_discount == 1) {
                // Percentage discount
                $discount_rate = min($discount_cupone->discount, 100) * 0.01; // Cap at 100%
                $price = $price - ($discount_rate * $price);
            } else {
                // Fixed amount discount
                $price = max($price - $discount_cupone->discount, 0); // Ensure price doesn't go below 0
            }
        }

        return round($price, 2); // Round to 2 decimal places for currency
    }

    /**
     * Pick a random element based on weighted probabilities
     */
    protected function weightedRandom(array $weights)
    {
        $total = array_sum($weights);
        $rand = rand(1, $total * 10) / 10; // Support decimal weights

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
