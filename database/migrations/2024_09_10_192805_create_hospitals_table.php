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
        Schema::create('hospital', function (Blueprint $table) {
            $table->id();
            $table->string('alias');
            $table->string('hospital_phone', 13)->nullable();
            $table->string('hospital_email')->nullable();
            $table->timestamps();
        });

        Schema::create('hospital_content', function (Blueprint $table) {
            $table->id();
            $table->foreignId('hospital_id')->constrained('hospital');
            $table->string('title');
            $table->string('description');
            $table->string('address');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('hospital_content');
        Schema::dropIfExists('hospital');

    }
};
