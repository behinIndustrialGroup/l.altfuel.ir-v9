<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('course_registrations', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('national_id', 10);
            $table->string('birth_certificate_number', 10);
            $table->date('birth_date');
            $table->string('mobile', 11);
            $table->string('phone', 11);
            $table->string('course_key');
            $table->string('course_title');
            $table->unsignedBigInteger('price');
            $table->string('authority')->nullable();
            $table->string('status')->default('draft');
            $table->string('ref_id')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('course_registrations');
    }
};
