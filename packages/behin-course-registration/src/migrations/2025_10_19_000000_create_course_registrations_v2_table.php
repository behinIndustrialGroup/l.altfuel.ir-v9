<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // اگر جدول وجود ندارد، ایجادش کن
        if (!Schema::hasTable('course_registrations')) {
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
        } else {
            // اگر جدول وجود دارد، ستون‌ها را یکی‌یکی بررسی کن
            Schema::table('course_registrations', function (Blueprint $table) {
                if (!Schema::hasColumn('course_registrations', 'name')) {
                    $table->string('name')->after('id');
                }
                if (!Schema::hasColumn('course_registrations', 'national_id')) {
                    $table->string('national_id', 10)->after('name');
                }
                if (!Schema::hasColumn('course_registrations', 'birth_certificate_number')) {
                    $table->string('birth_certificate_number', 10)->after('national_id');
                }
                if (!Schema::hasColumn('course_registrations', 'birth_date')) {
                    $table->date('birth_date')->after('birth_certificate_number');
                }
                if (!Schema::hasColumn('course_registrations', 'mobile')) {
                    $table->string('mobile', 11)->after('birth_date');
                }
                if (!Schema::hasColumn('course_registrations', 'phone')) {
                    $table->string('phone', 11)->after('mobile');
                }
                if (!Schema::hasColumn('course_registrations', 'course_key')) {
                    $table->string('course_key')->after('phone');
                }
                if (!Schema::hasColumn('course_registrations', 'course_title')) {
                    $table->string('course_title')->after('course_key');
                }
                if (!Schema::hasColumn('course_registrations', 'price')) {
                    $table->unsignedBigInteger('price')->after('course_title');
                }
                if (!Schema::hasColumn('course_registrations', 'authority')) {
                    $table->string('authority')->nullable()->after('price');
                }
                if (!Schema::hasColumn('course_registrations', 'status')) {
                    $table->string('status')->default('draft')->after('authority');
                }
                if (!Schema::hasColumn('course_registrations', 'ref_id')) {
                    $table->string('ref_id')->nullable()->after('status');
                }
                if (!Schema::hasColumn('course_registrations', 'created_at')) {
                    $table->timestamps();
                }
            });
        }
    }


    public function down(): void
    {
        Schema::dropIfExists('course_registrations');
    }
};
