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
        Schema::table('telegram_ticket_messages', function (Blueprint $table) {
            if (!Schema::hasColumn('telegram_ticket_messages', 'attachment_path')) {
                $table->string('attachment_path', 512)->nullable()->after('message');
            }

            if (!Schema::hasColumn('telegram_ticket_messages', 'attachment_name')) {
                $table->string('attachment_name')->nullable()->after('attachment_path');
            }

            if (!Schema::hasColumn('telegram_ticket_messages', 'attachment_mime')) {
                $table->string('attachment_mime', 120)->nullable()->after('attachment_name');
            }

            if (!Schema::hasColumn('telegram_ticket_messages', 'attachment_size')) {
                $table->unsignedBigInteger('attachment_size')->nullable()->after('attachment_mime');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('telegram_ticket_messages', function (Blueprint $table) {
            if (Schema::hasColumn('telegram_ticket_messages', 'attachment_size')) {
                $table->dropColumn('attachment_size');
            }

            if (Schema::hasColumn('telegram_ticket_messages', 'attachment_mime')) {
                $table->dropColumn('attachment_mime');
            }

            if (Schema::hasColumn('telegram_ticket_messages', 'attachment_name')) {
                $table->dropColumn('attachment_name');
            }

            if (Schema::hasColumn('telegram_ticket_messages', 'attachment_path')) {
                $table->dropColumn('attachment_path');
            }
        });
    }
};
