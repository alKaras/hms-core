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
            $table->decimal('total_amount', 10, 2);

            $table->timestamp('created_at')->default(now());
            $table->timestamp('confirmed_at')->nullable()->default(null);
            $table->string('status')->default('pending'); // pending, paid, canceled

            $table->timestamp('cancelled_at')->nullable()->default(null);
            $table->string('cancel_reason')->nullable()->default(null);
            $table->timestamp('reserve_exp')->default(now()->addMinutes(15));

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->timestamp('updated_at')->default(now());
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
