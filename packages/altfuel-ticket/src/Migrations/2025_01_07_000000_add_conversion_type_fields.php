<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('altfuel_tickets', function (Blueprint $table) {
            if (!Schema::hasColumn('altfuel_tickets', 'conversion_type')) {
                $table->string('conversion_type')->nullable()->after('status');
            }
        });

        Schema::table('altfuel_ticket_catagories', function (Blueprint $table) {
            if (!Schema::hasColumn('altfuel_ticket_catagories', 'conversion_type_enabled')) {
                $table->boolean('conversion_type_enabled')->default(false)->after('parent_id');
            }
            if (!Schema::hasColumn('altfuel_ticket_catagories', 'conversion_type_required')) {
                $table->boolean('conversion_type_required')->default(false)->after('conversion_type_enabled');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('altfuel_tickets', function (Blueprint $table) {
            if (Schema::hasColumn('altfuel_tickets', 'conversion_type')) {
                $table->dropColumn('conversion_type');
            }
        });

        Schema::table('altfuel_ticket_catagories', function (Blueprint $table) {
            $columns = [];
            if (Schema::hasColumn('altfuel_ticket_catagories', 'conversion_type_enabled')) {
                $columns[] = 'conversion_type_enabled';
            }
            if (Schema::hasColumn('altfuel_ticket_catagories', 'conversion_type_required')) {
                $columns[] = 'conversion_type_required';
            }
            if (!empty($columns)) {
                $table->dropColumn($columns);
            }
        });
    }
};
