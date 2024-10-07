<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->decimal('sum_total', 10, 2);
            $table->decimal('sum_subtotal', 10, 2)->default(0);

            $table->timestamp('created_at')->default(now());
            $table->timestamp('confirmed_at')->nullable()->default(null);
            $table->integer('status')->default(0); // pending, paid, canceled

            $table->timestamp('cancelled_at')->nullable()->default(null);
            $table->string('cancel_reason')->nullable()->default(null);
            $table->timestamp('reserve_exp');

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
