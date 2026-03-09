<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ProductsSeeder extends Seeder
{
    public function run()
    {
        DB::table('products')->insert([
            'name' => 'Caixa de Som JBL 1000W',
            'description' => 'Som potente para eventos de médio porte',
            'category_id' => 1,
            'price' => 250,
            'quantity_available' => 5,
            'is_available' => true,
            'minimum_rental_days' => 1,
            'maximum_rental_days' => 7,
            'deposit_amount' => 100,
            'weight' => 12.5,
            'dimensions_length' => 40,
            'dimensions_width' => 30,
            'dimensions_height' => 60,
            'care_instructions' => 'Evitar exposição à chuva',
            'slug' => 'caixa-de-som-jbl-1000w', // <--- aqui
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
