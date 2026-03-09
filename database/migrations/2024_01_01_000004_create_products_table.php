<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateProductsTable extends Migration
{
    public function up()
    {
        Schema::create('products', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->text('short_description')->nullable();
            $table->unsignedBigInteger('category_id')->nullable();
            $table->decimal('price', 10, 2);
            $table->integer('quantity_available')->default(0);
            $table->boolean('is_available')->default(true);
            $table->boolean('is_featured')->default(false);
            $table->integer('minimum_rental_days')->default(1);
            $table->integer('maximum_rental_days')->default(30);
            $table->decimal('deposit_amount', 10, 2)->default(0);
            $table->decimal('weight', 8, 2)->nullable();
            $table->decimal('dimensions_length', 8, 2)->nullable();
            $table->decimal('dimensions_width', 8, 2)->nullable();
            $table->decimal('dimensions_height', 8, 2)->nullable();
            $table->text('care_instructions')->nullable();
            $table->json('specifications')->nullable();
            $table->integer('views_count')->default(0);
            $table->decimal('rating_average', 3, 2)->default(0);
            $table->integer('rating_count')->default(0);
            $table->timestamps();

            // Foreign keys
            $table->foreign('category_id')->references('id')->on('product_categories')->onDelete('set null');

            // Índices
            $table->index('category_id');
            $table->index('is_available');
            $table->index('is_featured');
            $table->index('price');
            $table->index('rating_average');
            $table->index(['is_available', 'category_id']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('products');
    }
}

