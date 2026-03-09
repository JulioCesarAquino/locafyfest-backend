<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSystemSettingsTable extends Migration
{
    public function up()
    {
        Schema::create('system_settings', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('key', 100)->unique();
            $table->text('value')->nullable();
            $table->text('description')->nullable();
            $table->enum('data_type', ['string', 'integer', 'float', 'boolean', 'json', 'text'])->default('string');
            $table->string('group', 50)->default('general'); // Para agrupar configurações
            $table->boolean('is_public')->default(false); // Se pode ser acessada publicamente
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();

            // Foreign keys
            $table->foreign('updated_by')->references('id')->on('users')->onDelete('set null');

            // Índices
            $table->index('group');
            $table->index('is_public');
            $table->index('updated_by');
        });
    }

    public function down()
    {
        Schema::dropIfExists('system_settings');
    }
}

