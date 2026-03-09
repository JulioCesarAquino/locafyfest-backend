<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateNotificationsTable extends Migration
{
    public function up()
    {
        Schema::create('notifications', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('user_id');
            $table->enum('type', [
                'order_confirmed', 'order_delivered', 'order_returned', 'order_cancelled',
                'payment_received', 'payment_failed', 'reminder_return',
                'product_available', 'promotion', 'system_maintenance'
            ]);
            $table->string('title');
            $table->text('message');
            $table->boolean('is_read')->default(false);
            $table->json('data')->nullable(); // Dados adicionais da notificação
            $table->string('action_url')->nullable(); // URL para ação relacionada
            $table->timestamp('read_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();

            // Foreign keys
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');

            // Índices
            $table->index('user_id');
            $table->index(['user_id', 'is_read']);
            $table->index('type');
            $table->index('created_at');
            $table->index('expires_at');
        });
    }

    public function down()
    {
        Schema::dropIfExists('notifications');
    }
}

