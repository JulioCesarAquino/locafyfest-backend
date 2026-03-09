<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateOrdersTable extends Migration
{
    public function up()
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('order_number', 50)->unique();
            $table->unsignedBigInteger('client_id');
            $table->enum('status', [
                'pending', 'confirmed', 'preparing', 'ready_for_delivery',
                'delivered', 'in_use', 'returned', 'cancelled', 'completed'
            ])->default('pending');
            $table->date('rental_start_date');
            $table->date('rental_end_date');
            $table->unsignedBigInteger('delivery_address_id')->nullable();
            $table->unsignedBigInteger('pickup_address_id')->nullable();
            $table->decimal('subtotal', 10, 2);
            $table->decimal('delivery_fee', 10, 2)->default(0);
            $table->decimal('discount_amount', 10, 2)->default(0);
            $table->decimal('total_amount', 10, 2);
            $table->decimal('deposit_amount', 10, 2)->default(0);
            $table->enum('payment_status', ['pending', 'paid', 'failed', 'refunded', 'partial_refund'])->default('pending');
            $table->string('payment_method', 30)->nullable();
            $table->string('payment_transaction_id')->nullable();
            $table->text('notes')->nullable();
            $table->text('cancellation_reason')->nullable();
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamp('returned_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamps();

            // Foreign keys
            $table->foreign('client_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('delivery_address_id')->references('id')->on('addresses')->onDelete('set null');
            $table->foreign('pickup_address_id')->references('id')->on('addresses')->onDelete('set null');

            // Índices
            $table->index('client_id');
            $table->index('status');
            $table->index('payment_status');
            $table->index(['rental_start_date', 'rental_end_date']);
            $table->index('created_at');
        });
    }

    public function down()
    {
        Schema::dropIfExists('orders');
    }
}

