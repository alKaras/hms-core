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
        Schema::create('doctors', function (Blueprint $table) {
            $table->id();
            $table->string('specialization');
            $table->tinyInteger('hidden')->default(0);
            $table->unsignedBigInteger('user_id');
            $table->timestamps();


            $table->foreign('user_id')->references('id')->on('users');
            $table->unique('user_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('doctor_departments');
        Schema::dropIfExists('doctors');
    }
};
