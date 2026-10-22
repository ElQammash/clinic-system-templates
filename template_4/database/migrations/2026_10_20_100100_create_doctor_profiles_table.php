<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('doctor_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('title_ar')->nullable();      // e.g. "استشاري طب الأطفال"
            $table->string('title_en')->nullable();
            $table->string('specialty', 50)->nullable(); // ClinicSpecialty value
            $table->string('color', 7)->default('#0d9488');
            $table->unsignedSmallInteger('slot_minutes')->default(15);
            $table->unsignedBigInteger('consultation_fee')->default(0); // minor units (piastres)
            $table->string('license_number', 50)->nullable();
            $table->string('signature_path')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('doctor_profiles');
    }
};
