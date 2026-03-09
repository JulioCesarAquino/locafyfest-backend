<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAddressesTable extends Migration
{
    public function up()
    {
        Schema::create('addresses', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('user_id');
            $table->enum('type', ['main', 'delivery', 'billing', 'other'])->default('main');
            $table->string('street');
            $table->string('number', 10)->nullable();
            $table->string('complement', 100)->nullable();
            $table->string('neighborhood', 100)->nullable();
            $table->string('city');
            $table->string('state', 2);
            $table->string('zip_code', 10);
            $table->decimal('latitude', 10, 8)->nullable();
            $table->decimal('longitude', 11, 8)->nullable();
            $table->boolean('is_default')->default(false);
            $table->timestamps();

            // Foreign keys
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');

            // Índices
            $table->index('user_id');
            $table->index(['user_id', 'is_default']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('addresses');
    }
}

