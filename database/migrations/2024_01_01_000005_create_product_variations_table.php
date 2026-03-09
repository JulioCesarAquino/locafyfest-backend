<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateProductVariationsTable extends Migration
{
    public function up()
    {
        Schema::create('product_variations', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('product_id');
            $table->string('name', 100); // Ex: "Tamanho", "Cor"
            $table->string('value', 100); // Ex: "M", "Azul"
            $table->string('sku', 50)->unique()->nullable();
            $table->decimal('price_modifier', 10, 2)->default(0);
            $table->integer('quantity_available')->default(0);
            $table->boolean('is_available')->default(true);
            $table->timestamps();

            // Foreign keys
            $table->foreign('product_id')->references('id')->on('products')->onDelete('cascade');

            // Índices
            $table->index('product_id');
            $table->index('is_available');
            $table->index(['product_id', 'name']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('product_variations');
    }
}

