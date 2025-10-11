<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('telegram_ticket_messages', function (Blueprint $table) {
            if (!Schema::hasColumn('telegram_ticket_messages', 'platform')) {
                $table->string('platform', 50)->nullable()->after('platform_message_id');
            }
            if (!Schema::hasColumn('telegram_ticket_messages', 'feedback')) {
                $table->string('feedback', 20)->default('none')->after('platform');
            }
        });

        Schema::table('telegram_tickets', function (Blueprint $table) {
            if (!Schema::hasColumn('telegram_tickets', 'is_bot_generated')) {
                $table->boolean('is_bot_generated')->default(false)->after('status');
            }
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('telegram_ticket_messages', function (Blueprint $table) {
            if (Schema::hasColumn('telegram_ticket_messages', 'feedback')) {
                $table->dropColumn('feedback');
            }
            if (Schema::hasColumn('telegram_ticket_messages', 'platform')) {
                $table->dropColumn('platform');
            }
        });

        Schema::table('telegram_tickets', function (Blueprint $table) {
            if (Schema::hasColumn('telegram_tickets', 'is_bot_generated')) {
                $table->dropColumn('is_bot_generated');
            }
        });
    }
};
