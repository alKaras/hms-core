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
        Schema::create('services', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('description')->nullable();
            $table->tinyInteger('is_public')->default(1);
            $table->unsignedBigInteger('doctor_id');
            $table->unsignedBigInteger('hospital_id');
            $table->unsignedBigInteger('department_id');
            $table->timestamps();

            $table->foreign('doctor_id')->references('id')->on('doctors');
            $table->foreign('hospital_id')->references('id')->on('hospital');
            $table->foreign('department_id')->references('id')->on('department');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('services');
    }
};
