<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ProductCategoriesSeeder extends Seeder
{
    public function run()
    {
        DB::table('product_categories')->insert([
            [
                'name' => 'Som e Iluminação',
                'description' => 'Equipamentos de áudio e luz para eventos',
                'slug' => 'som-e-iluminacao',
            ],
            [
                'name' => 'Móveis',
                'description' => 'Mesas, cadeiras e outros móveis para locação',
                'slug' => 'moveis',
            ],
            [
                'name' => 'Decoração',
                'description' => 'Itens decorativos para festas e eventos',
                'slug' => 'decoracao',
            ],
        ]);
    }
}
