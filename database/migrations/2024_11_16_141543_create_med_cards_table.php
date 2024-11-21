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
        Schema::create('medcards', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('firstname');
            $table->string('lastname');
            $table->text('date_birthday')->nullable();
            $table->enum('gender', ['male', 'female', 'non-binary'])->nullable();
            $table->string('contact_number')->nullable();
            $table->longText('address')->nullable();
            $table->string('blood_type')->nullable();
            $table->longText('allergies')->nullable();
            $table->longText('chronic_conditions')->nullable();
            $table->longText('current_medications')->nullable();
            $table->string('emergency_contact_name')->nullable();
            $table->string('emergency_contact_phone')->nullable();
            $table->longText('insurance_details')->nullable();
            $table->longText('additional_notes')->nullable();
            $table->enum('status', ['active', 'inactive', 'archived'])->default('active');
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('med_cards');
    }
};
