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
        Schema::table('med_appointments', function (Blueprint $table) {
            $table->unsignedBigInteger('medcard_id')->nullable()->default(null)->after('status');

            $table->foreign('medcard_id')->references('id')->on('medcards');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('med_appointments', function (Blueprint $table) {
            $table->dropForeignIdFor('medcard_id');
        });
    }
};
