<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('workshop_registrations')) {
            Schema::create('workshop_registrations', function (Blueprint $table) {
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
            Schema::table('workshop_registrations', function (Blueprint $table) {
                $columns = [
                    'name' => fn () => $table->string('name')->after('id'),
                    'national_id' => fn () => $table->string('national_id', 10)->after('name'),
                    'birth_certificate_number' => fn () => $table->string('birth_certificate_number', 10)->after('national_id'),
                    'birth_date' => fn () => $table->date('birth_date')->after('birth_certificate_number'),
                    'mobile' => fn () => $table->string('mobile', 11)->after('birth_date'),
                    'phone' => fn () => $table->string('phone', 11)->after('mobile'),
                    'course_key' => fn () => $table->string('course_key')->after('phone'),
                    'course_title' => fn () => $table->string('course_title')->after('course_key'),
                    'price' => fn () => $table->unsignedBigInteger('price')->after('course_title'),
                    'authority' => fn () => $table->string('authority')->nullable()->after('price'),
                    'status' => fn () => $table->string('status')->default('draft')->after('authority'),
                    'ref_id' => fn () => $table->string('ref_id')->nullable()->after('status'),
                ];

                foreach ($columns as $column => $callback) {
                    if (! Schema::hasColumn('workshop_registrations', $column)) {
                        $callback();
                    }
                }

                if (! Schema::hasColumn('workshop_registrations', 'created_at')) {
                    $table->timestamps();
                }
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('workshop_registrations');
    }
};
