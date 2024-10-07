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
        Schema::create('order_status_ref', function (Blueprint $table) {
            $table->id();
            $table->string('status_name');
            $table->timestamps();
        });

        DB::table('order_status_ref')->insert([
            ['id' => 1, 'status_name' => 'pending'],
            ['id' => 2, 'status_name' => 'paid'],
            ['id' => 3, 'status_name' => 'canceled'],
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('order_status_refs');
    }
};
