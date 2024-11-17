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
            $table->string('meet_link')->nullable()->after('medcard_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('med_appointments', function (Blueprint $table) {
            $table->dropColumn('meet_link');
        });
    }
};
