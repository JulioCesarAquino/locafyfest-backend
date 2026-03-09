<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateReviewsTable extends Migration
{
    public function up()
    {
        Schema::create('reviews', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('order_id');
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('product_id');
            $table->integer('rating')->default(5);
            $table->string('title')->nullable();
            $table->text('comment')->nullable();
            $table->boolean('is_approved')->default(false);
            $table->boolean('is_verified_purchase')->default(true);
            $table->json('helpful_votes')->nullable(); // Array de user_ids que marcaram como útil
            $table->integer('helpful_count')->default(0);
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();

            // Unique constraint
            $table->unique(['order_id', 'product_id']);

            // Foreign keys
            $table->foreign('order_id')->references('id')->on('orders')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('product_id')->references('id')->on('products')->onDelete('cascade');

            // Índices
            $table->index('user_id');
            $table->index('product_id');
            $table->index('rating');
            $table->index('is_approved');
            $table->index(['product_id', 'is_approved']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('reviews');
    }
}

